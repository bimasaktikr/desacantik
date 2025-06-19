<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImportLog extends EditRecord
{
    protected static string $resource = ImportLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
