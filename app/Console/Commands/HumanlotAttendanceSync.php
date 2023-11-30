<?php

namespace App\Console\Commands;

use App\Actions\Attendance\SyncAttendance;
use Illuminate\Console\Command;

class HumanlotAttendanceSync extends Command
{
    protected $signature = 'humanlot-attendance:sync';

    protected $description = 'This sync the logs from machine to local DB';

    public function handle(): void
    {
        SyncAttendance::dispatchSync();
    }
}
