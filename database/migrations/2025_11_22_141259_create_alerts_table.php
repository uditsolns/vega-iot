<?php

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('device_sensor_id')->constrained('device_sensors')->onDelete('cascade');

            $table->enum('severity', AlertSeverity::values());
            $table->enum('status', AlertStatus::values())->default(AlertStatus::Active->value);

            $table->decimal('trigger_value', 8, 2)->nullable();
            $table->string('threshold_breached', 50)->nullable();
            $table->text('reason')->nullable();

            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            $table->timestampTz('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('acknowledge_comment')->nullable();

            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolve_comment')->nullable();

            $table->boolean('is_back_in_range')->default(false);
            $table->timestampTz('last_notification_at')->nullable();
            $table->integer('notification_count')->default(0);

            $table->timestampTz('created_at')->useCurrent();

            $table->index('device_id', 'idx_alerts_device');
            $table->index('device_sensor_id', 'idx_alerts_sensor');
            $table->index('status', 'idx_alerts_status');
            $table->index('severity', 'idx_alerts_severity');
            $table->index(['device_id', 'status'], 'idx_alerts_device_status');
            $table->index('started_at', 'idx_alerts_started_at');
        });

        \Illuminate\Support\Facades\DB::statement(
            "CREATE INDEX idx_alerts_active ON alerts (status, severity) WHERE status = 'active'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
