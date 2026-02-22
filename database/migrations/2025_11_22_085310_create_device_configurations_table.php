<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');

            $table->integer('recording_interval')->default(5);
            $table->integer('sending_interval')->default(5);
            $table->string('wifi_ssid', 100)->nullable();
            $table->text('wifi_password')->nullable();
            $table->string('wifi_mode', 20)->default('WPA2');
            $table->integer('timezone_offset_minutes')->default(0);

            $table->timestampTz('effective_from')->useCurrent();
            $table->timestampTz('effective_to')->nullable();
            $table->timestampTz('last_synced_at')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('device_id', 'idx_device_configs_device');
            $table->index(['device_id', 'effective_from', 'effective_to'], 'idx_device_configs_effective');
        });

        DB::statement(
            "CREATE INDEX idx_device_configs_current ON device_configurations (device_id) WHERE effective_to IS NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('device_configurations');
    }
};
