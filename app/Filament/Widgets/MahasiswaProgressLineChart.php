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

class MahasiswaProgressLineChart extends LineChartWidget
{
    public ?string $mode = 'uploads'; // 'uploads' or 'businesses'
    protected static ?string $heading = 'Progress Mahasiswa (Time Series)';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return $this->mode === 'uploads' ? 'Progress Upload Mahasiswa' : 'Progress Data Usaha Mahasiswa';
    }

    protected function getViewData(): array
    {
        return [
            'mode' => $this->mode,
        ];
    }

    public function toggleMode($mode)
    {
        $this->mode = $mode;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        if (!$user->roles->contains('name', 'Employee')) {
            return [ 'datasets' => [], 'labels' => [] ];
        }

        // Get all village IDs assigned to the employee
        $employeeAreaIds = Assignment::where('user_id', $user->id)
            ->where('area_type', 'App\\Models\\Village')
            ->pluck('area_id')
            ->toArray();

        // Find all Mahasiswa who have assignments in those same areas
        $mahasiswaUserIds = Assignment::whereIn('area_id', $employeeAreaIds)
            ->where('area_type', 'App\\Models\\Village')
            ->whereHas('user.roles', function ($q) {
                $q->where('name', 'Mahasiswa');
            })
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $mahasiswaList = User::whereIn('id', $mahasiswaUserIds)->get();

        // Date range (last 14 days)
        $startDate = Carbon::now()->subDays(13)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $dates = collect();
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates->push($current->format('Y-m-d'));
            $current->addDay();
        }

        $datasets = [];
        $colors = [
            '#6366F1', '#F59E42', '#10B981', '#EF4444', '#F472B6', '#FBBF24', '#3B82F6', '#8B5CF6', '#EC4899', '#22D3EE',
            '#A3E635', '#F87171', '#FACC15', '#4ADE80', '#818CF8', '#F472B6', '#FCD34D', '#60A5FA', '#C084FC', '#F43F5E',
        ];
        $colorIndex = 0;

        foreach ($mahasiswaList as $mahasiswa) {
            $mahasiswaVillageIds = Assignment::where('user_id', $mahasiswa->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            $sharedVillageIds = array_intersect($employeeAreaIds, $mahasiswaVillageIds);
            if (empty($sharedVillageIds)) continue;

            $dataPerDate = array_fill_keys($dates->toArray(), 0);
            if ($this->mode === 'uploads') {
                $assignmentIds = Assignment::where('user_id', $mahasiswa->id)
                    ->where('area_type', 'App\\Models\\Village')
                    ->whereIn('area_id', $sharedVillageIds)
                    ->pluck('id');
                $uploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('date')
                    ->pluck('total', 'date');
                foreach ($uploads as $date => $count) {
                    $dataPerDate[$date] = $count;
                }
            } else {
                $businesses = Business::where('user_id', $mahasiswa->id)
                    ->whereIn('village_id', $sharedVillageIds)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('date')
                    ->pluck('total', 'date');
                foreach ($businesses as $date => $count) {
                    $dataPerDate[$date] = $count;
                }
            }
            $datasets[] = [
                'label' => $mahasiswa->name,
                'data' => array_values($dataPerDate),
                'borderColor' => $colors[$colorIndex % count($colors)],
                'backgroundColor' => $colors[$colorIndex % count($colors)],
                'tension' => 0.4,
            ];
            $colorIndex++;
        }

        return [
            'datasets' => $datasets,
            'labels' => $dates->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray(),
        ];
    }
}
