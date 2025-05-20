<?php

namespace App\Filament\Resources;

use App\Filament\Imports\BusinessCategoryImporter;
use App\Filament\Resources\BusinessCategoryResource\Pages;
use App\Filament\Resources\BusinessCategoryResource\RelationManagers;
use App\Models\BusinessCategory;
use Filament\Actions\Action as ActionsAction;
// use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Contracts\HasHeaderActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessCategoryResource extends Resource
{
    // use InteractsWithHeaderActions;

    protected static ?string $model = BusinessCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Master';
    protected static ?string $navigationLabel = 'Kategori Usaha';
    protected static ?string $label = 'Kategori Usaha';
    protected static ?string $pluralLabel = 'Kategori Usaha';
    protected static ?string $slug = 'business-categories';




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
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                ImportAction::make('import-categories')
                    ->importer(BusinessCategoryImporter::class)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'asc')
            ;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessCategories::route('/'),
            'create' => Pages\CreateBusinessCategory::route('/create'),
            'edit' => Pages\EditBusinessCategory::route('/{record}/edit'),
        ];
    }
}
