<?php

namespace App\Filament\Resources\AssignmentUploadResource\Pages;

use App\Filament\Resources\AssignmentUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignmentUpload extends CreateRecord
{
    protected static string $resource = AssignmentUploadResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
