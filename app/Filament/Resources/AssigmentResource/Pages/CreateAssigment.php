<?php

namespace App\Filament\Resources\AssigmentResource\Pages;

use App\Filament\Resources\AssigmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssigment extends CreateRecord
{
    protected static string $resource = AssigmentResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
