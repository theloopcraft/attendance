<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rats\Zkteco\Lib\ZKTeco;
use App\Services\AnvizDevice;
use Illuminate\Support\Facades\Log;

class Device extends Model
{
    public function __construct(protected Device $device)
    {
        $this->device = $device;
    }

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
        if($device->type == 'anviz' ) {
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
