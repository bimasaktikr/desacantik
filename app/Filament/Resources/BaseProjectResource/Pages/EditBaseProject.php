<?php

namespace App\Filament\Resources\BaseProjectResource\Pages;

use App\Filament\Resources\BaseProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBaseProject extends EditRecord
{
    protected static string $resource = BaseProjectResource::class;

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
