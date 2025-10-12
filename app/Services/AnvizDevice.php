<?php

namespace App\Services;

use App\Models\Device;
use App\Services\Contract\DeviceDataContract;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;

class AnvizDevice implements DeviceDataContract
{
    public function __construct(protected Device $device)
    {
        return $this;
    }

    protected function baseUrl(): PendingRequest
    {
        $ip = $this->device->ip;

        return Http::baseUrl("http://$ip/goform");
    }

    public function login()
    {
        $response = $this->baseUrl()->get('/chklogin', [
            'userid' => $this->device->user,
            'password' => $this->device->password,
        ]);

        return json_decode(preg_replace('/(\w+):/', '"$1":', strip_tags($response)), true);
    }

    protected function formatResponse($response)
    {
        return json_decode(preg_replace('/([{,])(\s*)([a-zA-Z0-9_]+?)\s*:/', '$1"$3":', strip_tags($response)), true);
    }

    public function getUsers(): Collection
    {
        $data = $this->login();
        $response = $this->baseUrl()->get('userlist', [
            'start' => 0,
            'limit' => 20,
            'session_key' => $data['session_key'],
        ]);

        return collect($this->formatResponse($response));
    }

    public function getAttendances(?string $startDate = null, ?string $endDate = null): Collection
    {
        $data = $this->login();

        if (! $startDate) {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        }

        $response = $this->baseUrl()->get('/searchrecord', [
            'limit' => 10000,
            'session_key' => $data['session_key'],
            'from' => $startDate,
            'to' => $endDate,
        ])->throw();

        return collect(collect($this->formatResponse($response))->get('record'))
            ->map(fn ($record) => new Fluent([
                ...$record,
                'action_at' => $record['time'],
                'action' => match ($record['status']) {
                    'IN' => 'Check-in',
                    'OUT' => 'Check-out',
                    default => 'Undefined',
                },
            ]),
            );

    }
}
