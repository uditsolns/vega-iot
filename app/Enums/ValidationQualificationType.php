<?php

namespace App\Enums;

enum ValidationQualificationType: string
{
    case IQ = 'IQ';
    case OQ = 'OQ';
    case PQ = 'PQ';

    /**
     * Get display label for qualification type
     */
    public function label(): string
    {
        return match ($this) {
            self::IQ => 'Installation Qualification (IQ)',
            self::OQ => 'Operational Qualification (OQ)',
            self::PQ => 'Performance Qualification (PQ)',
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
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }
}
