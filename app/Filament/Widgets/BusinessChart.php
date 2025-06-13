<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\Assignment;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BusinessChart extends LineChartWidget
{
    protected static ?string $heading = 'Tren Data Usaha';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->subDays(14)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $query = Business::query();

        // If user is admin, show all data
        if ($user->roles->contains('name', 'super_admin')) {
            // No additional filtering needed
        } else {
            // Get assigned areas based on user role
            if ($user->roles->contains('name', 'Mahasiswa')) {
                // For students, show only their assigned areas
                $assignments = Assignment::where('user_id', $user->id)->get();

                $query->where(function($q) use ($assignments) {
                    foreach ($assignments as $assignment) {
                        if ($assignment->area_type === 'App\\Models\\Village') {
                            $q->orWhere('village_id', $assignment->area_id);
                        } elseif ($assignment->area_type === 'App\\Models\\Sls') {
                            $q->orWhere('sls_id', $assignment->area_id);
                        }
                    }
                });
            } elseif ($user->roles->contains('name', 'Petugas')) {
                // For employees, show all areas assigned to students
                $assignments = Assignment::whereHas('user', function ($q) {
                    $q->whereHas('roles', function ($r) {
                        $r->where('name', 'Mahasiswa');
                    });
                })->get();

                $query->where(function($q) use ($assignments) {
                    foreach ($assignments as $assignment) {
                        if ($assignment->area_type === 'App\\Models\\Village') {
                            $q->orWhere('village_id', $assignment->area_id);
                        } elseif ($assignment->area_type === 'App\\Models\\Sls') {
                            $q->orWhere('sls_id', $assignment->area_id);
                        }
                    }
                });
            }
        }

        // Get data for the selected date range
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
                        'label' => 'Jumlah Data Usaha',
                        'data' => array_fill(0, $dates->count(), 0),
                    ],
                ],
                'labels' => $dates->map(function ($date) {
                    return Carbon::parse($date)->format('d M Y');
                })->toArray(),
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Data Usaha',
                    'data' => $data->pluck('total')->toArray(),
                ],
            ],
            'labels' => $data->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('d M Y');
            })->toArray(),
        ];
    }
}
