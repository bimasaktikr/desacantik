<?php

namespace App\Filament\Resources\KopindagBusinessResource\Pages;

use App\Filament\Resources\KopindagBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KopindagBusinessImport;
use App\Jobs\KopindagBusinessImportJob;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ListKopindagBusinesses extends ListRecords
{
    protected static string $resource = KopindagBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Import Excel')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('excel_file')
                        ->label('File Excel')
                        ->disk('public')
                        ->directory('kopindag-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            '.xlsx', '.xls', '.csv'
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Kirim ke queue
                    KopindagBusinessImportJob::dispatch($data['excel_file'], Auth::id());
                    Log::info($data['excel_file']);

                    Notification::make()
                        ->title('Berhasil Dikirim')
                        ->body('File Excel berhasil diunggah dan sedang diproses.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
