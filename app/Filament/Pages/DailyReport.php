<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Business;
use App\Models\Village;
use App\Models\District;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailyReportExport;

class DailyReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $title = 'Laporan Harian';
    protected static ?string $slug = 'laporan-harian';
    protected static ?string $navigationLabel = 'Laporan Harian';
    protected static ?string $navigationGroup = 'Laporan';

    protected static string $view = 'filament.pages.daily-report';

    public $cumulativeByDistrict = [];
    public $cumulativeByVillage = [];
    public $todayByDistrict = [];
    public $todayByVillage = [];

    public function mount()
    {
        // Cumulative by district
        $this->cumulativeByDistrict = District::withCount(['villages as business_count' => function ($query) {
            $query->join('businesses', 'villages.id', '=', 'businesses.village_id');
        }])->get();

        // Cumulative by village
        $this->cumulativeByVillage = Village::withCount('businesses')->get();

        // Today's progress by district
        $this->todayByDistrict = District::with(['villages' => function ($q) {
            $q->withCount(['businesses as today_count' => function ($query) {
                $query->whereDate('created_at', today());
            }]);
        }])->get();

        // Today's progress by village
        $this->todayByVillage = Village::withCount(['businesses as today_count' => function ($query) {
            $query->whereDate('created_at', today());
        }])->get();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Eager load all relationships and counts for PDF export
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

                    $data = [
                        'cumulativeByDistrict' => $cumulativeByDistrict,
                        'cumulativeByVillage' => $cumulativeByVillage,
                        'todayByDistrict' => $todayByDistrict,
                        'todayByVillage' => $todayByVillage,
                    ];
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.pages.daily-report-pdf', $data);
                    return response()->streamDownload(
                        fn () => print($pdf->stream()),
                        'laporan-harian-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Eager load all relationships and counts for Excel export
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

                    $export = new DailyReportExport($cumulativeByDistrict, $cumulativeByVillage, $todayByDistrict, $todayByVillage);

                    return Excel::download($export, 'laporan-harian-' . now()->format('Y-m-d') . '.xlsx');
                }),
        ];
    }
}
