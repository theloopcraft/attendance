<?php

namespace App\Traits;

use App\Models\HumanlotClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait HumanlotClientTrait
{
    public function client(): PendingRequest
    {
        $client = $this->currentClient();

        return Http::withHeaders(['x-tenant' => $client->app_id])->withToken($client->secret)
            ->baseUrl(getenv('APP_HUMANLOT'))
            ->asJson();
    }

    protected function currentClient(): Model
    {
        return HumanlotClient::query()->first();
    }
}
