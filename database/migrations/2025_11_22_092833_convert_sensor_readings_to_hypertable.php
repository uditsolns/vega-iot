<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            SELECT create_hypertable(
                'sensor_readings',
                'recorded_at',
                chunk_time_interval => INTERVAL '7 days',
                if_not_exists => TRUE
            );
        ");

        DB::statement("
            ALTER TABLE sensor_readings SET (
                timescaledb.compress,
                timescaledb.compress_segmentby = 'device_id, device_sensor_id',
                timescaledb.compress_orderby = 'recorded_at DESC'
            );
        ");

        DB::statement("SELECT add_compression_policy('sensor_readings', INTERVAL '7 days');");
        DB::statement("SELECT add_retention_policy('sensor_readings', INTERVAL '1 year');");
    }

    public function down(): void
    {
        DB::statement("SELECT remove_compression_policy('sensor_readings', if_exists => true);");
        DB::statement("SELECT remove_retention_policy('sensor_readings', if_exists => true);");
        DB::statement('DROP TABLE IF EXISTS sensor_readings CASCADE;');
    }
};
