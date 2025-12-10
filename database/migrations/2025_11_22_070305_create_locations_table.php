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
        Schema::create("locations", function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained()->onDelete("cascade");
            $table->string("name", 255);
            $table->text("address")->nullable();
            $table->string("city", 100)->nullable();
            $table->string("state", 100)->nullable();
            $table->string("country", 100)->default("India");
            $table->string("timezone", 50)->default("Asia/Kolkata");
            $table->boolean("is_active")->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint
            $table->unique(["company_id", "name"], "uq_company_location");

            // Indexes
            $table->index("company_id", "idx_locations_company_id");
            $table->index("deleted_at", "idx_locations_deleted_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("locations");
    }
};
