<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Active = 'active';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case AutoResolved = 'auto_resolved';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Acknowledged => 'Acknowledged',
            self::Resolved => 'Resolved',
            self::AutoResolved => 'Auto-Resolved',
        };
    }

    /**
     * Check if alert is open
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Active, self::Acknowledged]);
    }

    /**
     * Check if alert is closed
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::AutoResolved]);
    }

    /**
     * Get all enum values (for migrations)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all labels mapped by value
     */
    public static function labels(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Acknowledged->value => self::Acknowledged->label(),
            self::Resolved->value => self::Resolved->label(),
            self::AutoResolved->value => self::AutoResolved->label(),
        ];
    }
}
