<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("tickets", function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->foreignId("company_id")->constrained()->onDelete("cascade");
            $table->foreignId("assigned_to")->nullable()->constrained("users")->onDelete("set null");

            // Optional related entities
            $table->foreignId("device_id")->nullable()->constrained()->onDelete("set null");
            $table->foreignId("location_id")->nullable()->constrained()->onDelete("set null");
            $table->foreignId("hub_id")->nullable()->constrained()->onDelete("set null");
            $table->foreignId("area_id")->nullable()->constrained()->onDelete("set null");

            // Ticket details
            $table->string("subject");
            $table->text("description");
            $table->string("reason")->nullable();
            $table->enum("status", TicketStatus::values())
                ->default(TicketStatus::Open->value);
            $table->enum("priority", TicketPriority::values())
                ->default(TicketPriority::Medium->value);

            // Timestamps
            $table->timestamp("resolved_at")->nullable();
            $table->timestamp("closed_at")->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index("user_id", "idx_tickets_user_id");
            $table->index("company_id", "idx_tickets_company_id");
            $table->index("assigned_to", "idx_tickets_assigned_to");
            $table->index("status", "idx_tickets_status");
            $table->index("priority", "idx_tickets_priority");
            $table->index("created_at", "idx_tickets_created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("tickets");
    }
};
