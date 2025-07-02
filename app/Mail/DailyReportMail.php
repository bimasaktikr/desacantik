<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $excelPath;

    public function __construct($pdfPath, $excelPath)
    {
        $this->pdfPath = $pdfPath;
        $this->excelPath = $excelPath;
    }

    public function build()
    {
        return $this->subject('Laporan Harian Otomatis')
            ->view('emails.daily-report')
            ->attach(Storage::disk('local')->path($this->pdfPath), [
                'as' => 'laporan-harian.pdf',
                'mime' => 'application/pdf',
            ])
            ->attach(Storage::disk('local')->path($this->excelPath), [
                'as' => 'laporan-harian.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
