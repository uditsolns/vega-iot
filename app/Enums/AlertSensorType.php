<?php

namespace App\Enums;

enum AlertSensorType: string
{
    case Temperature = 'temperature';
    case Humidity = 'humidity';
    case TempProbe = 'temp_probe';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Temperature => 'Temperature',
            self::Humidity => 'Humidity',
            self::TempProbe => 'Temperature Probe',
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
            self::Temperature->value => self::Temperature->label(),
            self::Humidity->value => self::Humidity->label(),
            self::TempProbe->value => self::TempProbe->label(),
        ];
    }
}
