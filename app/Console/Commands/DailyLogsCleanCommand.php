<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\ZktDevice;
use Illuminate\Console\Command;
use Rats\Zkteco\Lib\ZKTeco;

class DailyLogsCleanCommand extends Command
{
    protected $signature = 'daily:logs-clean';

    protected $description = 'This command will clean the logs daily';

    public function handle(): void
    {
        Device::query()->where('is_active', true)->where('type', '!=', 'anviz')->get()
            ->each(function (Device $device) {
                $zk = new ZKTeco($device->ip, $device->port);
                $zk->connect();
                $zk->disableDevice();
                $zk->clearAttendance();
                $zk->enableDevice();
            });
    }
}
