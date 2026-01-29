<?php

namespace App\Enums;

enum ReportFileType: string
{
    case Pdf = 'pdf';
    case Csv = 'csv';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Csv => 'CSV',
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
