<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyReportExport implements WithMultipleSheets
{
    protected $cumulativeByDistrict;
    protected $cumulativeByVillage;
    protected $todayByDistrict;
    protected $todayByVillage;

    public function __construct($cumulativeByDistrict, $cumulativeByVillage, $todayByDistrict, $todayByVillage)
    {
        $this->cumulativeByDistrict = $cumulativeByDistrict;
        $this->cumulativeByVillage = $cumulativeByVillage;
        $this->todayByDistrict = $todayByDistrict;
        $this->todayByVillage = $todayByVillage;
    }

    public function sheets(): array
    {
        return [
            new \App\Exports\DailyReportSheet($this->cumulativeByDistrict, $this->cumulativeByVillage, 'Rekapitulasi Usaha'),
            new \App\Exports\DailyReportSheet($this->todayByDistrict, $this->todayByVillage, 'Progres Hari Ini', true),
        ];
    }
}
