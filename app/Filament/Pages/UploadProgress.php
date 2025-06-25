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
        $query = AssignmentUpload::query();

        if (!$user->roles->contains('name', 'super_admin')) {
            // Get assigned village IDs for the user
            $assignedVillageIds = Assignment::where('user_id', $user->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            // Only show uploads where the assignment's area is a village assigned to the user
            $query->whereHas('assignment', function ($q) use ($assignedVillageIds) {
                $q->where('area_type', 'App\\Models\\Village')
                  ->whereIn('area_id', $assignedVillageIds);
            });
        }

        return $table
            ->query(
                $query
                    ->when($this->districtId, function ($query) {
                        $query->whereHas('assignment', function ($q) {
                            $q->whereHasMorph('area', [Village::class], function ($q) {
                                $q->where('district_id', $this->districtId);
                            });
                        });
                    })
                    ->when($this->villageId, function ($query) {
                        $query->whereHas('assignment', function ($q) {
                            $q->whereHasMorph('area', [Village::class], function ($q) {
                                $q->where('id', $this->villageId);
                            });
                        });
                    })
            )
            ->columns([
                TextColumn::make('assignment.area.name')
                    ->label('Desa')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('assignment.area.district.name')
                    ->label('Kecamatan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_rows')
                    ->label('Total Data')
                    ->sortable(),
                TextColumn::make('processed_rows')
                    ->label('Data Diproses')
                    ->sortable(),
                TextColumn::make('success_rows')
                    ->label('Data Berhasil')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('failed_rows')
                    ->label('Data Gagal')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('import_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('import_status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Selesai',
                        'processing' => 'Diproses',
                        'failed' => 'Gagal',
                    ]),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-download')
                    ->url(fn (AssignmentUpload $record): string => route('filament.admin.resources.assignment-uploads.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (AssignmentUpload $record): bool => $record->import_status === 'completed'),
            ]);
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
