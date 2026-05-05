<?php

namespace App\Console;

use App\Services\WebsiteMonitorService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            app(WebsiteMonitorService::class)->dispatchDueWebsiteChecks();
        })->everyMinute();
    }

    protected function commands(): void
    {
        require base_path('routes/console.php');
    }
}
