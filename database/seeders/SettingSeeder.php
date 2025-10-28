<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'LastSyncedRecordID'],
            ['value' => '1']
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'SyncAnvizAttendancePerPage'],
            ['value' => '30']
        );
    }
}
