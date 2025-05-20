<?php

namespace App\Filament\Resources\AssigmentResource\Pages;

use App\Filament\Resources\AssigmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssigments extends ListRecords
{
    protected static string $resource = AssigmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
