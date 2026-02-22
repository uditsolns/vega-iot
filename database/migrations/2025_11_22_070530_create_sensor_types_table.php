<?php

use App\Enums\SensorDataType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensor_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('unit', 20)->nullable();
            $table->enum('data_type', SensorDataType::values())
                ->default(SensorDataType::Decimal->value);
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->boolean('supports_threshold_config')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('name', 'idx_sensor_types_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_types');
    }
};
