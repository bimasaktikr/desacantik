<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\Assignment;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;

class BusinessCumulativeChart extends LineChartWidget
{
    protected static ?string $heading = 'Tren Kumulatif Data Usaha';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $villageId = null;
    public ?string $userId = null;

    public function mount(): void
    {
        $this->startDate = now()->subDays(14)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        // The villageId and userId will be set by the parent page or default to null
    }

    #[On('refreshChartData')]
    public function refreshData(string $villageId = null, string $userId = null): void
    {
        $this->villageId = $villageId;
        $this->userId = $userId;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $query = Business::query();

        // Apply user filter if set
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        // Apply village filter if set
        if ($this->villageId) {
            $query->where('village_id', $this->villageId);
        }

        // If user is admin, show all data
        if ($user->roles->contains('name', 'super_admin')) {
            // No additional filtering needed
        } else {
            // Get assigned areas based on user role
            if ($user->roles->contains('name', 'Mahasiswa')) {
                // For students, show only their assigned areas
                $assignments = Assignment::where('user_id', $user->id)
                    ->where('area_type', 'App\\Models\\Village')
                    ->pluck('area_id')
                    ->toArray();

                $query->whereIn('village_id', $assignments);
            } elseif ($user->roles->contains('name', 'Petugas')) {
                // For employees, show all areas assigned to students
                $assignments = Assignment::whereHas('user', function ($q) {
                    $q->whereHas('roles', function ($r) {
                        $r->where('name', 'Mahasiswa');
                    });
                })
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();

                $query->whereIn('village_id', $assignments);
            }
        }

        // Get cumulative data for the selected date range
        $data = $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->whereBetween('created_at', [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay()
        ])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // If no data, return empty dataset with selected date range
        if ($data->isEmpty()) {
            $dates = collect();
            $currentDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            while ($currentDate <= $endDate) {
                $dates->push($currentDate->format('Y-m-d'));
                $currentDate->addDay();
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Total Kumulatif Data Usaha',
                        'data' => array_fill(0, $dates->count(), 0),
                    ],
                ],
                'labels' => $dates->map(function ($date) {
                    return Carbon::parse($date)->format('d M Y');
                })->toArray(),
            ];
        }

        // Calculate cumulative totals
        $cumulativeData = [];
        $runningTotal = 0;
        foreach ($data as $row) {
            $runningTotal += $row->total;
            $cumulativeData[] = $runningTotal;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Kumulatif Data Usaha',
                    'data' => $cumulativeData,
                ],
            ],
            'labels' => $data->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('d M Y');
            })->toArray(),
        ];
    }
}
