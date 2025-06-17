<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\Assignment;
use App\Models\AssignmentUpload;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $query = Business::query();

        $totalUploads = 0;
        $successUploads = 0;
        $failedUploads = 0;

        if ($user->roles->contains('name', 'Mahasiswa')) {
            $query->where('user_id', $user->id);
            $assignmentIds = Assignment::where('user_id', $user->id)
                ->pluck('id')
                ->toArray();

            $totalUploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)->count();
            $successUploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)->where('import_status', 'berhasil')->count();
            $failedUploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)->where('import_status', 'gagal')->count();
        } elseif ($user->roles->contains('name', 'Petugas')) {
            $assignedAreaIds = Assignment::whereHas('user', function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', 'Mahasiswa');
                });
            })->pluck('area_id')->toArray();

            $query->whereIn('village_id', $assignedAreaIds);
            $totalUploads = AssignmentUpload::whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            })->count();
            $successUploads = AssignmentUpload::whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            })->where('import_status', 'berhasil')->count();
            $failedUploads = AssignmentUpload::whereHas('assignment', function ($q) use ($assignedAreaIds) {
                $q->whereIn('area_id', $assignedAreaIds);
            })->where('import_status', 'gagal')->count();
        }

        return [
            Stat::make('Total Data Usaha', $query->count())
                ->description('Total data usaha yang telah diupload')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Total File Upload', $totalUploads)
                ->description('Total file yang telah diupload')
                ->descriptionIcon('heroicon-m-document')
                ->color('primary'),

            Stat::make('Upload Berhasil', $successUploads)
                ->description('File yang berhasil diproses')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Upload Gagal', $failedUploads)
                ->description('File yang gagal diproses')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
