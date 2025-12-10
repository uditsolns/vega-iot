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
        Schema::create("roles", function (Blueprint $table) {
            $table->id();
            // Note: company_id foreign key will be added after companies table is created
            $table->foreignId("company_id")->nullable();
            $table->string("name", 50);
            $table->text("description")->nullable();
            $table->integer("hierarchy_level")->default(100);
            $table->boolean("is_system_role")->default(false);
            $table->boolean("is_editable")->default(true);
            $table->timestamps();

            // Unique constraint for company + role name combination
            $table->unique(["company_id", "name"], "uq_company_role_name");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("roles");
    }
};
