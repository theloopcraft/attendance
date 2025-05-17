<?php

namespace App\Actions\Attendance;

use App\Actions\User\SyncUserFromDevice;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\User;
use App\Services\ZktDevice;
use App\Traits\DeviceTraits;
use DateTimeZone;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Action;
use PhAnviz\Client;
use PhAnviz\PhAnviz;

class SyncAttendance extends Action
{
    use DeviceTraits;

    public function handle(): void
    {
        $devices = $this->getDevices();

        if (! $devices->count()) {
            Notification::make()
                ->title('No active devices were detected, downloading failed.')
                ->danger()
                ->send();

            return;
        }

        $this->getDevices()->each(function (Device $device) {
            try {
                if ($device->type == 'anviz') {

                    $client = Client::createInstance($device->id, $device->ip, $device->port);
                    $anviz = new PhAnviz($client, new DateTimeZone($device->timezone));
                    $responses = $anviz->downloadNewTimeAttendanceRecords(true);

                    collect($responses)->each(function ($record) use ($device) {

                        $user = User::query()->firstOrCreate(['biometric_id' => $record['user_code']],
                            ['name' => $record['user_code']]);

                        Attendance::query()
                            ->firstOrCreate([
                                'device_id' => $device->id,
                                'user_id' => $user->id,
                                'action_at' => Carbon::createFromTimestamp($record['timestamp'], $device->timezone)->toDateTimeString(),
                            ],
                                ['action' => $record['record_type'] ? 'Check-out' : 'Check-in']
                            );
                    });
                } else {
                    $zkt = (new ZktDevice($device));
                    $attendances = $zkt->getAttendances($device->version == 1 ? 40 : 49);

                    $attendances->each(function ($record) use ($device) {
                        $user = User::query()->where('biometric_id', $record['id'])->latest()->first();

                        if (! $user) {
                            SyncUserFromDevice::dispatchSync();
                        }

                        $user = User::query()->where('biometric_id', $record['id'])->latest()->first();

                        Attendance::query()->firstOrCreate(
                            ['device_id' => $device->id, 'user_id' => $user?->id, 'action_at' => $record->action_at],
                            ['action' => $this->action($record->action)]
                        );
                    });

                }

            } catch (Exception $exception) {
                Notification::make()
                    ->title($exception->getMessage())
                    ->danger()
                    ->send();
            }
        });
    }

    protected function action(string $action): string
    {
        if (str_contains($action, 'in')) {
            return 'Check-in';
        } elseif (str_contains($action, 'out')) {
            return 'Check-out';
        }

        return 'Undefined';
    }
}
