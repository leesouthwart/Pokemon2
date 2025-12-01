<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('convert:currency')->dailyAt('09:00');
        $schedule->command('get:access_token')->dailyAt('09:05')->withoutOverlapping();
        $schedule->command('app:update-cards')->dailyAt('09:30')->withoutOverlapping();
        $schedule->command('psa:reset-expired')->hourly();
        $schedule->command('pending:create')->dailyAt('16:00')->timezone('Europe/London')->withoutOverlapping();
        // Process pending bids every 10 minutes to catch auctions ending soon
        $schedule->command('bids:process')->everyTenMinutes()->withoutOverlapping();
       // $schedule->command('app:test-logs')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
