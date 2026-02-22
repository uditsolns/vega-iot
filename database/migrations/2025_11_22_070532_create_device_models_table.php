<?php

use App\Enums\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_models', function (Blueprint $table) {
            $table->id();
            $table->enum('vendor', Vendor::values());
            $table->string('model_name', 100);
            $table->text('description')->nullable();
            $table->integer('max_slots');
            $table->boolean('is_configurable')->default(false);
            $table->jsonb('data_format')->nullable();
            $table->timestamps();

            $table->unique(['vendor', 'model_name']);
            $table->index('vendor', 'idx_device_models_vendor');
            $table->index('is_configurable', 'idx_device_models_configurable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_models');
    }
};
