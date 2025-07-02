<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DailyReportMailerService;

class SendDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:send-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily business report via email';

    /**
     * Execute the console command.
     */
    public function handle(DailyReportMailerService $service)
    {
        // Add your recipient(s) here
        $recipients = ['admin@example.com', 'manager@example.com'];
        $service->send($recipients);
        $this->info('Daily report sent!');
    }
}
