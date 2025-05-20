<?php
namespace App\Filament\Pages;

use App\Livewire\ShowGeojsonMap;
use App\Models\Sls;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class SLSDigitalMap extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static string $view = 'filament.pages.sls-digital-map';
    protected static ?string $title = 'SLS Digital Map';
    protected static ?string $navigationGroup = 'Peta Digital';
    protected static ?string $navigationLabel = 'Peta Digital SLS';
    protected static ?string $slug = 'sls-digital-map';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(SLS::query())
            ->columns([
                TextColumn::make('code')
                    ->label('ID SLS')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama SLS')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('village.name')
                    ->label('Desa/Kelurahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('village.district.name')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('district_id')
                    ->label('Kecamatan')
                    ->options(\App\Models\District::pluck('name', 'id')->toArray())
                    ->form([

                            Select::make('district_id')
                                ->label('Kecamatan')
                                ->options(\App\Models\District::pluck('name', 'id'))
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set) => $set('village_id', null))
                                ->columnSpan(6),
                            Select::make('village_id')
                                ->label('Desa')
                                ->options(function (callable $get) {
                                    $districtId = $get('district_id');
                                    return $districtId
                                        ? \App\Models\Village::where('district_id', $districtId)->pluck('name', 'id')
                                        : [];
                                })
                                ->reactive()
                                ->columnSpan(6),

                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['district_id'])) {
                            $query->whereHas('village.district', fn ($q) => $q->where('id', $data['district_id']));
                        }
                        if (!empty($data['village_id'])) {
                            $query->where('village_id', $data['village_id']);
                        }
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->actions([
                // Action::make('Lihat Peta')
                //     ->label('Lihat Peta')
                //     ->button()
                //     ->color('primary')
                //     ->icon('heroicon-o-eye')
                //     ->action(function (SLS $record) {
                //         $geojsonUrl = Storage::disk('public')->url($record->geojson_path);
                //         // dd($geojsonUrl);
                //         $this->dispatch('openMapModal', $geojsonUrl);
                //     }),
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

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         ShowGeojsonMap::class,
    //     ];
    // }
}
