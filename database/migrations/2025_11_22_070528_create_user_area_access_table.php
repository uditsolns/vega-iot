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
        Schema::create("user_area_access", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->foreignId("area_id")->constrained()->onDelete("cascade");
            $table->timestamp("granted_at")->useCurrent();
            $table->foreignId("granted_by")->nullable()->constrained("users");

            // Unique constraint
            $table->unique(["user_id", "area_id"], "uq_user_area");

            // Indexes
            $table->index("user_id", "idx_user_area_access_user_id");
            $table->index("area_id", "idx_user_area_access_area_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("user_area_access");
    }
};
