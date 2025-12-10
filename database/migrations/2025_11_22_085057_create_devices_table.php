<?php

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("devices", function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string("device_uid", 50)->unique();
            $table->string("device_code", 20)->unique();
            $table->string("make", 50)->default("VEGA");
            $table->string("model", 50)->default("Alpha");
            $table->enum("type", DeviceType::values());
            $table->string("firmware_version", 50)->nullable();

            // API Authentication - api_key generated in Model boot method
            $table->string("api_key", 64)->unique();

            // Resolution and accuracy specs
            $table->decimal("temp_resolution", 4, 2)->default(0.1);
            $table->decimal("temp_accuracy", 4, 2)->default(0.5);
            $table->decimal("humidity_resolution", 4, 2)->default(1.0);
            $table->decimal("humidity_accuracy", 4, 2)->default(3.0);
            $table->decimal("temp_probe_resolution", 4, 2)->nullable();
            $table->decimal("temp_probe_accuracy", 4, 2)->nullable();

            // Assignment (two-step: company first, then area)
            $table
                ->foreignId("company_id")
                ->nullable()
                ->constrained()
                ->onDelete("set null");
            $table
                ->foreignId("area_id")
                ->nullable()
                ->constrained()
                ->onDelete("set null");
            $table->string("device_name")->nullable();

            // Status tracking
            $table
                ->enum("status", DeviceStatus::values())
                ->default(DeviceStatus::Offline->value);
            $table->boolean("is_active")->default(true);
            $table->timestamp("last_reading_at")->nullable();

            $table->timestamps();

            // Indexes
            $table->index("device_uid", "idx_devices_device_uid");
            $table->index("device_code", "idx_devices_device_code");
            $table->index("api_key", "idx_devices_api_key");
            $table->index("company_id", "idx_devices_company_id");
            $table->index("area_id", "idx_devices_area_id");
            $table->index("status", "idx_devices_status");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("devices");
    }
};
