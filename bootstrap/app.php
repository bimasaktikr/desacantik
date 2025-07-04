<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// use Illuminate\Support\Facades\Schedule;
use Illuminate\Console\Scheduling\Schedule;
// use App\Http\Middleware\CheckSiteActive;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // 'check-site-active' => CheckSiteActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // ✅ Tambahkan perintah queue:work
        $schedule->command('queue:work --stop-when-empty')->everyMinute();

        // Send daily report at 07:30, 16:00, and 00:00
        $schedule->command('report:send-daily')->dailyAt('07:30');
        $schedule->command('report:send-daily')->dailyAt('16:00');
        $schedule->command('report:send-daily')->dailyAt('00:00');
    })
    ->create();
