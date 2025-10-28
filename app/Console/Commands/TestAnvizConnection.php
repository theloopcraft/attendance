<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestAnvizConnection extends Command
{
    protected $signature = 'test:anviz-connection';

    protected $description = 'Tests the connection to the Anviz database.';

    public function handle()
    {
        $this->info('Command handle method started.');
        try {
            $this->info('Attempting to get DB connection...');
            $connection = DB::connection('sqlsrv');
            $this->info('Connection object retrieved. Driver: ' . $connection->getDriverName());
            $this->info('Testing simple SELECT @@VERSION ...');
            $version = $connection->select('SELECT @@VERSION as version');
            $this->info('Connected successfully! SQL Server version: ' . $version[0]->version);
            $tables = $connection->select("
                SELECT TABLE_SCHEMA, TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES
                ORDER BY TABLE_SCHEMA, TABLE_NAME
            ");
            $this->info('Listing available tables...');
            foreach ($tables as $table) {
                $fullTable = "{$table->TABLE_SCHEMA}.{$table->TABLE_NAME}";
                $this->line("Checking {$fullTable} ...");
                $query = "SELECT TOP 1 * FROM [{$table->TABLE_SCHEMA}].[{$table->TABLE_NAME}]";
                $data = $connection->select($query);
                $rowCount = is_array($data) ? count($data) : 0;
                $this->info("{$fullTable} → {$rowCount} row(s) fetched");
            }
            $this->info('Attempting to fetch data...');
            $data = $connection->select("SELECT TOP 1 * FROM [dbo].[Checkinout]");
            $this->info('Successfully fetched ' . count($data) . ' records.');

        } catch (\Throwable $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ' on line ' . $e->getLine());
            $this->error('Trace: ' . $e->getTraceAsString());
        }
        $this->info('Command handle method finished.');
        return 0;
    }
}
