<?php

namespace App\Actions\Attendance;

use App\Models\HumanlotClient;
use App\Traits\DeviceTraits;
use App\Traits\HumanlotClientTrait;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Action;

class SyncAttendanceToServer extends Action
{
    use DeviceTraits, HumanlotClientTrait;

    public function handle(): void
    {
        if (!$this->logsCount()) {
            Notification::make()
                ->title('No new logs have been identified for synchronization.')
                ->danger()
                ->send();
            return;
        }

        $this->getAttendance()->chunk(500, function ($attendances) {

            try {
                HumanlotClient::query()->where('status', 1)
                    ->each(function ($client) use ($attendances) {
                        $request = Http::withHeaders(['x-tenant' => $client->app_id])
                            ->withToken($client->secret)
                            ->baseUrl($client->base_url)
                            ->asJson()
                            ->acceptJson()
                            ->post('integerations/attendance_sync', [
                                'logs' => $attendances,
                            ]);

                        if (!$request->ok()) {
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
}
