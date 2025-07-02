<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Notifications\Notification;
use App\Services\DailyReportMailerService;
use App\Models\User;

class SendDailyReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $title = 'Kirim Laporan Harian Manual';
    protected static ?string $slug = 'kirim-laporan-harian';
    protected static ?string $navigationLabel = 'Kirim Laporan Harian';
    protected static ?string $navigationGroup = 'Laporan';

    protected static string $view = 'filament.pages.send-daily-report';

    public $recipients = [];

    public function sendReport()
    {
        if (empty($this->recipients)) {
            Notification::make()->title('Penerima harus dipilih!')->danger()->send();
            return;
        }

        // Get emails from selected user IDs
        $emails = User::whereIn('id', $this->recipients)->pluck('email')->toArray();

        app(DailyReportMailerService::class)->send($emails);

        Notification::make()->title('Laporan berhasil dikirim!')->success()->send();
        $this->recipients = [];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('recipients')
                ->label('Pilih Penerima (Employee)')
                ->multiple()
                ->searchable()
                ->options(
                    User::whereHas('roles', fn($q) => $q->where('name', 'Employee'))
                        ->pluck('email', 'id')
                )
                ->required(),
        ];
    }
}
