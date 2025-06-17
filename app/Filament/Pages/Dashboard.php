<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\BusinessChart;
use App\Filament\Widgets\BusinessCumulativeChart;
use Illuminate\Support\Facades\Auth;
use App\Models\Assignment;
use App\Models\Village;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?int $navigationSort = -2;

    public ?string $villageId = null;
    public ?string $userId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user->roles->contains('name', 'super_admin')) {
            $assignedAreas = Assignment::where('user_id', $user->id)->get();

            // Prioritize district assignment if available
            $assignedDistrict = $assignedAreas->firstWhere('area_type', 'App\\Models\\District');
            if ($assignedDistrict) {
                // If district is assigned, get first village in that district
                $village = Village::where('district_id', $assignedDistrict->area_id)->first();
                if ($village) {
                    $this->villageId = (string) $village->id;
                }
            } else {
                // If no district assignment, check for village assignment
                $assignedVillage = $assignedAreas->firstWhere('area_type', 'App\\Models\\Village');
                if ($assignedVillage) {
                    $this->villageId = (string) $assignedVillage->area_id;
                }
            }
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            // BusinessChart::class,
            // BusinessCumulativeChart::class,
        ];
    }

    protected function getWidgetsData(): array
    {
        return [
            'villageId' => $this->villageId,
            'userId' => $this->userId,
        ];
    }
}
