<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Reopened => 'Reopened',
        };
    }

    /**
     * Check if status is open (not resolved/closed)
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::Reopened]);
    }

    /**
     * Check if status is closed
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Closed]);
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
