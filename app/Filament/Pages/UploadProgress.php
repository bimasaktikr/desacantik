<?php

namespace App\Filament\Pages;

use App\Models\AssignmentUpload;
use App\Models\Business;
use App\Models\Assignment;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\BusinessChart;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use App\Models\District;
use App\Models\Village;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use App\Filament\Widgets\VillageBusinessSummary;

class UploadProgress extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?string $mode = 'table'; // Default mode for the table
    public ?string $districtId = null;
    public ?string $villageId = null;
    public ?string $userId = null;
    public $villageBusinessSummaryTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?string $title = 'Progress & Analytics';
    protected static ?string $navigationLabel = 'Progress & Analytics';
    protected static ?string $slug = 'upload-progress';

    protected static string $view = 'filament.pages.upload-progress';

    #[On('tab-changed')]
    public function setActiveTab(string $tab): void
    {
        $this->mode = $tab;
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->roles->contains('name', 'supervisor') || $user?->roles->contains('name', 'Employee') || $user?->roles->contains('name', 'super_admin')?? false;
    }

    public function mount(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user?->roles->contains('name', 'super_admin') && !$user?->roles->contains('name', 'Employee')) {
            redirect()->route('filament.admin.pages.dashboard');
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            // VillageBusinessSummary::class,
        ];
    }

    public function getDistricts()
    {
        return District::query()
            ->pluck('name', 'id')
            ->map(fn ($name, $id) => ['label' => $name, 'value' => $id])
            ->values()
            ->toArray();
    }

    public function getVillages()
    {
        if (!$this->districtId) {
            return [];
        }

        return Village::query()
            ->where('district_id', $this->districtId)
            ->pluck('name', 'id')
            ->map(fn ($name, $id) => ['label' => $name, 'value' => $id])
            ->values()
            ->toArray();
    }

    public function updatedDistrictId()
    {
        $this->villageId = null;
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        if ($user->roles->contains('name', 'super_admin')) {
            $villageQuery = Village::query();
        } else {
            $assignedVillageIds = Assignment::where('user_id', $user->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            $villageQuery = Village::query()->whereIn('id', $assignedVillageIds);
        }

        return $table
            ->query($villageQuery)
            ->columns([
                TextColumn::make('name')
                    ->label('Village')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('completed_uploads')
                    ->label('Completed')
                    ->getStateUsing(function ($record) {
                        return AssignmentUpload::whereHas('assignment', function ($q) use ($record) {
                            $q->where('area_type', 'App\\Models\\Village')
                              ->where('area_id', $record->id);
                        })->where('import_status', 'completed')->count();
                    }),
                TextColumn::make('processing_uploads')
                    ->label('Processing')
                    ->getStateUsing(function ($record) {
                        return AssignmentUpload::whereHas('assignment', function ($q) use ($record) {
                            $q->where('area_type', 'App\\Models\\Village')
                              ->where('area_id', $record->id);
                        })->where('import_status', 'processing')->count();
                    }),
                TextColumn::make('failed_uploads')
                    ->label('Failed')
                    ->getStateUsing(function ($record) {
                        return AssignmentUpload::whereHas('assignment', function ($q) use ($record) {
                            $q->where('area_type', 'App\\Models\\Village')
                              ->where('area_id', $record->id);
                        })->where('import_status', 'failed')->count();
                    }),
                TextColumn::make('total_uploads')
                    ->label('Total Uploads')
                    ->getStateUsing(function ($record) {
                        return AssignmentUpload::whereHas('assignment', function ($q) use ($record) {
                            $q->where('area_type', 'App\\Models\\Village')
                              ->where('area_id', $record->id);
                        })->count();
                    }),
            ])
            ->defaultSort('name');
    }

    public function form(Form $form): Form
    {
        $user = Auth::user();
        $districtOptions = $this->getDistricts();

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
                            ->afterStateUpdated(function () {
                                $this->refreshWidgets();
                            }),
                    ]),
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
}
