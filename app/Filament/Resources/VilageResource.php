<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VilageResource\Pages;
use App\Filament\Resources\VilageResource\RelationManagers;
use App\Models\Vilage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VilageResource extends Resource
{
    protected static ?string $model = Vilage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administrasi Wilayah';

    protected static ?string $navigationLabel = 'Desa/Kelurahan';
    protected static ?int $navigationSort = 3;

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
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
   Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListVilages::route('/'),
            'create' => Pages\CreateVilage::route('/create'),
            'edit' => Pages\EditVilage::route('/{record}/edit'),
        ];
    }
}
