<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use App\Models\AssignmentUpload;
use App\Models\Business;
use App\Models\User;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Village;

class MahasiswaCumulativeChart extends LineChartWidget
{
    public ?string $districtId = null;
    public ?string $villageId = null;

    protected static ?string $heading = 'Progress Kumulatif Mahasiswa';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'districtId' => $this->districtId,
            'villageId' => $this->villageId,
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        if (!$user->roles->contains('name', 'Employee')) {
            return [];
        }

        // Get all village IDs assigned to the employee
        $employeeAreaIds = Assignment::where('user_id', $user->id)
            ->where('area_type', 'App\\Models\\Village')
            ->pluck('area_id')
            ->toArray();

        // Apply district/village filters if set
        if ($this->districtId) {
            $employeeAreaIds = Village::whereIn('id', $employeeAreaIds)
                ->where('district_id', $this->districtId)
                ->pluck('id')
                ->toArray();
        }
        if ($this->villageId) {
            $employeeAreaIds = array_intersect($employeeAreaIds, [$this->villageId]);
        }

        // Find all Mahasiswa who have assignments in those same areas
        $mahasiswaUserIds = Assignment::whereIn('area_id', $employeeAreaIds)
            ->where('area_type', 'App\\Models\\Village')
            ->whereHas('user.roles', function ($q) {
                $q->where('name', 'Mahasiswa');
            })
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Get Mahasiswa users
        $mahasiswaList = User::whereIn('id', $mahasiswaUserIds)->get();

        // Generate dates for the last 30 days
        $endDate = now();
        $startDate = $endDate->copy()->subDays(30);
        $dates = collect();
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        // Define a set of consistent colors
        $colors = [
            '#3b82f6', // blue-500
            '#10b981', // emerald-500
            '#f59e0b', // amber-500
            '#ef4444', // red-500
            '#8b5cf6', // violet-500
            '#ec4899', // pink-500
            '#06b6d4', // cyan-500
            '#f97316', // orange-500
        ];

        $datasets = [];
        foreach ($mahasiswaList as $index => $mahasiswa) {
            $mahasiswaVillageIds = Assignment::where('user_id', $mahasiswa->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
            if (empty($sharedVillageIds)) continue;

            // Get daily data for the selected date range
            $dailyData = Business::where('user_id', $mahasiswa->id)
                ->whereIn('village_id', $sharedVillageIds)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray();

            // Calculate cumulative totals
            $cumulativeData = [];
            $runningTotal = 0;
            foreach ($dates as $date) {
                if (isset($dailyData[$date])) {
                    $runningTotal += $dailyData[$date];
                }
                $cumulativeData[] = $runningTotal;
            }

            $datasets[] = [
                'label' => $mahasiswa->name,
                'data' => $cumulativeData,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)],
                'tension' => 0.1,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $dates->map(fn ($date) => Carbon::parse($date)->format('d M'))->toArray(),
        ];
    }
}
