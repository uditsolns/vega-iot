<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->integer('slot_number');
            $table->foreignId('sensor_type_id')->constrained()->onDelete('restrict');
            $table->boolean('is_enabled')->default(true);
            $table->string('label', 100)->nullable();
            $table->string('accuracy', 50)->nullable();
            $table->string('resolution', 50)->nullable();
            $table->string('measurement_range', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['device_id', 'slot_number']);
            $table->index('device_id', 'idx_device_sensors_device');
            $table->index('sensor_type_id', 'idx_device_sensors_type');
            $table->index(['device_id', 'is_enabled'], 'idx_device_sensors_device_enabled');
        });

        \Illuminate\Support\Facades\DB::statement(
            "CREATE INDEX idx_device_sensors_enabled ON device_sensors (is_enabled) WHERE is_enabled = TRUE"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sensors');
    }
};
