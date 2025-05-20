<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasemapResource\Pages;
use App\Filament\Resources\BasemapResource\RelationManagers;
use App\Models\Basemap;
use App\Services\BaseMapImporter;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class BasemapResource extends Resource
{
    protected static ?string $model = Basemap::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Peta Digital';

    protected static ?string $navigationLabel = 'Peta Dasar';

    protected static ?string $title = 'Peta Dasar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            TextInput::make('name')->required(),
            TextInput::make('period')->label('Periode (e.g. 2024_1)'),
            TextInput::make('regency_name')->label('Kabupaten/Kota'),
            TextInput::make('source')->default('BPS'),
            FileUpload::make('file_path')
                ->label('GeoJSON File')
                ->disk('public')
                ->directory('geojsons')
                ->acceptedFileTypes(['.geojson', 'application/geo+json'])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('period')->label('Periode'),
                TextColumn::make('regency_name')->label('Kabupaten/Kota'),
                TextColumn::make('source')->label('Sumber'),
                TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('file_path')
                    ->label('File GeoJSON')
                    ->url(fn (Basemap $record): string => $record->file_path)
                    ->openUrlInNewTab()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),




            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),

                ActionsAction::make('splitToSls')
                    ->label('Split to SLS')
                    ->icon('heroicon-m-scissors')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // dd($record->file_path);
                        if (!$record->file_path || !Storage::disk('public')->exists($record->file_path)) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('GeoJSON file not found.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Panggil service
                        BaseMapImporter::importsls($record->file_path, $record->id);

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Basemap split into SLS successfully!')
                            ->success()
                            ->send();
                    }),
                ActionsAction::make('splitToVillage')
                    ->label('Split to Village')
                    ->icon('heroicon-m-scissors')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // dd($record->file_path);
                        if (!$record->file_path || !Storage::disk('public')->exists($record->file_path)) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('GeoJSON file not found.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Panggil service
                        BaseMapImporter::importvillage($record->file_path, $record->id);

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Basemap split into SLS successfully!')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListBasemaps::route('/'),
            'create' => Pages\CreateBasemap::route('/create'),
            'edit' => Pages\EditBasemap::route('/{record}/edit'),
        ];
    }
}
