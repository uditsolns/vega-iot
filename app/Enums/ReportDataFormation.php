<?php

namespace App\Enums;

enum ReportDataFormation: string
{
    case SingleTemperature = 'single_temperature';
    case CombinedTemperatureHumidity = 'combined_temperature_humidity';
    case SeparateTemperatureHumidity = 'separate_temperature_humidity';
    case CombinedProbeTemperature = 'combined_probe_temperature';
    case CombinedProbeTemperatureHumidity = 'combined_probe_temperature_humidity';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SingleTemperature => 'Single Temperature',
            self::CombinedTemperatureHumidity => 'Combined Temperature & Humidity',
            self::SeparateTemperatureHumidity => 'Separate Temperature & Humidity',
            self::CombinedProbeTemperature => 'Combined Temperature (Probe)',
            self::CombinedProbeTemperatureHumidity => 'Combined Temperature (Probe) & Humidity',
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
