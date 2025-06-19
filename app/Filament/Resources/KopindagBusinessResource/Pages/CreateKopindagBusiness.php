<?php

namespace App\Filament\Resources\KopindagBusinessResource\Pages;

use App\Filament\Resources\KopindagBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKopindagBusiness extends CreateRecord
{
    protected static string $resource = KopindagBusinessResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
