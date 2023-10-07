<?php

namespace Database\Seeders;

use App\Models\Timezone;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TimezoneSeeder extends Seeder
{
    public function run()
    {
        collect(CarbonTimeZone::listIdentifiers())->each(
            fn ($time) => Timezone::query()->firstOrCreate(['name' => Str::lower($time)]),
        );
    }
}
