<?php

namespace App\Filament\Resources\AssignmentUploadResource\Pages;

use App\Filament\Resources\AssignmentUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentUpload extends EditRecord
{
    protected static string $resource = AssignmentUploadResource::class;

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
