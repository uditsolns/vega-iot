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
        Schema::table("roles", function (Blueprint $table) {
            // Add foreign key constraint to companies table
            $table
                ->foreign("company_id")
                ->references("id")
                ->on("companies")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("roles", function (Blueprint $table) {
            $table->dropForeign(["company_id"]);
        });
    }
};
