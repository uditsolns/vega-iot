<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreshMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:fresh-timescale {--seed : Seed the database after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables including TimescaleDB hypertables and re-run migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dropping all tables including hypertables...');

        try {
            // First, drop the hypertable explicitly
            DB::statement('DROP TABLE IF EXISTS device_readings CASCADE;');
            $this->info('Dropped hypertable: device_readings');

            // Get all remaining tables
            $tables = DB::select("
                SELECT tablename
                FROM pg_tables
                WHERE schemaname = 'public'
                AND tablename != 'migrations'
            ");

            // Drop remaining tables
            foreach ($tables as $table) {
                DB::statement("DROP TABLE IF EXISTS {$table->tablename} CASCADE");
            }

            // Drop migrations table last
            DB::statement('DROP TABLE IF EXISTS migrations CASCADE');

            $this->info('All tables dropped successfully.');

            // Run migrations
            $this->info('Running migrations...');
            $this->call('migrate');

            // Seed if requested
            if ($this->option('seed')) {
                $this->info('Seeding database...');
                $this->call('db:seed');
            }

            $this->info('Database refreshed successfully!');

            return $this::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return $this::FAILURE;
        }
    }
}
