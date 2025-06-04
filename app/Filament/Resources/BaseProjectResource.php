<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseProjectResource\Pages;
use App\Filament\Resources\BaseProjectResource\RelationManagers;
use App\Models\BaseProject;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;

class BaseProjectResource extends Resource
{
    protected static ?string $model = BaseProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Pendataan';

    protected static ?string $navigationLabel = 'Download Project';

    protected static ?string $title = 'Download Project';

    protected static ?string $slug = 'base-projects';

    protected static ?int $navigationSort = 2;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->label('Nama Project')
                    ->placeholder('Nama Project')
                    ->maxLength(255)
                    ->reactive(),
                FileUpload::make('file_path')
                    ->label('File Project')
                    ->disk('public')
                    ->directory('projects')
                    ->required()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get) {
                        $name = Str::slug($get('name') ?? 'project');
                        $date = now()->format('Ymd');
                        $extension = $file->getClientOriginalExtension();

                        return "{$name}_{$date}.{$extension}";
                    })
                    ->placeholder('Upload File Project'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Project')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn ($record) => auth()->user()->can('update', $record)
                    ),
                Tables\Actions\DeleteAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        return response()->download(Storage::disk('public')->path($record->file_path));
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
            'index' => Pages\ListBaseProjects::route('/'),
            'create' => Pages\CreateBaseProject::route('/create'),
            'edit' => Pages\EditBaseProject::route('/{record}/edit'),
        ];
    }
}
