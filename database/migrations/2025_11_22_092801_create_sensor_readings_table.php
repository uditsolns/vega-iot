<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->integer('device_id');
            $table->integer('device_sensor_id');
            $table->timestampTz('recorded_at');
            $table->timestampTz('received_at')->useCurrent();

            $table->integer('company_id');
            $table->integer('area_id')->nullable();

            $table->decimal('value_numeric', 10, 4)->nullable();
            $table->jsonb('metadata')->nullable();

            $table->primary(['device_id', 'device_sensor_id', 'recorded_at']);

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('device_sensor_id')->references('id')->on('device_sensors')->onDelete('cascade');

            $table->index(['company_id', 'recorded_at'], 'idx_sensor_readings_company_time');
            $table->index(['area_id', 'recorded_at'], 'idx_sensor_readings_area_time');
            $table->index(['device_id', 'recorded_at'], 'idx_sensor_readings_device_time');
            $table->index(['device_sensor_id', 'recorded_at'], 'idx_sensor_readings_sensor_time');
        });

        // GPS stored as native PostgreSQL POINT (longitude, latitude)
        DB::statement('ALTER TABLE sensor_readings ADD COLUMN value_point point');

        DB::statement(
            "CREATE INDEX idx_sensor_readings_metadata ON sensor_readings USING GIN(metadata) WHERE metadata IS NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS sensor_readings CASCADE');
    }
};
