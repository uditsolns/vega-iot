<?php

use App\Enums\DeviceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_uid', 50)->unique();
            $table->string('device_code', 20)->unique();
            $table->foreignId('device_model_id')->constrained()->onDelete('restrict');
            $table->string('firmware_version', 20)->nullable();

            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('set null');
            $table->string('device_name', 255)->nullable();

            $table->enum('status', DeviceStatus::values())->default(DeviceStatus::Offline->value);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_reading_at')->nullable();

            $table->timestamps();

            $table->index('device_model_id', 'idx_devices_model');
            $table->index('company_id', 'idx_devices_company');
            $table->index('area_id', 'idx_devices_area');
            $table->index('status', 'idx_devices_status');
            $table->index('device_uid', 'idx_devices_uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
