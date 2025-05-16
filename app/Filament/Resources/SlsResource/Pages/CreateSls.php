<?php

namespace App\Filament\Resources\SlsResource\Pages;

use App\Filament\Resources\SlsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSls extends CreateRecord
{
    protected static string $resource = SlsResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
