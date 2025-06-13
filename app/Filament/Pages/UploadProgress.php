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

class UploadProgress extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?string $title = 'Progress Upload Data';
    protected static ?string $navigationLabel = 'Progress Upload';
    protected static ?string $slug = 'upload-progress';

    protected static string $view = 'filament.pages.upload-progress';

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            BusinessChart::class,
        ];
    }

    public function table(Table $table): Table
    {
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

        // Get assigned areas based on user role
        if ($user->roles->contains('name', 'Mahasiswa')) {
            // For students, show only their assigned areas
            $assignedAreaIds = Assignment::where('user_id', $user->id)
                ->pluck('area_id')
                ->toArray();

            $query->whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            });
        } elseif ($user->roles->contains('name', 'Petugas')) {
            // For employees, show all areas assigned to students
            $assignedAreaIds = Assignment::whereHas('user', function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', 'Mahasiswa');
                });
            })->pluck('area_id')->toArray();

            $query->whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            });
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
