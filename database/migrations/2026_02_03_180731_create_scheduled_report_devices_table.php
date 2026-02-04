<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_report_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('scheduled_report_id')->constrained('scheduled_reports');
            $table->foreignId('device_id')->constrained('devices');
            $table->timestamp('created_at');

            $table->unique(['scheduled_report_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_report_devices');
    }
};
