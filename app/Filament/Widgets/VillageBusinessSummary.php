<?php

namespace App\Filament\Widgets;

use App\Models\Village;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class VillageBusinessSummary extends BaseWidget
{
    protected static ?string $heading = 'Village Business Summary';
    protected int | string | array $columnSpan = 'full';

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->query(
                Village::query()->with(['sls.businesses'])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Village')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sls_with_business_count')
                    ->label('SLS with Business')
                    ->getStateUsing(function ($record) {
                        // $record is a Village
                        return $record->sls->filter(fn($sls) => $sls->businesses->count() > 0)->count();
                    })
                    ->sortable(),
                TextColumn::make('businesses')
                    ->label('Businesses')
                    ->getStateUsing(function ($record) {
                        // $record is a Village
                        $businesses = $record->sls->flatMap->businesses->unique('id');
                        return $businesses->pluck('name')->join(', ');
                    })
                    ->searchable(),
            ])
            ->defaultSort('name');
    }
}
