<?php

namespace App\Filament\Resources\SlsResource\Pages;

use App\Filament\Resources\SlsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSls extends ListRecords
{
    protected static string $resource = SlsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
