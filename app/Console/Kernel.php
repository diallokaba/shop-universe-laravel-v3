<?php

namespace App\Console;

use App\Jobs\ArchiveDetteJobWithFireBase;
use App\Jobs\ArchiveDetteJobWithMongo;
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
        $db_archive = env('DB_ARCHIVE');
        // $schedule->command('inspire')->hourly();
        //$schedule->job(new HelloWorldJob)->everyMinute();
        //$schedule->job(new SendSMSNotificationJob)->weeklyOn(5, '14:00');
        //$schedule->job(new SendSMSNotificationJob)->everyMinute();
        if($db_archive == 'mongodb') {
            //$schedule->job(new ArchiveDetteJobWithMongo)->everyFiveSeconds();
            $schedule->job(new ArchiveDetteJobWithMongo)->daily();
        }elseif($db_archive == 'firebase') {
            $schedule->job(new ArchiveDetteJobWithFireBase)->daily();
        }
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
