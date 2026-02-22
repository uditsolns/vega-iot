<?php

namespace App\Models;

use App\Enums\ConfigRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceConfigurationRequest extends Model
{
    protected $fillable = [
        'device_id',
        'requested_config',
        'vendor_command',
        'status',
        'priority',
        'sent_at',
        'confirmed_at',
        'failed_at',
        'failure_reason',
        'retry_count',
        'max_retries',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_config' => 'array',
            'status' => ConfigRequestStatus::class,
            'priority' => 'integer',
            'retry_count' => 'integer',
            'max_retries' => 'integer',
            'sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ConfigRequestStatus::Pending);
    }

    public function markSent(): void
    {
        $this->update(['status' => ConfigRequestStatus::Sent, 'sent_at' => now()]);
    }

    public function markConfirmed(): void
    {
        $this->update(['status' => ConfigRequestStatus::Confirmed, 'confirmed_at' => now()]);
    }

    public function markFailed(string $reason): void
    {
        $this->update([
            'status' => ConfigRequestStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }
}
