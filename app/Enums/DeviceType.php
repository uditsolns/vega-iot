<?php

namespace App\Enums;

enum DeviceType: string
{
    case SingleTemp = 'single_temp';
    case SingleTempHumidity = 'single_temp_humidity';
    case DualTemp = 'dual_temp';
    case DualTempHumidity = 'dual_temp_humidity';

    /**
     * Get display label for the device type
     */
    public function label(): string
    {
        return match ($this) {
            self::SingleTemp => 'Single Temperature',
            self::SingleTempHumidity => 'Single Temperature & Humidity',
            self::DualTemp => 'Dual Temperature',
            self::DualTempHumidity => 'Dual Temperature & Humidity',
        };
    }

    /**
     * Get all enum values as array (for migrations)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all labels indexed by value
     */
    public static function labels(): array
    {
        return array_combine(
            self::values(),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
