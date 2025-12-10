<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("areas", function (Blueprint $table) {
            $table->id();
            $table->foreignId("hub_id")->constrained()->onDelete("cascade");
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->boolean("is_active")->default(true);
            $table->softDeletes();

            // Alert channel configuration for this area
            $table->boolean("alert_email_enabled")->default(true);
            $table->boolean("alert_sms_enabled")->default(false);
            $table->boolean("alert_voice_enabled")->default(false);
            $table->boolean("alert_push_enabled")->default(false);

            // Notification types enabled
            $table->boolean("alert_warning_enabled")->default(true);
            $table->boolean("alert_critical_enabled")->default(true);
            $table->boolean("alert_back_in_range_enabled")->default(true);
            $table->boolean("alert_device_status_enabled")->default(true);

            // Notification interval for acknowledged alerts (in hours)
            $table
                ->integer("acknowledged_alert_notification_interval")
                ->default(24);

            $table->timestamps();

            // Unique constraint
            $table->unique(["hub_id", "name"], "uq_hub_area");

            // Indexes
            $table->index("hub_id", "idx_areas_hub_id");
            $table->index("deleted_at", "idx_areas_deleted_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("areas");
    }
};
