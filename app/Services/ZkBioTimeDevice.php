<?php

namespace App\Services;

use App\Models\Device;
use App\Services\Contract\DeviceDataContract;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;

class ZkBioTimeDevice implements DeviceDataContract
{
    public function __construct(protected Device $device)
    {
    }

    protected function baseUrl(): PendingRequest
    {
        $ip = $this->device->ip;
        $port = $this->device->port;

        return Http::baseUrl("http://$ip:$port")
            ->contentType('application/json')
            ->accept('application/json');
    }

    public function getToken(): string
    {
        $response = $this->baseUrl()->post('/api-token-auth/', [
            'username' => $this->device->user,
            'password' => $this->device->password,
        ])->throw();

        return $response->json('token');
    }

    protected function authenticated(): PendingRequest
    {
        return $this->baseUrl()->withToken($this->getToken(), 'Token');
    }

    public function getUsers(): Collection
    {
        $users = collect();
        $page = 1;

        do {
            $response = $this->authenticated()
                ->get('/personnel/api/employees/', ['page' => $page, 'limit' => 100])
                ->throw()
                ->json();

            $users = $users->merge($response['data'] ?? []);
            $page++;
        } while (! empty($response['next']));

        return $users;
    }

    public function getAttendances(?string $startDate = null, ?string $endDate = null): Collection
    {
        $params = ['limit' => 100];

        $startTime = $startDate
            ? $startDate . ' 00:00:00'
            : ($this->device->last_synced_at?->format('Y-m-d H:i:s'));

        if ($startTime) {
            $params['start_time'] = $startTime;
        }

        if ($endDate) {
            $params['end_time'] = $endDate . ' 23:59:59';
        }

        $records = collect();
        $page = 1;

        do {
            $response = $this->authenticated()
                ->get('/iclock/api/transactions/', array_merge($params, ['page' => $page]))
                ->throw()
                ->json();

            $data = $response['data'] ?? [];

            $records = $records->merge(
                collect($data)->map(fn ($record) => new Fluent([
                    ...$record,
                    'action_at' => $record['punch_time'],
                    'action' => match ($record['punch_state']) {
                        '0' => 'Check-in',
                        '1' => 'Check-out',
                        '4' => 'Check-in',  // OT-in
                        '5' => 'Check-out', // OT-out
                        default => 'Undefined',
                    },
                    'biometric_id' => $record['emp_code'],
                ]))
            );

            $page++;
        } while (! empty($response['next']));

        return $records;
    }
}
