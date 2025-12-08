<?php

namespace App\Actions\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\User;
use App\Models\Setting;
use App\Traits\DeviceTraits;
use Lorisleiva\Actions\Action;

class SyncAnvizAttendance extends Action
{

    use DeviceTraits;

    public function handle()
    {
        $anvizDevices = $this->getAnvizDevices();
        if (!$anvizDevices->count()) {
            return;
        }

        $lastLogid = Setting::query()->where('key', 'LastSyncedRecordID')->first()->value ?? 0;
        $perPage = Setting::query()->where('key', 'SyncAnvizAttendancePerPage')->first()->value ?? 30;
        // get only 30 logs from lastLogid
        // $logs = DB::connection('anviz')->select("
        //     SELECT TOP {$perPage}
        //         c.Logid,
        //         c.CheckTime,
        //         c.CheckType,
        //         c.Sensorid,
        //         f.ClientName AS DeviceName,
        //         f.IPaddress AS DeviceIP,
        //         u.Name AS UserName,
        //         u.Userid AS Userid
        //     FROM [dbo].[Checkinout] AS c
        //     LEFT JOIN [dbo].[Userinfo] AS u
        //         ON c.Userid = u.Userid
        //     LEFT JOIN [dbo].[FingerClient] AS f
        //         ON c.Sensorid = f.Clientid
        //     WHERE c.Logid > ?
        //     ORDER BY c.Logid ASC
        // ", [$lastLogid]);

        $logs = DB::connection('anviz')->select("
            SELECT TOP {$perPage}
                c.Logid,
                c.CheckTime,
                c.CheckType,
                c.Clientid AS Sensorid,
                c.ClientName AS DeviceName,
                f.IPaddress AS DeviceIP,
                c.Name AS UserName,
                u.Userid AS Userid
            FROM [dbo].[V_Record] AS c
            LEFT JOIN [dbo].[Userinfo] AS u
                ON c.Userid = u.Userid
            LEFT JOIN [dbo].[FingerClient] AS f
                ON c.Sensorid = f.Clientid
            WHERE c.Logid > ?
            ORDER BY c.Logid ASC
        ", [$lastLogid]);

        collect($logs)->each(function ($log) {

            $user = $this->getOrCreateUser($log);
            $device = $this->getOrCreateDevice($log);

            $action = match ((int) $log->CheckType) {
                0, 3, 4 => 'Check-in',
                1, 2, 5 => 'Check-out',
                default => 0,
            };

            if ($action) {
                Attendance::query()->firstOrCreate(
                    [
                        'device_id' => $device->id,
                        'user_id' => $user->id,
                        'action_at' => Carbon::parse($log->CheckTime, $device->timezone)->toDateTimeString(),
                    ],
                    ['action' => $action]
                );
            }
        });

        Setting::query()->updateOrCreate(
            ['key' => 'LastSyncedRecordID'],
            ['value' => collect($logs)->last()->Logid]
        );
    }

    protected function getOrCreateUser($log)
    {
        $user = User::query()->firstOrCreate(
            ['biometric_id' => $log->Userid],
            ['name' => $log->UserName]
        );

        if ($user->name != $log->UserName) {
            $user->name = $log->UserName;
            $user->save();
        }

        return $user;
    }

    protected function getOrCreateDevice($log)
    {
        return Device::query()->firstOrCreate(
            [
                'name' => $log->DeviceName,
                'ip' => $log->DeviceIP,
            ],
            [
                'type' => 'anviz',
                'is_active' => true,
                'version' => 1,
                'timezone' => "indian/maldives",
            ]
        );
    }
}
