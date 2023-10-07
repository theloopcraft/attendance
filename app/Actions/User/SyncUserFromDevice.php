<?php

namespace App\Actions\User;

use App\Models\User;
use App\Traits\DeviceTraits;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Action;
use Native\Laravel\Facades\Notification;
use Rats\Zkteco\Lib\Helper\Util;
use Rats\Zkteco\Lib\ZKTeco;

class SyncUserFromDevice extends Action
{
    use DeviceTraits;

    public function handle()
    {
        $devices = $this->getDevices();

        if (! $devices->count()) {
            //            Notification::make()
            //                ->title('Saved successfully')
            //                ->success()
            //                ->send();

            Notification::title('Hello from NativePHP')
                ->message('This is a detail message coming from your Laravel app.')
                ->show();
            //            Filament::notify('danger', 'sync failed, there are no devices added');

            return;
        }

        $this->getDevices()->where('type', 'zkt')->each(function ($device) {

            $zk = new ZKTeco($device->ip);

            $connection = $zk->connect();

            $device->update(['is_active' => $connection]);

            if (! $connection) {
                Filament::notify('danger', 'unable to connect to device. '.$device->name);

                return;
            }

            collect($zk->getUser())->each(function ($user) {
                User::query()->updateOrCreate([
                    'biometric_id' => $user['userid'],
                ], [
                    'name' => $user['name'] ?? 'not found',
                    'is_admin' => $this->decideIsAdmin($user['role']),
                    'password' => $user['password'] ?? Hash::make(Str::random(10)),
                ]);
            });

        });
        Filament::notify('success', 'Sync Completed');

    }

    protected function decideIsAdmin($role): bool
    {
        if ($role == Util::LEVEL_ADMIN) {
            return true;
        }

        return false;
    }
}
