<?php

namespace App\Actions\Attendance;

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

        $this->getAttendance()->chunk(500, function ($attendances) {

            try {
                HumanlotClient::query()->where('status', 1)
                    ->each(function (HumanlotClient $client) use ($attendances) {

                        $response = $client->validateToken();

                        if (! $response->successful()) {
                            $client->update(['status' => 0]);

                            Notification::make()
                                ->title('It appears an invalid token has been provided, Please double-check.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $request = Http::withHeaders(['x-tenant' => $client->app_id])
                            ->withToken($client->secret)
                            ->baseUrl($client->base_url)
                            ->asJson()
                            ->acceptJson()
                            ->post('integerations/attendance_sync', [
                                'logs' => $this->formatAttendance($attendances),
                            ]);

                        if (! $request->ok()) {
                            return;
                        }

                        $this->syncCompleted($attendances);

                        Notification::make()
                            ->title('Attendance records have been successfully synced to the server.')
                            ->success()
                            ->send();
                    });

            } catch (Exception $exception) {

                Notification::make()
                    ->title($exception->getMessage())
                    ->danger()
                    ->send();
            }

        });

    }

    public function formatAttendance(Collection $attendances): Collection
    {
        return $attendances->map(function ($log) {
            return [
                'id' => $log->id,
                'action_at' => $log->action_at,
                'action' => $log->action,
                'device_type' => 'attendance_machine',
                'action_device' => $log->device,
                'device' => [
                    'name' => $log->device?->name,
                    'ip' => $log->device?->ip,
                    'type' => 'custom-api',
                    'location' => $log->device?->location,
                    'timezone' => $log->device?->timezone,
                ],
                'user' => [
                    'biometric_id' => $log->employee?->personal_id,
                ],
            ];
        });
    }
}
