<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Broadcast AFL data every minute
        $schedule->command('broadcast:afl-data')
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/broadcast-afl.log'));
                
        // You can adjust the frequency as needed:
        // ->everyFiveMinutes()
        // ->everyThirtyMinutes()
        // ->hourly()
        // ->daily()
        // Or use a custom cron expression:
        // ->cron('*/2 * * * *')  // Every 2 minutes
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
