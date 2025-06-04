<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentUploadResource\Pages;
use App\Filament\Resources\AssignmentUploadResource\RelationManagers;
use App\Models\AssignmentUpload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssignmentUploadResource extends Resource
{
    protected static ?string $model = AssignmentUpload::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            'index' => Pages\ListAssignmentUploads::route('/'),
            'create' => Pages\CreateAssignmentUpload::route('/create'),
            'edit' => Pages\EditAssignmentUpload::route('/{record}/edit'),
        ];
    }
}
