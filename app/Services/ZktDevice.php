<?php

namespace App\Services;

use App\Models\Device;
use App\Services\Contract\DeviceDataContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Rats\Zkteco\Lib\Helper\Util;
use Rats\Zkteco\Lib\ZKTeco;

class ZktDevice implements DeviceDataContract
{
    public function __construct(protected Device $device)
    {
    }

    public function getUsers(): Collection
    {
        $zk = new ZKTeco($this->device->ip);

        return collect($zk->getUser());
    }

    public function getAttendances(?string $startDate = null, ?string $endDate = null): Collection
    {
        $zk = new ZKTeco($this->device->ip, $this->device->port);
        $zk->connect();
        $zk->disableDevice();


        $attendanceLogs = collect($zk->getAttendance($this->device->version == 1 ? 40 : 49))->map(fn ($record) => new Fluent([
            ...$record,
            'action_at' => $record['timestamp'],
            'action' => Util::getAttType($record['type']),
        ]));

        //        $zk->clearAttendance(); // Clear attendance logs after fetching
        $zk->enableDevice();

        return $attendanceLogs;
    }
}
