<?php

namespace App\Models;

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
        'alert_id',
        'user_id',
        'channel',
        'sent_at',
        'is_delivered',
        'delivered_at',
        'delivery_error',
        'message_content',
        'external_reference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'is_delivered' => 'boolean',
            'delivered_at' => 'datetime',
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
     * Scope to delivered notifications
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('is_delivered', true);
    }

    /**
     * Scope to failed notifications
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('is_delivered', false)
            ->whereNotNull('delivery_error');
    }

    /**
     * Scope to pending notifications
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_delivered', false)
            ->whereNull('delivery_error');
    }

    /**
     * Scope to notifications by channel
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to recent notifications first
     */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderBy('sent_at', 'desc');
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Mark notification as delivered
     */
    public function markDelivered(?string $externalReference = null): bool
    {
        return $this->update([
            'is_delivered' => true,
            'delivered_at' => now(),
            'external_reference' => $externalReference ?? $this->external_reference,
            'delivery_error' => null,
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markFailed(string $error): bool
    {
        return $this->update([
            'is_delivered' => false,
            'delivery_error' => $error,
        ]);
    }

    /**
     * Retry sending the notification
     */
    public function retry(): void
    {
        $this->update([
            'sent_at' => now(),
            'is_delivered' => false,
            'delivery_error' => null,
        ]);
    }
}
