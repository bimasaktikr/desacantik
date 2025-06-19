<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateImportLog extends CreateRecord
{
    protected static string $resource = ImportLogResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
