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

            // Sent timestamp
            $table->timestampTz("sent_at")->nullable();

            // Delivery status
            $table->boolean("is_delivered")->default(false);
            $table->timestampTz("delivered_at")->nullable();
            $table->text("delivery_error")->nullable();

            // Message details
            $table->text("message_content")->nullable();
            $table->string("external_reference", 255)->nullable();

            // Indexes
            $table->index("alert_id");
            $table->index("user_id");
            $table->index("sent_at");
            $table->index(["alert_id", "channel"]);
            $table->index(["is_delivered", "sent_at"]);
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
