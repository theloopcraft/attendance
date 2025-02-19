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
        $response = collect(json_decode(file_get_contents(public_path('/zkt/api-transactions-response.json')),
            true))['data'];

        collect($response)->each(function ($attendance) {

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
