<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calibration_instruments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies');

            // Instrument identity
            $table->string('instrument_name')->nullable();
            $table->string('instrument_code')->nullable()->unique();
            $table->string('serial_no')->nullable();

            // Manufacturer details
            $table->string('make')->nullable();
            $table->string('model')->nullable();

            // Location
            $table->string('location')->nullable();

            // Technical specifications
            $table->string('measurement_range')->nullable();
            $table->string('resolution')->nullable();
            $table->string('accuracy')->nullable();

            // Calibration tracking
            $table->date('last_calibrated_at')->nullable();
            $table->date('calibration_due_at')->nullable();


            $table->timestamps();

            $table->index('calibration_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_instruments');
    }
};
