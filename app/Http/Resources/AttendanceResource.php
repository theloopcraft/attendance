<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Attendance */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_at' => $this->action_at,
            'sync_at' => $this->sync_at,
            'device' => $this->device->name,
            'device_ip' => $this->device->ip,
        ];
    }
}
