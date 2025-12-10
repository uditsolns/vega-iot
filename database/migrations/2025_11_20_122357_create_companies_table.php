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
        Schema::create("companies", function (Blueprint $table) {
            $table->id();
            $table->string("name", 150);
            $table->string("client_name", 150);
            $table->string("email", 255)->unique();
            $table->string("phone", 20)->nullable();
            $table->text("billing_address")->nullable();
            $table->text("shipping_address")->nullable();
            $table->string("gst_number", 20)->nullable();
            $table->boolean("is_active")->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index("email", "idx_companies_email");
            $table->index("is_active", "idx_companies_is_active");
            $table->index("deleted_at", "idx_companies_deleted_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("companies");
    }
};
