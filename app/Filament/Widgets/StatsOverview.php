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

        if ($user->roles->contains('name', 'super_admin')) {
            // Super admin: show all
            $totalUploads = AssignmentUpload::count();
            $successUploads = AssignmentUpload::where('import_status', 'berhasil')->count();
            $failedUploads = AssignmentUpload::where('import_status', 'gagal')->count();
        } elseif ($user->roles->contains('name', 'Mahasiswa')) {
            $query->where('user_id', $user->id);
            $assignmentIds = Assignment::where('user_id', $user->id)
                ->pluck('id')
                ->toArray();

            $totalUploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)->count();
            $successUploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)->where('import_status', 'berhasil')->count();
            $failedUploads = AssignmentUpload::whereIn('assignment_id', $assignmentIds)->where('import_status', 'gagal')->count();
        } elseif ($user->roles->contains('name', 'Employee')) {
            // Get all area (village) IDs assigned to the employee
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

            // Show stats for all businesses and uploads by those Mahasiswa in those areas
            $query->whereIn('user_id', $mahasiswaUserIds)
                ->whereIn('village_id', $employeeAreaIds);

            $totalUploads = AssignmentUpload::whereIn('assignment_id', function ($q) use ($employeeAreaIds, $mahasiswaUserIds) {
                $q->select('id')
                  ->from('assignments')
                  ->whereIn('area_id', $employeeAreaIds)
                  ->whereIn('user_id', $mahasiswaUserIds)
                  ->where('area_type', 'App\\Models\\Village');
            })->count();
            $successUploads = AssignmentUpload::whereIn('assignment_id', function ($q) use ($employeeAreaIds, $mahasiswaUserIds) {
                $q->select('id')
                  ->from('assignments')
                  ->whereIn('area_id', $employeeAreaIds)
                  ->whereIn('user_id', $mahasiswaUserIds)
                  ->where('area_type', 'App\\Models\\Village');
            })->where('import_status', 'berhasil')->count();
            $failedUploads = AssignmentUpload::whereIn('assignment_id', function ($q) use ($employeeAreaIds, $mahasiswaUserIds) {
                $q->select('id')
                  ->from('assignments')
                  ->whereIn('area_id', $employeeAreaIds)
                  ->whereIn('user_id', $mahasiswaUserIds)
                  ->where('area_type', 'App\\Models\\Village');
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
