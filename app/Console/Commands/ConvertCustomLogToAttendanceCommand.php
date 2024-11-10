<?php

namespace App\Console\Commands;

use App\Actions\Custom\ConvertLog;
use App\Actions\Custom\GetAttendanceLogsFromCustomFeeder;
use App\Models\FeederLog;
use Illuminate\Console\Command;

class ConvertCustomLogToAttendanceCommand extends Command
{
    protected $signature = 'sync:custom-log-to-attendance';

    protected $description = 'Command to convert custom logs to attendance';

    public function handle(): void
    {
        $this->info("Starting fetching logs");

        GetAttendanceLogsFromCustomFeeder::run(now()->toDateString(), now()->toDateString());

        $this->info("Completed fetching logs Successfully");

        $feederLogs = FeederLog::query()
            ->where('status', 'pending')
            ->lazyById();

        $this->info("Starting converting logs");

        $totalLogs = FeederLog::where('status', 'pending')->count();

        $this->output->progressStart($totalLogs);

        foreach ($feederLogs as $log) {
            ConvertLog::run($log);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Converted logs successfully");

    }
}
