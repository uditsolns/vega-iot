<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Convert table to TimescaleDB hypertable
        DB::statement("
            SELECT create_hypertable(
                'device_readings',
                'recorded_at',
                chunk_time_interval => INTERVAL '7 days',
                if_not_exists => TRUE
            );
        ");

        // Step 2: Enable compression
        DB::statement("
            ALTER TABLE device_readings SET (
                timescaledb.compress,
                timescaledb.compress_segmentby = 'device_id',
                timescaledb.compress_orderby = 'recorded_at DESC'
            );
        ");

        // Step 3: Add compression policy (compress chunks older than 7 days)
        DB::statement("
            SELECT add_compression_policy('device_readings', INTERVAL '7 days');
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove compression policy
        DB::statement("
            SELECT remove_compression_policy('device_readings', if_exists => true);
        ");

        // Note: Reverting a hypertable to regular table is destructive
        // This is optional and would require recreating the table
        // Uncomment only if you want to fully revert (will lose data)

        DB::statement("DROP TABLE IF EXISTS device_readings CASCADE;");
    }
};
