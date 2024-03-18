<?php

namespace App\Actions\User;

use App\Models\Device;
use App\Models\User;
use App\Traits\DeviceTraits;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Action;
use Rats\Zkteco\Lib\Helper\Util;
use Rats\Zkteco\Lib\ZKTeco;

class SyncUserFromDevice extends Action
{
    use DeviceTraits;

    public function handle(): void
    {
        $devices = $this->getDevices();

        if (! $devices->count()) {
            return;
        }

        $this->getDevices()->where('type', 'zkt')->each(function (Device $device) {

            $zk = new ZKTeco($device->ip, $device->port);
            $connection = $zk->connect();

            if (! $connection) {
                return;
            }

            collect($zk->getUser())->each(function ($user) {
                User::query()->updateOrCreate([
                    'name' => $user['name'] ?? 'not found',
                    'biometric_id' => $user['userid'],
                ], [
                    //                    'name' => $user['name'] ?? 'not found',
                    'is_admin' => $this->decideIsAdmin($user['role']),
                    'password' => $user['password'] ?? Hash::make(Str::random(10)),
                ]);
            });

        });

        Notification::make()
            ->title('Users have successfully synced.')
            ->success()
            ->send();
    }

    protected function decideIsAdmin($role): bool
    {
        if ($role == Util::LEVEL_ADMIN) {
            return true;
        }

        return false;
    }
}
