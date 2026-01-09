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
        Schema::create("alert_notifications", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("alert_id")
                ->constrained("alerts")
                ->onDelete("cascade");
            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->onDelete("cascade");

            // Channel type (email, sms, voice, push)
            $table->string("channel", 20);

            // Notification status (pending, sent, failed)
            $table
                ->enum("status", ["pending", "sent", "failed"])
                ->default("pending");

            // Event type (triggered, acknowledged, resolved, back_in_range)
            $table->string("event", 50);

            // Timestamps
            $table->timestampTz("queued_at");
            $table->timestampTz("sent_at")->nullable();
            $table->timestampTz("failed_at")->nullable();

            // Retry tracking
            $table->unsignedTinyInteger("retry_count")->default(0);

            // Message details
            $table->text("message_content")->nullable();
            $table->string("external_reference", 255)->nullable();
            $table->text("error_message")->nullable();

            // Indexes
            $table->index("alert_id");
            $table->index("user_id");
            $table->index("status");
            $table->index(["alert_id", "status"]);
            $table->index(["status", "queued_at"]);
            $table->index(["alert_id", "channel"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("alert_notifications");
    }
};
