<?php

namespace App\Services\Contract;

use App\Models\Device;
use Illuminate\Support\Collection;

interface DeviceDataContract
{
    public function __construct(Device $device);

    public function getUsers(): Collection;

    public function getAttendances(string $startDate = null, string $endDate = null): Collection;
}
