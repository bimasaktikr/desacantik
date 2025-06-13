<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\Assignment;
use App\Models\AssignmentUpload;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $query = Business::query();
        $uploadQuery = AssignmentUpload::query();

        // Get assigned areas based on user role
        if ($user->roles->contains('name', 'Mahasiswa')) {
            // For students, show only their assigned areas
            $assignedAreaIds = Assignment::where('user_id', $user->id)
                ->pluck('area_id')
                ->toArray();

            $query->whereIn('village_id', $assignedAreaIds);
            $uploadQuery->whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            });
        } elseif ($user->roles->contains('name', 'Petugas')) {
            // For employees, show all areas assigned to students
            $assignedAreaIds = Assignment::whereHas('user', function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', 'Mahasiswa');
                });
            })->pluck('area_id')->toArray();

            $query->whereIn('village_id', $assignedAreaIds);
            $uploadQuery->whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            });
        }

        return [
            Stat::make('Total Data Usaha', $query->count())
                ->description('Total data usaha yang telah diupload')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Total File Upload', $uploadQuery->count())
                ->description('Total file yang telah diupload')
                ->descriptionIcon('heroicon-m-document')
                ->color('primary'),

            Stat::make('Upload Berhasil', $uploadQuery->where('import_status', 'berhasil')->count())
                ->description('File yang berhasil diproses')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Upload Gagal', $uploadQuery->where('import_status', 'gagal')->count())
                ->description('File yang gagal diproses')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
