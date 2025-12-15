<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Rats\Zkteco\Lib\ZKTeco;

class DailyUpdateCommand extends Command
{
    protected $signature = 'daily:update-software';

    protected $description = 'Command description';

    public function handle(): void
    {
        exec('git pull');
        exec('composer install');
        $this->LogWithTime("composer updated running");

        // exec('npm install');
        // $this->LogWithTime("npm install running");

        // exec('npm run build');
        // $this->LogWithTime("npm build running");
    }

    public function LogWithTime($message)
    {
        $time = now()->timezone('Indian/Maldives')->format('D, d M Y H:i');
        Log::alert($message);
    }
}
