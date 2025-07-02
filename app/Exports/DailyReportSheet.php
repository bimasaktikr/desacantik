<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyReportSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $districts;
    protected $villages;
    protected $title;
    protected $isToday;

    public function __construct($districts, $villages, $title, $isToday = false)
    {
        $this->districts = $districts;
        $this->villages = $villages;
        $this->title = $title;
        $this->isToday = $isToday;
    }

    public function array(): array
    {
        $rows = [
            ['Kecamatan', 'Desa/Kelurahan', $this->isToday ? 'Usaha Ditambahkan Hari Ini' : 'Total Usaha'],
        ];

        foreach ($this->districts as $district) {
            $districtTotal = $this->isToday
                ? $district->villages->sum('today_count')
                : $district->business_count;
            $rows[] = [$district->name, '', $districtTotal];

            foreach ($this->villages->where('district_id', $district->id) as $village) {
                $villageTotal = $this->isToday
                    ? ($village->today_count ?? 0)
                    : ($village->businesses_count ?? 0);
                $rows[] = ['', $village->name, $villageTotal];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E0E7EF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '888888'],
                ],
            ],
        ]);

        // Apply border to all cells
        $rowCount = count($this->array());
        $sheet->getStyle("A1:C{$rowCount}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Bold and background for district rows
        for ($i = 2; $i <= $rowCount; $i++) {
            if ($sheet->getCell("A{$i}")->getValue() && !$sheet->getCell("B{$i}")->getValue()) {
                $sheet->getStyle("A{$i}:C{$i}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 30,
            'C' => 22,
        ];
    }
}
