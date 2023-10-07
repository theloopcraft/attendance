<?php

use App\Models\Timezone;
use App\Models\User;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        User::query()->create([
            'email' => 'admin@admin.com',
            'name' => 'Admin',
            'is_admin' => 1,
            'password' => Hash::make('password'),
        ]);

        collect(CarbonTimeZone::listIdentifiers())->each(
            fn($time) => Timezone::query()->firstOrCreate(['name' => Str::lower($time)]),
        );
    }

};
