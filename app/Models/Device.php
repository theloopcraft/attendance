<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rats\Zkteco\Lib\ZKTeco;

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
    ];

    public static function testVoice($device): void
    {
        $zk = new ZKTeco($device->ip, $device->port);
        if ($zk->connect()) {
            $device->is_active = 1;
            $device->save();
        }
        $zk->testVoice();
        $zk->disconnect();
    }

    public static function clearLogs($device): void
    {
        $zk = new ZKTeco($device->ip, $device->port);
//        $zk->clearAttendance();
        $zk->disconnect();
    }

    public static function users($device): void
    {
        $zk = new ZKTeco($device->ip, $device->port);
        $zk->getUser();
        $zk->disconnect();

    }

}
