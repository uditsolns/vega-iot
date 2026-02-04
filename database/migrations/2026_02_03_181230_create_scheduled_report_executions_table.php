<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_report_executions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('scheduled_report_id')->constrained('scheduled_reports')->cascadeOnDelete();

            $table->timestamp('executed_at');
            $table->string('status');

            $table->integer('reports_generated')->default(0);
            $table->string('reports_failed')->default(0);

            $table->string('error_message')->nullable();
            $table->jsonb('execution_details')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_report_executions');
    }
};
