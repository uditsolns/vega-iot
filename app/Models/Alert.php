<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'device_sensor_id',
        'severity',
        'status',
        'trigger_value',
        'threshold_breached',
        'reason',
        'started_at',
        'ended_at',
        'duration_seconds',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledge_comment',
        'resolved_at',
        'resolved_by',
        'resolve_comment',
        'is_back_in_range',
        'last_notification_at',
        'notification_count',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'trigger_value' => 'decimal:2',
            'is_back_in_range' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_notification_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function deviceSensor(): BelongsTo
    {
        return $this->belongsTo(DeviceSensor::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $deviceIds = Device::forUser($user)->pluck('id')->toArray();
        return $query->whereIn('device_id', $deviceIds);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AlertStatus::Active);
    }

    public function scopeAcknowledged(Builder $query): Builder
    {
        return $query->where('status', AlertStatus::Acknowledged);
    }

    public function scopeByStatus(Builder $query, AlertStatus|string $status): Builder
    {
        $value = $status instanceof AlertStatus ? $status->value : $status;
        return $query->where('status', $value);
    }

    public function scopeBySeverity(Builder $query, AlertSeverity|string $severity): Builder
    {
        $value = $severity instanceof AlertSeverity ? $severity->value : $severity;
        return $query->where('severity', $value);
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('started_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [AlertStatus::Active->value, AlertStatus::Acknowledged->value]);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [AlertStatus::Resolved->value, AlertStatus::AutoResolved->value]);
    }

    // ========================================
    // ACTIONS
    // ========================================

    public function acknowledge(User $user, ?string $comment = null): bool
    {
        if ($this->status !== AlertStatus::Active) {
            return false;
        }

        return $this->update([
            'status' => AlertStatus::Acknowledged,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
            'acknowledge_comment' => $comment,
        ]);
    }

    public function resolve(User $user, ?string $comment = null, bool $isBackInRange = false): bool
    {
        if (!in_array($this->status, [AlertStatus::Active, AlertStatus::Acknowledged])) {
            return false;
        }

        $endedAt = now();

        return $this->update([
            'status' => AlertStatus::Resolved,
            'resolved_at' => $endedAt,
            'resolved_by' => $user->id,
            'resolve_comment' => $comment,
            'ended_at' => $endedAt,
            'duration_seconds' => $this->started_at->diffInSeconds($endedAt),
            'is_back_in_range' => $isBackInRange,
        ]);
    }

    public function autoResolve(): bool
    {
        if (!in_array($this->status, [AlertStatus::Active, AlertStatus::Acknowledged])) {
            return false;
        }

        $endedAt = now();

        return $this->update([
            'status' => AlertStatus::AutoResolved,
            'ended_at' => $endedAt,
            'duration_seconds' => $this->started_at->diffInSeconds($endedAt),
            'is_back_in_range' => true,
        ]);
    }

    public function incrementNotificationCount(): void
    {
        $this->increment('notification_count');
        $this->update(['last_notification_at' => now()]);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        }

        return $minutes > 0 ? sprintf('%dm %ds', $minutes, $seconds) : sprintf('%ds', $seconds);
    }
}
