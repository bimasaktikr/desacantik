<?php

namespace App\Filament\Resources\BasemapResource\Pages;

use App\Filament\Resources\BasemapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasemaps extends ListRecords
{
    protected static string $resource = BasemapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
