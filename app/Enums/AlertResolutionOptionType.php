<?php

namespace App\Enums;

enum AlertResolutionOptionType: string
{
    case PossibleCause     = 'possible_cause';
    case RootCause         = 'root_cause';
    case CorrectiveAction  = 'corrective_action';

    public function label(): string
    {
        return match ($this) {
            self::PossibleCause    => 'Possible Cause',
            self::RootCause        => 'Root Cause',
            self::CorrectiveAction => 'Corrective Action',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
