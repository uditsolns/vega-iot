<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensor_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_sensor_id')->constrained()->onDelete('cascade');
            $table->decimal('min_critical', 8, 2)->nullable();
            $table->decimal('max_critical', 8, 2)->nullable();
            $table->decimal('min_warning', 8, 2)->nullable();
            $table->decimal('max_warning', 8, 2)->nullable();
            $table->timestampTz('effective_from')->useCurrent();
            $table->timestampTz('effective_to')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('device_sensor_id', 'idx_sensor_configs_sensor');
            $table->index(['device_sensor_id', 'effective_from', 'effective_to'], 'idx_sensor_configs_effective');
        });

        DB::statement(
            "CREATE INDEX idx_sensor_configs_current ON sensor_configurations (device_sensor_id) WHERE effective_to IS NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_configurations');
    }
};
