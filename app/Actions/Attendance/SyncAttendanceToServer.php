<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\HumanlotClient;
use App\Traits\DeviceTraits;
use App\Traits\HumanlotClientTrait;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Action;

class SyncAttendanceToServer extends Action
{
    use DeviceTraits, HumanlotClientTrait;

    public function handle(): void
    {
        if (! $this->logsCount()) {
            Notification::make()
                ->title('No attendance records to sync.')
                ->danger()
                ->send();

            return;
        }

        $this->getAttendance()->chunk(10, function ($attendances) {

            try {
                HumanlotClient::query()
                    ->where('status', 1)
                    ->each(function (HumanlotClient $client) use ($attendances) {

                        $response = $client->validateToken();

                        if ($response->unauthorized()) {
                            $client->update(['status' => 0]);

                            Notification::make()
                                ->title('It appears an invalid token has been provided, Please double-check.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $attendances->groupBy('device_id')->each(function (
                            $collection,
                            $key
                        ) use ($client) {

                            $device = Device::find($key);

                            $request = Http::withHeaders(['x-tenant' => $client->app_id])
                                ->withToken($client->secret)
                                ->baseUrl($client->base_url)
                                ->asJson()
                                ->acceptJson()
                                ->post('integerations/sync_attendance', [
                                    'device_name' => $device->name,
                                    'device_ip' => $device->ip,
                                    'timezone' => $device->timezone,
                                    'logs' => $this->formatAttendance($collection),
                                ]);

                            if (! $request->ok()) {
                                return;
                            }

                            $this->syncCompleted($collection);

                            Notification::make()
                                ->title('Attendance records have been successfully synced to the server.')
                                ->success()
                                ->send();
                        });

                    });

            } catch (Exception $exception) {

                Notification::make()
                    ->title($exception->getMessage())
                    ->danger()
                    ->send();
            }

        });

    }

    public function formatAttendance(Collection $attendances): array
    {
        return $attendances->map(function (Attendance $attendance) {
            return [
                'name' => $attendance->user?->name ?? 'Unknown',
                'personal_id' => $attendance->user?->biometric_id ?? null,
                'action' => $attendance->action,
                'action_at' => $attendance->action_at,
            ];
        })->filter(function ($log) {
            return $log['personal_id'] !== 'Unknown';
        })->values()->toArray();
    }
}
