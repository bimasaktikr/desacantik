<?php

namespace App\Filament\Resources\AssignmentUploadResource\Pages;

use App\Filament\Resources\AssignmentUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListAssignmentUploads extends ListRecords
{
    protected static string $resource = AssignmentUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'success' => Tab::make('Success')
                ->modifyQueryUsing(fn ($query) => $query->where('import_status', 'berhasil')),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn ($query) => $query->where('import_status', 'gagal')),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('import_status', 'pending')),
        ];
    }
}
