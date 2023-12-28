<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Rats\Zkteco\Lib\ZKTeco;

class DailyRebootDeviceCommand extends Command
{
    protected $signature = 'daily:reboot-device';

    protected $description = 'Command description';

    public function handle(): void
    {
        Device::query()->where('is_active', true)->where('type', '!=', 'anviz')->get()
            ->each(function (Device $device) {
                $zk = new ZKTeco($device->ip, $device->port);
                $zk->connect();
                $zk->restart();
            });
    }
}
