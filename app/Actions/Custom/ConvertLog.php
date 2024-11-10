<?php

namespace App\Actions\Custom;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\FeederLog;
use App\Models\User;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class ConvertLog
{
    use AsAction;

    public function handle(FeederLog $feederLog)
    {
        $device = Device::updateOrCreate([
            'name' => $feederLog->device,
            'type' => 'custom',
        ], [
            'location' => $feederLog->device,
            'timezone' => 'Indian/Maldives',
            'ip' => '127.0.0.1',
            'port' => '0000'
        ]);

        $user = User::updateOrCreate([
            'name' => $feederLog->name,
            'biometric_id' => $feederLog->staff_no,
        ]);


        Attendance::query()->firstOrCreate(
            ['device_id' => $device->id, 'user_id' => $user?->id, 'action_at' => $feederLog->action_at],
            ['action' => $this->action(Str::lower($feederLog->action))]
        );

        $feederLog->update(['status' => 'completed']);


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
