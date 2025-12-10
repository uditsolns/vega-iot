<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case Warning = 'warning';
    case Critical = 'critical';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Warning => 'Warning',
            self::Critical => 'Critical',
        };
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
            self::Warning->value => self::Warning->label(),
            self::Critical->value => self::Critical->label(),
        ];
    }
}
