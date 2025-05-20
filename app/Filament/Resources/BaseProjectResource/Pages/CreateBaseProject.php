<?php

namespace App\Filament\Resources\BaseProjectResource\Pages;

use App\Filament\Resources\BaseProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBaseProject extends CreateRecord
{
    protected static string $resource = BaseProjectResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
