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
        Schema::create("ticket_comments", function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId("ticket_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");

            // Comment details
            $table->text("comment");
            $table->boolean("is_internal")->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index("ticket_id", "idx_ticket_comments_ticket_id");
            $table->index("user_id", "idx_ticket_comments_user_id");
            $table->index("is_internal", "idx_ticket_comments_is_internal");
            $table->index("created_at", "idx_ticket_comments_created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("ticket_comments");
    }
};
