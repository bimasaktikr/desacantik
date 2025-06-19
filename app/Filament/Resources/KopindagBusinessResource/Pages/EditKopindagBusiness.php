<?php

namespace App\Filament\Resources\KopindagBusinessResource\Pages;

use App\Filament\Resources\KopindagBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKopindagBusiness extends EditRecord
{
    protected static string $resource = KopindagBusinessResource::class;

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
