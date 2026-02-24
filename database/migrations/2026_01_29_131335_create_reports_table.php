<?php

use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('device_id')->constrained('devices');
            $table->foreignId('generated_by')->constrained('users');

            $table->string('name');
            $table->enum('file_type', ReportFileType::values())->default(ReportFileType::Pdf->value);
            $table->enum('format', ReportFormat::values())
                ->default(ReportFormat::Graphical->value);
            $table->jsonb('sensor_ids')
                ->comment('Array of device_sensor_id values selected for this report');
            $table->integer('interval');
            $table->dateTime('from_datetime');
            $table->dateTime('to_datetime');

            $table->timestamp('generated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
