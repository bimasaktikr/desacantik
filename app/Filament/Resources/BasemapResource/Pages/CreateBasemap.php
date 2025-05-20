<?php

namespace App\Filament\Resources\BasemapResource\Pages;

use App\Filament\Resources\BasemapResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateBasemap extends CreateRecord
{
    protected static string $resource = BasemapResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id(); // Optional
        return $data;
    }

    // protected function afterCreate(): void
    // {
    //     $path = $this->record->file_path;
    //     $absolutePath = Storage::disk('public')->path($path);

    //     SlsGeojsonImporter::import($absolutePath, $path, $this->record);
    // }
}
