<?php

namespace App\Console\Commands;

use App\Actions\Attendance\SyncAttendance;
use App\Actions\User\SyncUserFromDevice;
use App\Models\Device;
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
