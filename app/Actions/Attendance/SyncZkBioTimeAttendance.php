<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\User;
use App\Services\ZkBioTimeDevice;
use App\Traits\DeviceTraits;
use Exception;
use Filament\Notifications\Notification;
use Lorisleiva\Actions\Action;

class SyncZkBioTimeAttendance extends Action
{
    use DeviceTraits;

    public function handle(): void
    {
        $devices = $this->getBioTimeDevices();

        if (! $devices->count()) {
            return;
        }

        $devices->each(function (Device $device) {
            try {
                $syncedAt = now();

                $bioTime = new ZkBioTimeDevice($device);
                $attendances = $bioTime->getAttendances();

                $attendances->each(function ($record) use ($device) {
                    $user = User::query()
                        ->where('biometric_id', $record->biometric_id)
                        ->latest()
                        ->first();

                    if (! $user) {
                        $user = $this->syncUser($device, $record->biometric_id);
                    }

                    if (! $user) {
                        return;
                    }

                    Attendance::query()->firstOrCreate(
                        [
                            'device_id' => $device->id,
                            'user_id' => $user->id,
                            'action_at' => $record->action_at,
                        ],
                        ['action' => $record->action]
                    );
                });

                $device->update(['last_synced_at' => $syncedAt]);
            } catch (Exception $exception) {
                Notification::make()
                    ->title($exception->getMessage())
                    ->danger()
                    ->send();
            }
        });
    }

    protected function syncUser(Device $device, string $empCode): ?User
    {
        try {
            $bioTime = new ZkBioTimeDevice($device);
            $employees = $bioTime->getUsers();

            $employee = $employees->firstWhere('emp_code', $empCode);

            if (! $employee) {
                return null;
            }

            $name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')) ?: $empCode;

            return User::query()->updateOrCreate(
                ['biometric_id' => $empCode],
                ['name' => $name]
            );
        } catch (Exception) {
            return null;
        }
    }
}
