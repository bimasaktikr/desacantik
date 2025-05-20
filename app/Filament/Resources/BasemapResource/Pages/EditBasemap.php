<?php

namespace App\Filament\Resources\BasemapResource\Pages;

use App\Filament\Resources\BasemapResource;
use App\Services\BaseMapImporter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditBasemap extends EditRecord
{
    protected static string $resource = BasemapResource::class;

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
