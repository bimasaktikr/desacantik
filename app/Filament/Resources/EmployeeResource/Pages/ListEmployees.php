<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Imports\EmployeeImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('Import Excel')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File (.xlsx)')
                        ->disk('public')
                        ->directory('imports')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '.xlsx']),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/public/' . $data['file']);
                    Excel::import(new EmployeeImport, $filePath);
                    Notification::make()
                        ->title('Import Success')
                        ->success()
                        ->body('Data karyawan berhasil diimpor.')
                        ->send();
                })
                ->icon('heroicon-m-arrow-down-tray')
                ->modalHeading('Import Data Karyawan dari Excel'),
        ];
    }
}
