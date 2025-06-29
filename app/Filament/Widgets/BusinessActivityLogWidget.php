<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;
use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class BusinessActivityLogWidget extends BaseWidget
{
    public ?Business $record = null;

    protected function getTableQuery(): Builder|Relation|null
    {
        return Activity::query()
            ->where('subject_type', Business::class)
            ->where('subject_id', $this->record->id)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('created_at')->label('Date')->dateTime('Y-m-d H:i:s'),
            TextColumn::make('causer.name')->label('User'),
            TextColumn::make('description')->label('Event'),
            TextColumn::make('properties')->label('Changes')->formatStateUsing(function ($state) {
                if (isset($state['attributes'])) {
                    return '<pre style="white-space: pre-wrap;">' . e(json_encode($state['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                }
                return '-';
            })->html(),
        ];
    }
}
