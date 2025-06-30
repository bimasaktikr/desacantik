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
use App\Models\User;
use Filament\Tables\Tab;

class AssignmentUploadResource extends Resource
{
    protected static ?string $model = AssignmentUpload::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Assignment Petugas';

    protected static ?string $slug = 'assignment-upload';

    protected static ?string $title = 'Daftar Assignments Upload';



    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && $user->roles->contains('name', 'super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('assignment.id')->label('Assignment')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('file_path')->label('File')->limit(30),
                Tables\Columns\TextColumn::make('import_status')->label('Import Status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('error_message')->label('Error')->limit(30),
                Tables\Columns\TextColumn::make('imported_at')->label('Imported At')->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('total_rows')->label('Total'),
                Tables\Columns\TextColumn::make('processed_rows')->label('Processed'),
                Tables\Columns\TextColumn::make('success_rows')->label('Success'),
                Tables\Columns\TextColumn::make('failed_rows')->label('Failed'),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                // You can add filters here if needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Assignment Upload Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->infolist([
                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('id')->label('ID'),
                                \Filament\Infolists\Components\TextEntry::make('assignment.id')->label('Assignment'),
                                \Filament\Infolists\Components\TextEntry::make('user.name')->label('User'),
                                \Filament\Infolists\Components\TextEntry::make('file_path')->label('File Path'),
                                \Filament\Infolists\Components\TextEntry::make('import_status')->label('Import Status'),
                                \Filament\Infolists\Components\TextEntry::make('imported_at')->label('Imported At')->dateTime('d M Y H:i'),
                                \Filament\Infolists\Components\TextEntry::make('created_at')->label('Created At')->dateTime('d M Y H:i'),
                            ]),
                        \Filament\Infolists\Components\Section::make('Import Details')
                            ->collapsible()
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('error_message')->label('Error Message'),
                                \Filament\Infolists\Components\TextEntry::make('total_rows')->label('Total Rows'),
                                \Filament\Infolists\Components\TextEntry::make('processed_rows')->label('Processed Rows'),
                                \Filament\Infolists\Components\TextEntry::make('success_rows')->label('Success Rows'),
                                \Filament\Infolists\Components\TextEntry::make('failed_rows')->label('Failed Rows'),
                            ]),
                    ]),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->file_path ? (\Illuminate\Support\Str::startsWith($record->file_path, ['http://', 'https://']) ? $record->file_path : \Illuminate\Support\Facades\Storage::url($record->file_path)) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->file_path)),
                // Tables\Actions\EditAction::make(), // Disabled edit action
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(null);
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
