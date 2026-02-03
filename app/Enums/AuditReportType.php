<?php

namespace App\Enums;

enum AuditReportType: string
{
    case User = 'user';
    case Device = 'device';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User Activity Report',
            self::Device => 'Device Activity Report',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::User->value => self::User->label(),
            self::Device->value => self::Device->label(),
        ];
    }
}
