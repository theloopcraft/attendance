<?php

namespace App\Console\Commands;

use App\Actions\Attendance\SyncAttendanceToServer;
use Illuminate\Console\Command;

class SyncToServerCommand extends Command
{
    protected $signature = 'sync:server';

    protected $description = 'Attendance logs to server';

    public function handle()
    {
        SyncAttendanceToServer::dispatchSync();
    }
}
