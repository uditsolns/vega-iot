<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("device_readings", function (Blueprint $table) {
            // Primary key columns
            $table->integer("device_id");
            $table->timestamp("recorded_at");
            $table->timestamp("received_at")->useCurrent();

            // Denormalized hierarchy IDs for fast queries
            $table->integer("company_id");
            $table->integer("location_id")->nullable();
            $table->integer("hub_id")->nullable();
            $table->integer("area_id")->nullable();

            // Sensor values
            $table->decimal("temperature", 6, 2)->nullable();
            $table->decimal("humidity", 6, 2)->nullable();
            $table->decimal("temp_probe", 6, 2)->nullable();

            // Device metadata at time of reading
            $table->decimal("battery_voltage", 4, 2)->nullable();
            $table->smallInteger("battery_percentage")->nullable();
            $table->integer("wifi_signal_strength")->nullable();

            // Operational metadata
            $table->string("firmware_version", 20)->nullable();

            // Raw data reference (optional, for debugging)
            $table->jsonb("raw_payload")->nullable();

            // Composite primary key
            $table->primary(["device_id", "recorded_at"]);

            // Foreign key only on device_id (other IDs denormalized)
            $table
                ->foreign("device_id")
                ->references("id")
                ->on("devices")
                ->onDelete("cascade");

            // Indexes for common query patterns (time-first queries)
            $table->index(
                ["device_id", "recorded_at"],
                "idx_readings_device_time",
            );
            $table->index(
                ["company_id", "recorded_at"],
                "idx_readings_company_time",
            );
            $table->index(
                ["location_id", "recorded_at"],
                "idx_readings_location_time",
            );
            $table->index(["hub_id", "recorded_at"], "idx_readings_hub_time");
            $table->index(["area_id", "recorded_at"], "idx_readings_area_time");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // TimescaleDB hypertables must be dropped separately
        // Cannot use Schema::dropIfExists() as it gets batched with other tables
        DB::statement("DROP TABLE IF EXISTS device_readings CASCADE");
    }
};
