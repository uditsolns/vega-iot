<?php

use App\Enums\ValidationQualificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('validation_studies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies');

            // Area details
            $table->string('area_type')->nullable();
            $table->string('area_reference')->nullable();

            // Study details
            $table->unsignedInteger('number_of_loggers')->nullable();
            $table->string('cfa', 50)->nullable();
            $table->string('location')->nullable();

            // Qualification
            $table->enum('qualification_type', ValidationQualificationType::values())->nullable();

            $table->string('reason')->nullable();

            // Conditions
            $table->string('temperature_range')->nullable();
            $table->string('duration')->nullable();

            // Schedule
            $table->string('mapping_start_at')->nullable();
            $table->string('mapping_end_at')->nullable();
            $table->string('mapping_due_at')->nullable();

            // Documents
            $table->string('report_path')->nullable();

            $table->timestamps();

            $table->index('mapping_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_studies');
    }
};
