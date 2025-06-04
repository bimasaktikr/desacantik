<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('Import Excel')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File (.xlsx)')
                        ->disk('public') // or 'local' if you prefer
                        ->directory('imports')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '.xlsx']),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/public/' . $data['file']);
                    Excel::import(new StudentsImport, $filePath);
                    Notification::make()
                        ->title('Import Success')
                        ->success()
                        ->body('Data siswa berhasil diimpor.')
                        ->send();
                })
                ->icon('heroicon-m-arrow-down-tray')
                ->modalHeading('Import Data Siswa dari Excel'),

        ];
    }
}
