<?php

namespace App\Enums;

enum ConfigRequestStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Confirmed, self::Failed]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
