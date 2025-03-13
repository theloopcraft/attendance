<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\User;
use App\Traits\DeviceTraits;
use Doctrine\Common\Cache\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;

class SyncAttendance extends Action
{
    use DeviceTraits;


    public function handle(): void
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '-1');

        $attendances = Attendance::query()->latest()->first();
        $startAt = Carbon::now()->startOfDay()->toDateTimeString();
        $endAt = Carbon::now()->addDay()->endOfDay()->toDateTimeString();


        if ($attendances) {
            $startAt = Carbon::parse($attendances->action_at)->startOfDay()->toDateTimeString();
            $endAt = Carbon::parse($attendances->action_at)->addDay()->endOfDay()->toDateTimeString();
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
            Log::error('API request failed', $response->json());
            return; // Stop execution if API fails
        }

        $allData = $response->json('data');

//        if (!$allData) {
//
//            $response = Http::baseUrl('http://192.168.1.155')
//                ->timeout(4000)
//                ->withToken('de70f6cb421a5a62a478d448bdddc9a95cacc9ab', 'Token')
//                ->acceptJson()
//                ->get('iclock/api/transactions/', [
//                    'start_time' => Carbon::parse($startAt)->startOfDay()->toDateTimeString(),
//                    'end_time' => Carbon::parse($endAt)->addDays(2)->startOfDay()->toDateTimeString(),,
//                    'page' => 1,
//                    'page_size' => 500,
//                ]);
//
//            if (!$response->successful()) {
//                Log::error('API request failed', $response->json());
//                return;
//            }
//
//            $allData = $response->json('data');
//
//        }

        $maxRetries = 10; // Set a limit to avoid infinite loops
        $retryCount = 0;

        do {
            $page = 1;
            $allData = [];

            do {
                $response = Http::baseUrl('http://192.168.1.155')
                    ->timeout(4000)
                    ->withToken('de70f6cb421a5a62a478d448bdddc9a95cacc9ab', 'Token')
                    ->acceptJson()
                    ->get('iclock/api/transactions/', [
                        'start_time' => Carbon::parse($startAt)->startOfDay()->toDateTimeString(),
                        'end_time' => Carbon::parse($endAt)->endOfDay()->toDateTimeString(),
                        'page' => $page,
                        'page_size' => 500,
                    ]);

                if (!$response->successful()) {
                    Log::error('API request failed', $response->json());
                    break 2; // Exit both loops on failure
                }

                $data = $response->json('data');

                if (!empty($data)) {
                    $allData = array_merge($allData, $data);
                    $page++;
                }

            } while (!empty($data)); // Continue while there's data

            // If data is still empty, adjust start & end time and retry
            if (empty($allData)) {
                $startAt = Carbon::parse($startAt)->addDay()->startOfDay()->toDateTimeString();
                $endAt = Carbon::parse($endAt)->addDay()->endOfDay()->toDateTimeString();
                $retryCount++;
            }

        } while (empty($allData) && $retryCount < $maxRetries);


        // Process attendance data
        collect($allData)->each(function ($attendance) {
            $device = Device::firstOrCreate([
                'name' => $attendance['terminal_alias'] ?? "Manual Entries",
            ], [
                'type' => 'API',
                'timezone' => 'Indian/Maldives',
                'location' => $attendance['area_alias'] ?? 'Unknown',
                'ip' => 'localhost',
                'port' => '0',
                'is_active' => 1
            ]);

            $user = User::firstOrCreate(
                ['biometric_id' => $attendance['emp_code']],
                ['name' => $attendance['first_name']]
            );

            Attendance::firstOrCreate([
                'device_id' => $device->id,
                'user_id' => $user->id,
                'action_at' => $attendance['punch_time'],
            ], [
                'action' => $attendance['punch_state_display']
            ]);
        });

        Log::info("Attendance sync completed.");
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
