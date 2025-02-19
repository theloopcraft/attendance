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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;
use PhAnviz\Client;
use PhAnviz\PhAnviz;

class SyncAttendance extends Action
{
    use DeviceTraits;

    public function handle(): void
    {

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '-1');

        $attendances = Attendance::query()->latest()->first();
        $startAt = Carbon::now()->startOfDay()->toDateTimeString();
        $endAt = Carbon::now()->endOfDay()->toDateTimeString();

        if ($attendances) {
            $startAt = Carbon::parse($attendances->action_at)->startOfDay()->toDateTimeString();
            $endAt = Carbon::parse($attendances->action_at)->endOfDay()->toDateTimeString();
        }

        $response = Http::baseUrl('http://192.168.1.155')
            ->timeout(4000)
            ->withToken('de70f6cb421a5a62a478d448bdddc9a95cacc9ab', 'Token')
            ->acceptJson()
            ->get('iclock/api/transactions/', [
                'start_time' => $startAt,
                'end_time' => $endAt,
                'page' => 1,
                'page_size' => 500,
            ]);

        if (!$response->successful()) {
            Log::error($response->json());
            dd($response->json());
        }

        $allData = $response->json('data');
        
        collect($allData)->each(function ($attendance) {

            $device = \App\Models\Device::firstOrCreate([
                'name' => $attendance['terminal_alias'],
            ], [
                'type' => 'API',
                'timezone' => 'indian/maldives',
                'location' => $attendance['area_alias'],
                'ip' => 'localhost',
                'port' => '0',
                'is_active' => 1
            ]);

            $user = User::query()->firstOrCreate(
                ['biometric_id' => $attendance['emp_code']],
                ['name' => $attendance['first_name']]);

            Attendance::query()
                ->firstOrCreate([
                    'device_id' => $device->id,
                    'user_id' => $user->id,
                    'action_at' => $attendance['punch_time'],
                ], [
                    'action' => $attendance['punch_state_display']
                ]);
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
