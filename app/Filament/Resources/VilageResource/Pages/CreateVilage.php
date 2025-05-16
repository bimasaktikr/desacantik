<?php

namespace App\Filament\Resources\VilageResource\Pages;

use App\Filament\Resources\VilageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVilage extends CreateRecord
{
    protected static string $resource = VilageResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
