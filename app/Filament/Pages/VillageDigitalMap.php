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


class VillageDigitalMap extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.village-digital-map';

    protected static ?string $navigationGroup = 'Peta Digital';

    protected static ?string $navigationLabel = 'Peta Digital Desa';
    protected static ?string $title = 'Peta Digital Desa';
    protected static ?string $slug = 'village-digital-map';
    protected static ?int $navigationSort = 2;


    public function table(Table $table): Table
    {
        return $table
            ->query(Village::query())
            ->columns([
                TextColumn::make('code')
                    ->label('ID Desa/Kelurahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Desa/Kelurahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('district.name')
                    ->label('Nama Kecamatan')
                    ->searchable()
                    ->sortable()

            ])
            ->filters([
                SelectFilter::make('district_id')
                    ->label('Kecamatan')
                    ->options(\App\Models\District::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->where('district_id', $data['value']);
                        }
                    }),

            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        return response()->download(Storage::disk('public')->path($record->geojson_path));
                    }),
                Action::make('Lihat Peta')
                    ->action(fn ($record, $livewire) => $livewire->dispatch('showMap', $record->id)),
            ]);

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
