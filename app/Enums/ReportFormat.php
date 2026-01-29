<?php

namespace App\Enums;

enum ReportFormat: string
{
    case Graphical = 'graphical';
    case Tabular = 'tabular';
    case Both = 'both';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Graphical => 'Low',
            self::Tabular => 'Medium',
            self::Both => 'High',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all labels as array
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }
        return $labels;
    }
}
