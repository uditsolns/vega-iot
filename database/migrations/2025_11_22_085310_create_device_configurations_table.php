<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("device_configurations", function (Blueprint $table) {
            $table->id();
            $table->foreignId("device_id")->constrained()->onDelete("cascade");

            // Temperature thresholds (internal sensor)
            $table->decimal("temp_min_critical", 6, 2)->default(20.0);
            $table->decimal("temp_max_critical", 6, 2)->default(50.0);
            $table->decimal("temp_min_warning", 6, 2)->default(25.0);
            $table->decimal("temp_max_warning", 6, 2)->default(45.0);

            // Humidity thresholds
            $table->decimal("humidity_min_critical", 6, 2)->default(40.0);
            $table->decimal("humidity_max_critical", 6, 2)->default(90.0);
            $table->decimal("humidity_min_warning", 6, 2)->default(50.0);
            $table->decimal("humidity_max_warning", 6, 2)->default(80.0);

            // Temperature probe thresholds (for dual_temp types)
            $table->decimal("temp_probe_min_critical", 6, 2)->nullable();
            $table->decimal("temp_probe_max_critical", 6, 2)->nullable();
            $table->decimal("temp_probe_min_warning", 6, 2)->nullable();
            $table->decimal("temp_probe_max_warning", 6, 2)->nullable();

            // Recording intervals (in minutes)
            $table->integer("record_interval")->default(5);
            $table->integer("send_interval")->default(15);

            // WiFi configuration
            $table->string("wifi_ssid", 100)->nullable();
            $table->string("wifi_password", 100)->nullable();

            // Active sensor selection
            $table->string("active_temp_sensor", 10)->default("INT");

            // Tracking
            $table->boolean("is_current")->default(true);
            $table
                ->foreignId("updated_by")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");

            $table->timestamps();

            // Unique constraint with deferred check - only one current config per device
            $table
                ->unique(
                    ["device_id", "is_current"],
                    "uq_device_current_config",
                )
                ->deferrable();

            // Indexes
            $table->index("device_id", "idx_device_configurations_device_id");
        });

        // Create partial index for current configurations (PostgreSQL specific)
        DB::statement(
            "CREATE INDEX idx_device_configurations_is_current ON device_configurations (is_current) WHERE is_current = TRUE",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("device_configurations");
        DB::statement("DROP INDEX idx_device_configurations_is_current");
    }
};
