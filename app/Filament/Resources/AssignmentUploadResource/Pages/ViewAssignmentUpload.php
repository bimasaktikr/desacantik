<?php

namespace App\Filament\Resources\AssignmentUploadResource\Pages;

use App\Filament\Resources\AssignmentUploadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Group;

class ViewAssignmentUpload extends ViewRecord
{
    protected static string $resource = AssignmentUploadResource::class;

    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->schema([
                Group::make([
                    TextEntry::make('id')->label('ID'),
                    TextEntry::make('assignment.id')->label('Assignment'),
                    TextEntry::make('user.name')->label('User'),
                    TextEntry::make('file_path')->label('File Path'),
                    TextEntry::make('import_status')->label('Import Status'),
                    TextEntry::make('error_message')->label('Error Message'),
                    TextEntry::make('imported_at')->label('Imported At')->dateTime('d M Y H:i'),
                    TextEntry::make('total_rows')->label('Total Rows'),
                    TextEntry::make('processed_rows')->label('Processed Rows'),
                    TextEntry::make('success_rows')->label('Success Rows'),
                    TextEntry::make('failed_rows')->label('Failed Rows'),
                    TextEntry::make('created_at')->label('Created At')->dateTime('d M Y H:i'),
                ]),
            ]);
    }
}
