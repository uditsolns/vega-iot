<?php

namespace App\Enums;

enum ScheduledReportFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Fortnightly = 'fortnightly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Fortnightly => 'Fortnightly',
            self::Monthly => 'Monthly',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }
        return $labels;
    }
}
