<?php

namespace App\Services;

use App\Exports\DailyReportExport;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Mail\DailyReportMail;

class DailyReportMailerService
{
    public function send($recipients)
    {
        // Prepare data (same as in your export actions)
        $cumulativeByDistrict = \App\Models\District::withCount(['villages as business_count' => function ($query) {
            $query->join('businesses', 'villages.id', '=', 'businesses.village_id');
        }])->with(['villages' => function ($q) {
            $q->withCount('businesses');
        }])->get();

        $cumulativeByVillage = \App\Models\Village::with('district')->withCount('businesses')->get();

        $todayByDistrict = \App\Models\District::with(['villages' => function ($q) {
            $q->withCount(['businesses as today_count' => function ($query) {
                $query->whereDate('created_at', today());
            }]);
        }])->get();

        $todayByVillage = \App\Models\Village::with('district')->withCount(['businesses as today_count' => function ($query) {
            $query->whereDate('created_at', today());
        }])->get();

        // Generate Excel
        $excelPath = 'reports/laporan-harian-' . now()->format('Y-m-d') . '.xlsx';
        Excel::store(new DailyReportExport($cumulativeByDistrict, $cumulativeByVillage, $todayByDistrict, $todayByVillage), $excelPath, 'local');

        // Generate PDF
        $pdfPath = 'reports/laporan-harian-' . now()->format('Y-m-d') . '.pdf';
        $pdf = Pdf::loadView('filament.pages.daily-report-pdf', [
            'cumulativeByDistrict' => $cumulativeByDistrict,
            'cumulativeByVillage' => $cumulativeByVillage,
            'todayByDistrict' => $todayByDistrict,
            'todayByVillage' => $todayByVillage,
        ]);
        Storage::disk('local')->put($pdfPath, $pdf->output());

        // Send email
        Mail::to($recipients)->send(new DailyReportMail($pdfPath, $excelPath));

        // Optionally, clean up files after sending
        Storage::disk('local')->delete([$pdfPath, $excelPath]);
    }
}
