<?php

namespace App\Models;

use App\Enums\AlertSensorType;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "device_id",
        "type",
        "severity",
        "status",
        "trigger_value",
        "threshold_breached",
        "reason",
        "started_at",
        "ended_at",
        "acknowledged_at",
        "acknowledged_by",
        "acknowledge_comment",
        "resolved_at",
        "resolved_by",
        "resolve_comment",
        "duration_seconds",
        "is_back_in_range",
        "last_notification_at",
        "notification_count",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "type" => AlertSensorType::class,
            "severity" => AlertSeverity::class,
            "status" => AlertStatus::class,
            "trigger_value" => "decimal:2",
            "started_at" => "datetime",
            "ended_at" => "datetime",
            "acknowledged_at" => "datetime",
            "resolved_at" => "datetime",
            "last_notification_at" => "datetime",
            "is_back_in_range" => "boolean",
            "created_at" => "datetime",
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the device that triggered this alert
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the user who acknowledged this alert
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "acknowledged_by");
    }

    /**
     * Get the user who resolved this alert
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "resolved_by");
    }

    /**
     * Get all notifications sent for this alert
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope alerts for a specific user (based on device access)
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Get device IDs the user has access to
        $deviceIds = Device::forUser($user)->pluck("id")->toArray();

        return $query->whereIn("device_id", $deviceIds);
    }

    /**
     * Scope to active alerts only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where("status", AlertStatus::Active);
    }

    /**
     * Scope to acknowledged alerts
     */
    public function scopeAcknowledged(Builder $query): Builder
    {
        return $query->where("status", AlertStatus::Acknowledged);
    }

    /**
     * Scope to alerts by status
     */
    public function scopeByStatus(
        Builder $query,
        AlertStatus|string $status,
    ): Builder {
        $statusValue =
            $status instanceof AlertStatus ? $status->value : $status;
        return $query->where("status", $statusValue);
    }

    /**
     * Scope to alerts by severity
     */
    public function scopeBySeverity(
        Builder $query,
        AlertSeverity|string $severity,
    ): Builder {
        $severityValue =
            $severity instanceof AlertSeverity ? $severity->value : $severity;
        return $query->where("severity", $severityValue);
    }

    /**
     * Scope to alerts by sensor type
     */
    public function scopeByType(
        Builder $query,
        AlertSensorType|string $type,
    ): Builder {
        $typeValue = $type instanceof AlertSensorType ? $type->value : $type;
        return $query->where("type", $typeValue);
    }

    /**
     * Scope to get recent alerts first
     */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderBy("started_at", "desc");
    }

    /**
     * Scope to open alerts (active or acknowledged)
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn("status", [
            AlertStatus::Active->value,
            AlertStatus::Acknowledged->value,
        ]);
    }

    /**
     * Scope to closed alerts (resolved or auto-resolved)
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn("status", [
            AlertStatus::Resolved->value,
            AlertStatus::AutoResolved->value,
        ]);
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Acknowledge the alert
     */
    public function acknowledge(User $user, ?string $comment = null): bool
    {
        if ($this->status !== AlertStatus::Active) {
            return false;
        }

        return $this->update([
            "status" => AlertStatus::Acknowledged,
            "acknowledged_at" => now(),
            "acknowledged_by" => $user->id,
            "acknowledge_comment" => $comment,
        ]);
    }

    /**
     * Resolve the alert
     */
    public function resolve(
        User $user,
        ?string $comment = null,
        bool $isBackInRange = false,
    ): bool {
        if (
            !in_array($this->status, [
                AlertStatus::Active,
                AlertStatus::Acknowledged,
            ])
        ) {
            return false;
        }

        $endedAt = now();
        $durationSeconds = $this->started_at->diffInSeconds($endedAt);

        return $this->update([
            "status" => AlertStatus::Resolved,
            "resolved_at" => $endedAt,
            "resolved_by" => $user->id,
            "resolve_comment" => $comment,
            "ended_at" => $endedAt,
            "duration_seconds" => $durationSeconds,
            "is_back_in_range" => $isBackInRange,
        ]);
    }

    /**
     * Auto-resolve the alert (when sensor returns to normal range)
     */
    public function autoResolve(): bool
    {
        if (
            !in_array($this->status, [
                AlertStatus::Active,
                AlertStatus::Acknowledged,
            ])
        ) {
            return false;
        }

        $endedAt = now();
        $durationSeconds = $this->started_at->diffInSeconds($endedAt);

        return $this->update([
            "status" => AlertStatus::AutoResolved,
            "ended_at" => $endedAt,
            "duration_seconds" => $durationSeconds,
            "is_back_in_range" => true,
        ]);
    }

    /**
     * Increment notification count
     */
    public function incrementNotificationCount(): void
    {
        $this->increment("notification_count");
        $this->update(["last_notification_at" => now()]);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get formatted duration
     */
    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf("%dh %dm %ds", $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf("%dm %ds", $minutes, $seconds);
        } else {
            return sprintf("%ds", $seconds);
        }
    }

    /**
     * Get device name
     */
    public function getDeviceNameAttribute(): ?string
    {
        return $this->device?->device_name ?? $this->device?->device_code;
    }

    /**
     * Get area name
     */
    public function getAreaNameAttribute(): ?string
    {
        return $this->device?->area?->name;
    }
}
