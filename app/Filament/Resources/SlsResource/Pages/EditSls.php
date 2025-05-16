<?php

namespace App\Filament\Resources\SlsResource\Pages;

use App\Filament\Resources\SlsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSls extends EditRecord
{
    protected static string $resource = SlsResource::class;

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
