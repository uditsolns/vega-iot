<?php

namespace App\Enums;

enum AlertNotificationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get labels for display
     */
    public static function labels(): array
    {
        return [
            self::Pending->value => 'Pending',
            self::Sent->value => 'Sent',
            self::Failed->value => 'Failed',
        ];
    }

    /**
     * Get label for current status
     */
    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    /**
     * Get color for badge display
     */
    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }

    /**
     * Check if notification is pending
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Check if notification is sent
     */
    public function isSent(): bool
    {
        return $this === self::Sent;
    }

    /**
     * Check if notification failed
     */
    public function isFailed(): bool
    {
        return $this === self::Failed;
    }
}
