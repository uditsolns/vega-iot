<?php

namespace App\Enums;

enum SensorDataType: string
{
    case Decimal = 'decimal';
    case Point = 'point';
    case Integer = 'integer';
    case Boolean = 'boolean';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
