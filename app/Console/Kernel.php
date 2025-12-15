<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Traits\ParsesCronFrequency;

class Kernel extends ConsoleKernel
{
    use ParsesCronFrequency;
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('humanlot-attendance:sync')->cron($this->parseFrequency(env('HUMANLOT_SYNC_MACHINE_FREQUENCY', '1m')));
        $schedule->command('sync:server')->cron($this->parseFrequency(env('HUMANLOT_SYNC_LIVE_FREQUENCY', '1m')));
        $schedule->command('daily:logs-clean')->dailyAt('00:00');
        $schedule->command('daily:update-software')->dailyAt('00:00');
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
