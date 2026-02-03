<?php

use App\Enums\AuditReportType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('generated_by')->constrained('users');
            $table->string('name');
            $table->enum('type', AuditReportType::values());
            $table->unsignedBigInteger('resource_id');
            $table->date('from_date');
            $table->date('to_date');

            $table->timestamp('generated_at')->useCurrent();

            $table->index(['type', 'resource_id']);
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
