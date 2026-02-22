<?php

namespace App\Enums;

enum Vendor: string
{
    case Zion = 'zion';
    case TZone = 'tzone';
    case Ideabyte = 'ideabyte';
    case Aliter = 'aliter';
    case Sunsui = 'sunsui';

    public function label(): string
    {
        return match ($this) {
            self::Zion => 'Zion',
            self::TZone => 'TZone',
            self::Ideabyte => 'Ideabyte',
            self::Aliter => 'Aliter',
            self::Sunsui => 'Sunsui',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
