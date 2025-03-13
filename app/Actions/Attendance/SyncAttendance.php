<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\User;
use App\Traits\DeviceTraits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;

class SyncAttendance extends Action
{
    use DeviceTraits;

    private string $apiBaseUrl = 'http://192.168.1.155';
    private string $apiToken = 'de70f6cb421a5a62a478d448bdddc9a95cacc9ab';
    private int $timeout = 4000;
    private int $pageSize = 500;
    private int $maxRetries = 5;

    public function handle(): void
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '-1');

        $this->syncAttendanceData();
    }

    private function syncAttendanceData(): void
    {
        $lastAttendance = Attendance::query()->latest()->first();
        $startAt = $lastAttendance ? Carbon::parse($lastAttendance->action_at) : Carbon::now()->startOfMonth()->subDay()->startOfDay();
        $endAt = $startAt->copy()->addDay()->endOfDay();


        $retryCount = 0;

        while ($retryCount < $this->maxRetries) {

            $allData = $this->fetchAttendanceData($startAt, $endAt);
            

            if (!empty($allData)) {
                $this->processAttendanceData($allData);
                Log::info("Attendance sync completed for period: $startAt to $endAt.");
                return;
            }

            $startAt = $startAt->copy()->addDay()->startOfDay();
            $endAt = $startAt->copy()->addDay()->endOfDay();

            dump($startAt, $endAt);

            $retryCount++;
        }

        Log::error("Max retries reached. No attendance data found.");
    }

    private function fetchAttendanceData(Carbon $startAt, Carbon $endAt): array
    {

        $response = Http::baseUrl($this->apiBaseUrl)
            ->timeout($this->timeout)
            ->withToken($this->apiToken, 'Token')
            ->acceptJson()
            ->get('iclock/api/transactions/', [
                'start_time' => $startAt->toDateTimeString(),
                'end_time' => $endAt->toDateTimeString(),
                'page' => 1,
                'page_size' => $this->pageSize,
            ]);

            if (!$response->successful()) {
                Log::error('API request failed', ['response' => $response->json()]);
                return [];
            }

        return $response->json('data');
    }

    private function processAttendanceData(array $attendances): void
    {
        collect($attendances)->each(function ($attendance) {
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
                'action' => $this->parseAction($attendance['punch_state_display'])
            ]);
        });
    }

    private function parseAction(string $action): string
    {
        if (str_contains(strtolower($action), 'in')) {
            return 'Check-in';
        } elseif (str_contains(strtolower($action), 'out')) {
            return 'Check-out';
        }
        return 'Undefined';
    }
}
