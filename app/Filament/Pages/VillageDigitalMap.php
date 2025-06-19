<?php

namespace App\Filament\Pages;

use App\Models\Village;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VillageDigitalMap extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Peta Digital Desa';
    protected static ?string $navigationGroup = 'Peta Digital';
    protected static ?string $title = 'Peta Digital Desa';
    protected static ?string $slug = 'village-digital-map';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.village-digital-map';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Village::query()
                    ->when(Auth::user()->roles->contains('name', 'Employee'), function (Builder $query) {
                        $query->whereIn('id', Auth::user()->employee->villages->pluck('id'));
                    })
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Desa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('geojson_path')
                    ->label('Status Peta')
                    ->formatStateUsing(fn ($state) => $state ? 'Tersedia' : 'Belum Tersedia')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->actions([
                Action::make('viewMap')
                    ->label('Lihat Peta')
                    ->icon('heroicon-o-map')
                    ->visible(fn (Village $record) => $record->geojson_path)
                    ->action(function (Village $record) {
                        Log::info('Dispatching showMap event for village: ' . $record->id);
                        $this->dispatch('showMap', villageId: $record->id)->to('show-village-map');
                    }),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Village $record) => $record->geojson_path)
                    ->action(function (Village $record) {
                        return response()->download(Storage::disk('public')->path($record->geojson_path));
                    }),
            ])
            ->defaultSort('name');
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        return [
            'villagesWithoutMap' => Village::when(Auth::user()->roles->contains('name', 'Employee'), function ($query) {
                $query->whereIn('id', Auth::user()->employee->villages->pluck('id'));
            })->whereNull('geojson_path')->count(),
        ];
    }

    public function getView(): string
    {
        return static::$view;
    }

    public function splitBasemap()
    {
        // Misal: panggil service untuk membagi geojson ke masing-masing desa
        // atau buat dummy logic dulu

        // Contoh:
        $villages = Village::whereNull('geojson_path')->get();
        foreach ($villages as $village) {
            $village->update([
                'geojson_path' => 'dummy/path/' . $village->id . '.geojson',
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Berhasil membagi basemap ke desa-desa.',
        ]);
    }
}
