<?php

namespace App\Filament\Resources\BaseProjectResource\Pages;

use App\Filament\Resources\BaseProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBaseProjects extends ListRecords
{
    protected static string $resource = BaseProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
