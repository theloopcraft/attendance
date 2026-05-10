<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\User;
use App\Traits\DeviceTraits;
use Illuminate\Support\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;

class SyncAttendance extends Action
{
    use DeviceTraits;

    private string $apiBaseUrl;
    private string $apiToken;
    private int $timeout = 4000;
    private int $pageSize = 500;
    private int $maxRetries = 5;

    public function __construct()
    {
        $this->apiBaseUrl = (string) env('ATTENDANCE_API_BASE_URL', '');
        $this->apiToken = (string) env('ATTENDANCE_API_TOKEN', '');
    }

    public function handle(): void
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '-1');

        if ($this->apiBaseUrl === '' || $this->apiToken === '') {
            Log::error('Attendance API configuration missing', [
                'has_base_url' => $this->apiBaseUrl !== '',
                'has_token' => $this->apiToken !== '',
            ]);
            return;
        }

        $this->syncAttendanceData();
    }

    private function syncAttendanceData(): void
    {
        $lastAttendance = Attendance::query()->latest()->first();
        $startAt = $lastAttendance ? Carbon::parse($lastAttendance->action_at)->subDay() : Carbon::now()->subMonth()->startOfMonth();
        $endAt = now()->copy()->addDay()->endOfDay();


        $retryCount = 0;


        while ($retryCount < $this->maxRetries) {

            $allData = $this->fetchAttendanceData($startAt, $endAt);

            if (!empty($allData)) {
                $this->processAttendanceData($allData);
                Log::info("Attendance sync completed for period: $startAt to $endAt.");
            }

            $startAt = $endAt->copy()->addDay()->startOfDay();
            $endAt = $endAt->copy()->addDay()->endOfDay();

            $retryCount  = $retryCount + 1;
        }

        Log::error("Max retries reached. No attendance data found.");
    }

    private function fetchAttendanceData(Carbon $startAt, Carbon $endAt): array
    {

        try {
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
                Log::error('Attendance API request failed', [
                    'url' => rtrim($this->apiBaseUrl, '/').'/iclock/api/transactions/',
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json('data');

            if (!is_array($data)) {
                Log::error('Attendance API response missing data array', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            return $data;
        } catch (RequestException $exception) {
            Log::error('Attendance API request exception', [
                'message' => $exception->getMessage(),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Unexpected error while syncing attendance', [
                'message' => $throwable->getMessage(),
            ]);
        }

        return [];
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
