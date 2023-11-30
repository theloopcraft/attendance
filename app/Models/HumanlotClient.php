<?php

namespace App\Models;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;

class HumanlotClient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'app_id',
        'secret',
        'status',
        'base_url',
    ];

    public function validateToken(): PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::baseUrl($this->base_url)
            ->withHeaders(['x-tenant' => $this->app_id])
            ->withToken($this->secret)
            ->post('/integerations/validate_token');
    }
}
