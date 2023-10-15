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
use Filament\Facades\Filament;
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

        if (!$devices->count()) {
            Notification::make()
                ->title('No active devices were detected, downloading failed.')
                ->danger()
                ->send();

            return;
        }

        $this->getDevices()->each(function (Device $device) {
            try {
                if ($device->type == 'anviz') {

                    $client = Client::createInstance($device->id, $device->ip, 5010);
                    $anviz = new PhAnviz($client, new DateTimeZone('Indian/Maldives'));

                    $responses = $anviz->downloadNewTimeAttendanceRecords(true);

                    collect($responses)->each(function ($record) use ($device) {

                        $user = User::query()->firstOrCreate(['biometric_id' => $record['user_code']],
                            ['name' => $record['user_code']]);
                        Attendance::query()
                            ->firstOrCreate([
                                'device_id' => $device->id,
                                'user_id' => $user->id,
                                'action_at' => Carbon::createFromTimestamp($record['timestamp'],
                                    'Indian/maldives')->toDateTimeString()
                            ],
                                ['action' => $record['record_type'] ? 'Check-out' : 'Check-in']
                            );
                    });
                } else {
                    $zkt = (new ZktDevice($device));
                    $attendances = $zkt->getAttendances();

                    $attendances->each(function ($record) use ($device) {
                        $user = User::query()->where('biometric_id', $record['id'])->first();

                        if (!$user) {
                            SyncUserFromDevice::dispatchSync();
                        }

                        $user = User::query()->where('biometric_id', $record['id'])->first();

                        Attendance::query()->firstOrCreate(
                            ['device_id' => $device->id, 'user_id' => $user?->id, 'action_at' => $record->action_at],
                            ['action' => $record->action]
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

}
