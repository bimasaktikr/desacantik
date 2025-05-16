<?php

namespace App\Filament\Resources\KecamatanResource\Pages;

use App\Filament\Resources\KecamatanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKecamatan extends CreateRecord
{
    protected static string $resource = KecamatanResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
