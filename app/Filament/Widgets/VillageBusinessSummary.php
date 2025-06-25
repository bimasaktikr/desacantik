<?php

namespace App\Filament\Widgets;

use App\Models\Village;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Assignment;

class VillageBusinessSummary extends BaseWidget
{
    protected static ?string $heading = 'Village Business Summary';
    protected int | string | array $columnSpan = 'full';

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        $user = Auth::user();

        // If super_admin, show all villages. Otherwise, filter by assignment.
        if ($user->roles->contains('name', 'super_admin')) {
            $villageQuery = Village::query()->with(['sls.businesses']);
        } else {
            $assignedVillageIds = Assignment::where('user_id', $user->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            $villageQuery = Village::query()
                ->whereIn('id', $assignedVillageIds)
                ->with(['sls.businesses']);
        }

        return $table
            ->query($villageQuery)
            ->columns([
                TextColumn::make('name')
                    ->label('Village')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sls_with_business_count')
                    ->label('SLS with Business')
                    ->getStateUsing(function ($record) {
                        return $record->sls->filter(fn($sls) => $sls->businesses->count() > 0)->count();
                    })
                    ->sortable(),
                TextColumn::make('businesses_count')
                    ->label('Total Businesses')
                    ->getStateUsing(function ($record) {
                        return $record->sls->flatMap->businesses->unique('id')->count();
                    }),
            ])
            ->defaultSort('name');
    }
}
