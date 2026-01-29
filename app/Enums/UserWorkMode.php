<?php

namespace App\Enums;

enum UserWorkMode: string
{
    case Shipping = 'shipping';
    case Storage = 'storage';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Shipping => 'Shipping',
            self::Storage => 'Storage',
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
            self::Shipping->value => self::Shipping->label(),
            self::Storage->value => self::Storage->label(),
        ];
    }
}
