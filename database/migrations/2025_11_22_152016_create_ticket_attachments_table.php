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
        Schema::create("ticket_attachments", function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId("ticket_id")->constrained()->onDelete("cascade");
            $table
                ->foreignId("comment_id")
                ->nullable()
                ->constrained("ticket_comments")
                ->onDelete("cascade");
            $table
                ->foreignId("uploaded_by")
                ->constrained("users")
                ->onDelete("cascade");

            // File details
            $table->string("file_name");
            $table->string("file_path");
            $table->string("file_type")->nullable();
            $table->unsignedBigInteger("file_size")->default(0);

            // Timestamps
            $table->timestamp("uploaded_at")->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index("ticket_id", "idx_ticket_attachments_ticket_id");
            $table->index("comment_id", "idx_ticket_attachments_comment_id");
            $table->index("uploaded_by", "idx_ticket_attachments_uploaded_by");
            $table->index("uploaded_at", "idx_ticket_attachments_uploaded_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("ticket_attachments");
    }
};
