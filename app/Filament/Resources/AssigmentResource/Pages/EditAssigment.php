<?php

namespace App\Filament\Resources\AssigmentResource\Pages;

use App\Filament\Resources\AssigmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssigment extends EditRecord
{
    protected static string $resource = AssigmentResource::class;

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
