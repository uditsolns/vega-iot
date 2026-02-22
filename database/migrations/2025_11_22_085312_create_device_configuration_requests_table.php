<?php

use App\Enums\ConfigRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_configuration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->jsonb('requested_config');
            $table->text('vendor_command');
            $table->enum('status', ConfigRequestStatus::values())->default(ConfigRequestStatus::Pending->value);
            $table->integer('priority')->default(1);
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['device_id', 'status'], 'idx_config_requests_device_status');
        });

        DB::statement(
            "CREATE INDEX idx_config_requests_pending ON device_configuration_requests (status, priority, created_at) WHERE status = 'pending'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('device_configuration_requests');
    }
};
