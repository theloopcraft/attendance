<?php

namespace App\Traits;

use App\Models\Attendance;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait DeviceTraits
{
    public function getDevices(): Collection|array
    {
        return Device::query()->where('is_active', true)->get();
    }

    public function logsCount(): int
    {
        return $this->getAttendance()->count();
    }

    public function getAttendance(): Builder
    {
        return Attendance::query()->with(['device', 'user'])
            ->where('sync_at', 0)
            ->orWhereNull('sync_at')
            ->latest();

    }

    public function syncCompleted($attendances): void
    {
        collect($attendances)->each(fn($attendance) => $attendance->update(['sync_at' => now()]));
    }
}
