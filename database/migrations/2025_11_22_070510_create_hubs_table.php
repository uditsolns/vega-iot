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
        Schema::create("hubs", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("location_id")
                ->constrained()
                ->onDelete("cascade");
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->boolean("is_active")->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint
            $table->unique(["location_id", "name"], "uq_location_hub");

            // Indexes
            $table->index("location_id", "idx_hubs_location_id");
            $table->index("deleted_at", "idx_hubs_deleted_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("hubs");
    }
};
