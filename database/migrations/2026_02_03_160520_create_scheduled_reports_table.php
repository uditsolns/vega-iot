<?php

use App\Enums\DeviceType;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use App\Enums\ScheduledReportFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->enum('frequency', ScheduledReportFrequency::values());
            $table->string('timezone', 50)->default('Asia/Kolkata');
            $table->time('time');

            // Email configuration
            $table->jsonb('recipient_emails');

            // Report settings
            $table->enum('device_type', DeviceType::values());
            $table->enum('file_type', ReportFileType::values())->default(ReportFileType::Pdf->value);
            $table->enum('format', ReportFormat::values())->default(ReportFormat::Graphical->value);
            $table->enum('data_formation', ReportDataFormation::values());
            $table->integer('interval');

            // status;
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('next_run_at');
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
