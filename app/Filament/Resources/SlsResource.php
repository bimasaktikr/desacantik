<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlsResource\Pages;
use App\Filament\Resources\SlsResource\RelationManagers;
use App\Models\Sls;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Livewire as ComponentsLivewire;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class SlsResource extends Resource
{
    protected static ?string $model = Sls::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administrasi Wilayah';

    protected static ?string $navigationLabel = 'SLS';

    protected static ?int $navigationSort = 4;


    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\Sls::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('name')
                ->label('Nama SLS')
                ->searchable()
                ->sortable(),

            TextColumn::make('sls_code')
                ->label('Kode SLS')
                ->searchable()
                ->sortable(),

            TextColumn::make('village.name')
                ->label('Desa')
                ->sortable()
                ->searchable(),

            TextColumn::make('village.district.name')
                ->label('Kecamatan')
                ->sortable()
                ->searchable(),

            TextColumn::make('village.district.regency.name')
                ->label('Kota/Kab')
                ->sortable()
                ->searchable(),

            TextColumn::make('baseMap.period')
                ->label('Base Map')
                ->sortable()
                ->toggleable(),
        ])
        ->filters([
            SelectFilter::make('district_id')
                ->label('Kecamatan')
                ->options(\App\Models\District::pluck('name', 'id')->toArray())
                ->form([

                        Forms\Components\Select::make('district_id')
                            ->label('Kecamatan')
                            ->options(\App\Models\District::pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('village_id', null))
                            ->columnSpan(6),
                        Forms\Components\Select::make('village_id')
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
        // ->filtersFormColumns(3)
        // ->filtersFormWidth(MaxWidth::FourExtraLarge)
        ->actions([

        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSls::route('/'),
            'create' => Pages\CreateSls::route('/create'),
            'edit' => Pages\EditSls::route('/{record}/edit'),
        ];
    }
}
