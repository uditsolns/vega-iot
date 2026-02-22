<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_model_sensor_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained()->onDelete('cascade');
            $table->integer('slot_number');
            $table->foreignId('fixed_sensor_type_id')->nullable()->constrained('sensor_types')->onDelete('restrict');
            $table->string('label', 100)->nullable();
            $table->string('accuracy', 50)->nullable();
            $table->string('resolution', 50)->nullable();
            $table->string('measurement_range', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['device_model_id', 'slot_number']);
            $table->index('device_model_id', 'idx_model_sensor_slots_model');
            $table->index('fixed_sensor_type_id', 'idx_model_sensor_slots_sensor_type');
        });

        Schema::create('device_model_available_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained()->onDelete('cascade');
            $table->foreignId('sensor_type_id')->constrained()->onDelete('cascade');

            $table->unique(['device_model_id', 'sensor_type_id']);
            $table->index('device_model_id', 'idx_model_available_sensors_model');
            $table->index('sensor_type_id', 'idx_model_available_sensors_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_model_available_sensors');
        Schema::dropIfExists('device_model_sensor_slots');
    }
};
