<?php

namespace App\Models;

use App\Enums\AlertNotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertNotification extends Model
{
    use HasFactory;

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
        "alert_id",
        "user_id",
        "channel",
        "status",
        "event",
        "queued_at",
        "sent_at",
        "failed_at",
        "retry_count",
        "message_content",
        "external_reference",
        "error_message",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "status" => AlertNotificationStatus::class,
            "queued_at" => "datetime",
            "sent_at" => "datetime",
            "failed_at" => "datetime",
            "retry_count" => "integer",
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the alert this notification belongs to
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    /**
     * Get the user who received this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to sent notifications
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where("status", AlertNotificationStatus::Sent);
    }

    /**
     * Scope to failed notifications
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where("status", AlertNotificationStatus::Failed);
    }

    /**
     * Scope to pending notifications
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where("status", AlertNotificationStatus::Pending);
    }

    /**
     * Scope to notifications by channel
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where("channel", $channel);
    }

    /**
     * Scope to notifications by event
     */
    public function scopeByEvent(Builder $query, string $event): Builder
    {
        return $query->where("event", $event);
    }

    /**
     * Scope to recent notifications first
     */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderBy("queued_at", "desc");
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Mark notification as sent
     */
    public function markSent(?string $externalReference = null): bool
    {
        return $this->update([
            "status" => AlertNotificationStatus::Sent,
            "sent_at" => now(),
            "external_reference" =>
                $externalReference ?? $this->external_reference,
            "error_message" => null,
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markFailed(string $error): bool
    {
        return $this->update([
            "status" => AlertNotificationStatus::Failed,
            "failed_at" => now(),
            "error_message" => $error,
            "retry_count" => $this->retry_count + 1,
        ]);
    }

    /**
     * Check if notification is pending
     */
    public function isPending(): bool
    {
        return $this->status === AlertNotificationStatus::Pending;
    }

    /**
     * Check if notification is sent
     */
    public function isSent(): bool
    {
        return $this->status === AlertNotificationStatus::Sent;
    }

    /**
     * Check if notification is failed
     */
    public function isFailed(): bool
    {
        return $this->status === AlertNotificationStatus::Failed;
    }
}
