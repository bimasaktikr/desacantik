<?php

namespace App\Filament\Resources\VilageResource\Pages;

use App\Filament\Resources\VilageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVilage extends EditRecord
{
    protected static string $resource = VilageResource::class;

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
