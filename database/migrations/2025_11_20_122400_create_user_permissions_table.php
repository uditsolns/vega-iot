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
        Schema::create("user_permissions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table
                ->foreignId("permission_id")
                ->constrained()
                ->onDelete("cascade");
            $table->timestamp("granted_at")->useCurrent();
            $table->foreignId("granted_by")->nullable()->constrained("users");

            // Unique constraint
            $table->unique(["user_id", "permission_id"], "uq_user_permission");

            // Index
            $table->index("user_id", "idx_user_permissions_user_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("user_permissions");
    }
};
