<?php

namespace App\Console;

use App\Jobs\HelloWorldJob;
use App\Jobs\SendSms;
use App\Jobs\SendSMSNotificationJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        //$schedule->job(new HelloWorldJob)->everyMinute();
        //$schedule->job(new SendSMSNotificationJob)->weeklyOn(5, '14:00');
        $schedule->job(new SendSMSNotificationJob)->everyMinute();
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
