<?php

use App\Enums\AlertResolutionOptionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alert_resolution_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained("companies")->nullOnDelete();
            $table->enum('type', AlertResolutionOptionType::values());
            $table->string('label', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['type', 'is_system', 'company_id'], 'idx_aro_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_resolution_options');
    }
};
