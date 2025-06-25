<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\BusinessChart;
use App\Filament\Widgets\BusinessCumulativeChart;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Village;
use App\Models\District;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Grid;
use Livewire\Attributes\On;
use App\Models\Business;
use Filament\Forms\Components\Actions\Action;

class BusinessAnalytics extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?string $title = 'Analisis Data Usaha';
    protected static ?string $navigationLabel = 'Analisis Data';
    protected static ?string $slug = 'business-analytics';

    protected static string $view = 'filament.pages.business-analytics';

    public ?string $districtId = null;
    public ?string $villageId = null;
    public string $activeTab = 'daily';
    public ?string $userId = null;

    // Determine if the navbar should be registered for this page
    public function shouldRegisterNavigation(): bool
    {
        // Only show in navigation for super_admin and Employee roles
        $user = Auth::user();
        return $user && ($user->roles->contains('name', 'super_admin') || $user->roles->contains('name', 'Mahasiswa'));
    }

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->roles->contains('name', 'Mahasiswa')) {
            $this->userId = $user->id;
        }

        if (!$user->roles->contains('name', 'super_admin')) {
            $assignedAreas = Assignment::where('user_id', $user->id)->get();

            // Prioritize district assignment if available
            $assignedDistrict = $assignedAreas->firstWhere('area_type', 'App\\Models\\District');
            if ($assignedDistrict) {
                $this->districtId = (string) $assignedDistrict->area_id;
            } else {
                // If no district assignment, check for village assignment
                $assignedVillage = $assignedAreas->firstWhere('area_type', 'App\\Models\\Village');
                if ($assignedVillage) {
                    $this->villageId = (string) $assignedVillage->area_id;
                    // Automatically set district if village is assigned and district can be determined
                    $village = Village::find($this->villageId);
                    if ($village && $village->district_id) {
                        $this->districtId = (string) $village->district_id;
                    }
                }
            }
        }
    }

    #[On('tab-changed')]
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function form(Form $form): Form
    {
        $user = Auth::user();
        $districtOptions = [];

        if ($user->roles->contains('name', 'super_admin')) {
            $districtOptions = District::pluck('name', 'id')->toArray();
        } else {
            $assignedDistrictIds = Assignment::where('user_id', $user->id)
                ->where('area_type', 'App\\Models\\District')
                ->pluck('area_id')
                ->toArray();

            if (!empty($assignedDistrictIds)) {
                $districtOptions = District::whereIn('id', $assignedDistrictIds)->pluck('name', 'id')->toArray();
            } else {
                // If no district assignment, check for village assignment to determine districts
                $assignedVillageIds = Assignment::where('user_id', $user->id)
                    ->where('area_type', 'App\\Models\\Village')
                    ->pluck('area_id')
                    ->toArray();

                if (!empty($assignedVillageIds)) {
                    $districtOptions = District::whereHas('villages', function (Builder $query) use ($assignedVillageIds) {
                        $query->whereIn('id', $assignedVillageIds);
                    })->pluck('name', 'id')->toArray();
                }
            }
        }

        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('districtId')
                            ->label('Kecamatan')
                            ->options($districtOptions)
                            ->placeholder('Pilih Kecamatan')
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('villageId', null);
                                $set('userId', null);
                                $this->refreshWidgets();
                            }),
                        Select::make('villageId')
                            ->label('Desa/Kelurahan')
                            ->options(function (Get $get) use ($user): array {
                                if ($user->roles->contains('name', 'Mahasiswa')) {
                                    // Only show villages where the user has assignments
                                    $assignedVillageIds = Assignment::where('user_id', $user->id)
                                        ->where('area_type', 'App\\Models\\Village')
                                        ->pluck('area_id')
                                        ->toArray();
                                    return Village::whereIn('id', $assignedVillageIds)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                                // Default: filter by selected district
                                return Village::where('district_id', $get('districtId'))
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->placeholder('Pilih Desa/Kelurahan')
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('userId', null);
                                $this->refreshWidgets();
                            }),
                    ]),
                Select::make('userId')
                    ->label('Petugas')
                    ->options(function (Get $get) use ($user): array {
                        $usersQuery = User::query();

                        if ($user->roles->contains('name', 'super_admin')) {
                            // Admins can filter by any user who has uploaded businesses or is assigned
                            $usersQuery->whereHas('businesses'); // Users who have uploaded businesses
                        } else {
                            // Non-admins see users relevant to their assigned areas/roles
                            $usersQuery->whereHas('assignments', function ($q) use ($user) {
                                $q->where('user_id', $user->id); // Only allow filtering by themselves initially
                            });
                        }

                        // Filter users based on selected district/village
                        if ($get('villageId')) {
                            $villageUsers = Business::where('village_id', $get('villageId'))
                                                    ->pluck('user_id')
                                                    ->filter()
                                                    ->unique()
                                                    ->toArray();
                            $usersQuery->whereIn('id', $villageUsers);
                        } elseif ($get('districtId')) {
                            $districtVillageIds = Village::where('district_id', $get('districtId'))
                                                         ->pluck('id')
                                                         ->toArray();
                            $districtUsers = Business::whereIn('village_id', $districtVillageIds)
                                                     ->pluck('user_id')
                                                     ->filter()
                                                     ->unique()
                                                     ->toArray();
                            $usersQuery->whereIn('id', $districtUsers);
                        }

                        return $usersQuery->pluck('name', 'id')->toArray();
                    })
                    ->placeholder('Pilih Petugas')
                    ->default(fn () => Auth::user()->roles->contains('name', 'Mahasiswa') ? Auth::id() : null)
                    ->visible(fn () => !Auth::user()->roles->contains('name', 'Mahasiswa'))
                    ->live()
                    ->afterStateUpdated(fn () => $this->refreshWidgets())
            ]);
    }

    public function refreshWidgets(): void
    {
        $this->dispatch('refreshChartData', villageId: $this->villageId, userId: $this->userId);
    }

    public function applyFilter(): void
    {
        $this->refreshWidgets();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // The widgets will be rendered directly in the Blade view
        ];
    }

    // Method to get a specific widget instance for rendering in Blade
    public function getWidget(string $widgetClass): object
    {
        return app()->make($widgetClass, ['villageId' => $this->villageId]);
    }
}
