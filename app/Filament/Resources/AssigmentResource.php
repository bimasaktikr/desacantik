<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssigmentResource\Pages;
use App\Filament\Resources\AssigmentResource\RelationManagers;
use App\Models\Assigment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssigmentResource extends Resource
{
    protected static ?string $model = Assigment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Pendataan';
    protected static ?string $navigationLabel = 'Assigment';
    protected static ?string $title = 'Penugasan';
    protected static ?string $slug = 'assigments';
    protected static ?int $navigationSort = 2;


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
            'index' => Pages\ListAssigments::route('/'),
            'create' => Pages\CreateAssigment::route('/create'),
            'edit' => Pages\EditAssigment::route('/{record}/edit'),
        ];
    }
}
