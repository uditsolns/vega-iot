<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Online = "online";
    case Offline = "offline";
    case Maintenance = "maintenance";
    case Decommissioned = "decommissioned";

    /**
     * Get display label for the device status
     */
    public function label(): string
    {
        return match ($this) {
            self::Online => "Online",
            self::Offline => "Offline",
            self::Maintenance => "Maintenance",
            self::Decommissioned => "Decommissioned",
        };
    }

    /**
     * Get all enum values as array (for migrations)
     */
    public static function values(): array
    {
        return array_column(self::cases(), "value");
    }

    /**
     * Get all labels indexed by value
     */
    public static function labels(): array
    {
        return array_combine(
            self::values(),
            array_map(fn($case) => $case->label(), self::cases()),
        );
    }
}
