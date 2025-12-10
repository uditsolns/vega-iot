<?php

use App\Enums\AlertSensorType;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("alerts", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("device_id")
                ->constrained("devices")
                ->onDelete("cascade");

            // Alert classification
            $table->enum("type", AlertSensorType::values());
            $table->enum("severity", AlertSeverity::values());
            $table
                ->enum("status", AlertStatus::values())
                ->default(AlertStatus::Active->value);

            // Alert details
            $table->decimal("trigger_value", 8, 2);
            $table->string("threshold_breached", 50)->nullable();
            $table->text("reason")->nullable();

            // Timestamps
            $table->timestampTz("started_at");
            $table->timestampTz("ended_at")->nullable();

            // Acknowledgment
            $table->timestampTz("acknowledged_at")->nullable();
            $table
                ->foreignId("acknowledged_by")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");
            $table->text("acknowledge_comment")->nullable();

            // Resolution
            $table->timestampTz("resolved_at")->nullable();
            $table
                ->foreignId("resolved_by")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");
            $table->text("resolve_comment")->nullable();

            // Duration in seconds (calculated on resolution)
            $table->integer("duration_seconds")->nullable();

            // Back in range flag
            $table->boolean("is_back_in_range")->default(false);

            // Notification tracking
            $table->timestampTz("last_notification_at")->nullable();
            $table->integer("notification_count")->default(0);

            $table->timestampTz("created_at")->nullable();

            // Indexes for performance
            $table->index("device_id");
            $table->index("status");
            $table->index("started_at");
            $table->index(["device_id", "status"]);
            $table->index(["device_id", "type", "severity"]);
            $table->index(["status", "last_notification_at"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("alerts");
    }
};
