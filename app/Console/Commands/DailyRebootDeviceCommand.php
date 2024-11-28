<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Rats\Zkteco\Lib\ZKTeco;

class DailyRebootDeviceCommand extends Command
{
    protected $signature = 'daily:reboot-device';

    protected $description = 'Command description';

    public function handle(): void
    {
        //        Device::query()->where('is_active', true)->where('type', '!=', 'anviz')->get()
        //            ->each(function (Device $device) {
        //                $zk = new ZKTeco($device->ip, $device->port);
        //                $zk->connect();
        //                $zk->restart();
        //                $device->update(['is_active' => true]);
        //            });
        //
        //        exec('git checkout .');

        exec('git pull');
        exec('composer install');

        //        exec('npm install');
        //
        //        exec('npm run build');

        $time = now()->timezone('Indian/Maldives')->format('D, d M Y H:i');
        Log::alert("composer updated running: $time");

    }
}
