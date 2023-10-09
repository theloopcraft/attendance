<?php

namespace App\Console\Commands;

use App\Actions\User\SyncUserFromDevice;
use Illuminate\Console\Command;

class HumanlotUserSyncCommand extends Command
{
    protected $signature = 'humanlot-user:sync';

    protected $description = 'This sync the users from machine to local DB';

    public function handle()
    {
        SyncUserFromDevice::dispatch();
    }
}
