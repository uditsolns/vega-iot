<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable TimescaleDB extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Dropping the extension will drop all hypertables
        // Uncomment only if you're sure you want to remove TimescaleDB
        // DB::statement('DROP EXTENSION IF EXISTS timescaledb CASCADE;');
    }
};
