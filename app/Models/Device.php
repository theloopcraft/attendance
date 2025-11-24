<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rats\Zkteco\Lib\ZKTeco;
use App\Services\AnvizDevice;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class Device extends Model
{

    protected $fillable = [
        'name',
        'location',
        'ip',
        'is_active',
        'timezone',
        'type',
        'port',
        'user',
        'device_id',
        'password',
        'version',
    ];

    public static function testVoice($device): void
    {
        try {
            if ($device->type == 'anviz') {
                $anviz = new AnvizDevice($device);
                $anviz->login();
                Log::info($anviz->login());
                return;
            }
            $zk = new ZKTeco($device->ip, $device->port);
            if ($zk->connect()) {
                $device->is_active = 1;
                $device->save();
            }
            $zk->testVoice();
            $zk->disconnect();
        } catch (\Throwable $th) {
            Notification::make()
                ->title('Unable to connect to device')
                ->danger()
                ->send();
        }
    }

    public static function reboot($device): void
    {
        $zk = new ZKTeco($device->ip, $device->port);
        if ($zk->connect()) {
            $device->is_active = 1;
            $device->save();
        }
        $zk->restart();
    }

    public static function clearLogs($device): void
    {
        $zk = new ZKTeco($device->ip, $device->port);
        if ($zk->connect()) {
            $device->is_active = 1;
            $device->save();
        }
        $zk->clearAttendance();
        //        $zk->disconnect();
    }

    public static function users($device): void
    {
        $zk = new ZKTeco($device->ip, $device->port);
        $zk->getUser();
        $zk->disconnect();
    }
}
