<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreshMigrateCommand extends Command
{
    protected $signature = 'migrate:fresh-timescale {--seed : Seed the database after migration}';
    protected $description = 'Drop all tables including TimescaleDB hypertables and re-run migrations';

    public function handle(): int
    {
        $this->info('Dropping all tables including hypertables...');

        try {
            // Drop hypertables first (TimescaleDB requires CASCADE)
            DB::statement('DROP TABLE IF EXISTS sensor_readings CASCADE;');
            $this->info('Dropped hypertable: sensor_readings');

            $tables = DB::select("
                SELECT tablename FROM pg_tables
                WHERE schemaname = 'public' AND tablename != 'migrations'
            ");

            foreach ($tables as $table) {
                DB::statement("DROP TABLE IF EXISTS {$table->tablename} CASCADE");
            }

            DB::statement('DROP TABLE IF EXISTS migrations CASCADE');
            $this->info('All tables dropped successfully.');

            $this->call('migrate');

            if ($this->option('seed')) {
                $this->call('db:seed');
            }

            $this->info('Database refreshed successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
