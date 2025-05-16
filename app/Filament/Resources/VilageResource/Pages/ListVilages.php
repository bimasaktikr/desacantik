<?php

namespace App\Filament\Resources\VilageResource\Pages;

use App\Filament\Resources\VilageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVilages extends ListRecords
{
    protected static string $resource = VilageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
