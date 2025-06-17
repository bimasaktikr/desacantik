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

class UploadProgress extends Page implements HasTable
{
    use InteractsWithTable;

    public ?string $mode = 'uploads'; // Default mode for the chart
    public string $activeTab = 'summary'; // Default active tab: 'summary' or 'chart'

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?string $title = 'Progress Upload Data';
    protected static ?string $navigationLabel = 'Progress Upload';
    protected static ?string $slug = 'upload-progress';

    protected static string $view = 'filament.pages.upload-progress';

    #[On('tab-changed')]
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->roles->contains('name', 'supervisor') || $user?->roles->contains('name', 'Employee') ?? false;
    }

    public function mount(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user?->roles->contains('name', 'supervisor') && !$user?->roles->contains('name', 'Employee')) {
            redirect()->route('filament.admin.pages.dashboard');
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            // BusinessChart::class,
        ];
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        if ($user->roles->contains('name', 'Employee')) {
            // Get all village IDs assigned to the employee
            $employeeAreaIds = Assignment::where('user_id', $user->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            Log::info('Employee village assignments', ['employee_id' => $user->id, 'village_ids' => $employeeAreaIds]);

            // Find all Mahasiswa who have assignments in those same areas
            $mahasiswaUserIds = Assignment::whereIn('area_id', $employeeAreaIds)
                ->where('area_type', 'App\\Models\\Village')
                ->whereHas('user.roles', function ($q) {
                    $q->where('name', 'Mahasiswa');
                })
                ->pluck('user_id')
                ->unique()
                ->toArray();

            // Query Mahasiswa users
            $mahasiswaQuery = User::whereIn('id', $mahasiswaUserIds);

            return $table
                ->query($mahasiswaQuery)
                ->columns([
                    TextColumn::make('name')
                        ->label('Mahasiswa')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('uploads_count')
                        ->label('Total Uploads')
                        ->getStateUsing(function (User $record) use ($employeeAreaIds) {
                            $mahasiswaVillageIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
                            Log::info('Shared village assignments', ['employee_id' => Auth::id(), 'mahasiswa_id' => $record->id, 'shared_village_ids' => $sharedVillageIds]);
                            $assignmentIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->whereIn('area_id', $sharedVillageIds)
                                ->pluck('id');
                            return AssignmentUpload::whereIn('assignment_id', $assignmentIds)->count();
                        }),
                    TextColumn::make('success_uploads_count')
                        ->label('Upload Berhasil')
                        ->getStateUsing(function (User $record) use ($employeeAreaIds) {
                            $mahasiswaVillageIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
                            $assignmentIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->whereIn('area_id', $sharedVillageIds)
                                ->pluck('id');
                            return AssignmentUpload::whereIn('assignment_id', $assignmentIds)
                                ->where('import_status', 'berhasil')->count();
                        }),
                    TextColumn::make('failed_uploads_count')
                        ->label('Upload Gagal')
                        ->getStateUsing(function (User $record) use ($employeeAreaIds) {
                            $mahasiswaVillageIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
                            $assignmentIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->whereIn('area_id', $sharedVillageIds)
                                ->pluck('id');
                            return AssignmentUpload::whereIn('assignment_id', $assignmentIds)
                                ->where('import_status', 'gagal')->count();
                        }),
                    TextColumn::make('businesses_count')
                        ->label('Total Data Usaha')
                        ->getStateUsing(function (User $record) use ($employeeAreaIds) {
                            $mahasiswaVillageIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
                            return Business::where('user_id', $record->id)
                                ->whereIn('village_id', $sharedVillageIds)
                                ->count();
                        }),
                    TextColumn::make('last_upload')
                        ->label('Last Upload')
                        ->getStateUsing(function (User $record) use ($employeeAreaIds) {
                            $mahasiswaVillageIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
                            $assignmentIds = Assignment::where('user_id', $record->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->whereIn('area_id', $sharedVillageIds)
                                ->pluck('id');
                            $last = AssignmentUpload::whereIn('assignment_id', $assignmentIds)
                                ->latest('created_at')
                                ->first();
                            return $last ? $last->created_at->format('d M Y H:i') : '-';
                        }),
                ])
                ->actions([
                    Action::make('view_uploads')
                        ->label('Lihat Uploads')
                        ->url(fn (User $record) => route('filament.admin.pages.upload-progress', ['mahasiswa' => $record->id]))
                        ->openUrlInNewTab(),
                ]);
        }
        // ... fallback to original table for other roles ...
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignment.area.name')
                    ->label('Wilayah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('import_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'berhasil' => 'success',
                        'gagal' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_rows')
                    ->label('Total Data')
                    ->numeric(),
                TextColumn::make('success_rows')
                    ->label('Berhasil')
                    ->numeric(),
                TextColumn::make('failed_rows')
                    ->label('Gagal')
                    ->numeric(),
                TextColumn::make('imported_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('import_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'berhasil' => 'Berhasil',
                        'gagal' => 'Gagal',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $query = AssignmentUpload::query()
            ->with(['user', 'assignment.area'])
            ->latest();

        $user = Auth::user();

        // If user is supervisor, show all uploads
        if ($user->roles->contains('name', 'supervisor')) {
            return $query;
        }

        // If user is employee, show only uploads from their assigned areas
        if ($user->roles->contains('name', 'employee')) {
            $assignedAreaIds = Assignment::where('user_id', $user->id)
                ->pluck('area_id')
                ->toArray();

            $query->whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            });
        }

        return $query;
    }
}
