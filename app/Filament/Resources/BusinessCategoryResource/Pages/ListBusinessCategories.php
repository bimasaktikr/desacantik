<?php

namespace App\Filament\Resources\BusinessCategoryResource\Pages;

use App\Filament\Imports\BusinessCategoryImporter;
use App\Filament\Resources\BusinessCategoryResource;
use App\Models\BusinessCategory;
use Filament\Actions;
use Filament\Actions\Action;
// use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ListBusinessCategories extends ListRecords
{
    protected static string $resource = BusinessCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


}
