<?php

namespace App\Filament\Resources\BusinessResource\Pages;

use App\Filament\Resources\BusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Widgets\BusinessActivityLogWidget;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    public static function getWidgets(): array
    {
        return [
            BusinessActivityLogWidget::class,
        ];
    }

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
