<?php

namespace App\Filament\Resources\AssignmentUploadResource\Pages;

use App\Filament\Resources\AssignmentUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssignmentUploads extends ListRecords
{
    protected static string $resource = AssignmentUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
