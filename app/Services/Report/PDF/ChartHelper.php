<?php

namespace App\Services\Report\PDF;

class ChartHelper
{
    public static function prepareChartData(array $logs, string $type, array $config = []): array
    {
        $chartWidth = $config['width'] ?? 760;
        $chartHeight = $config['height'] ?? 380;
        $padding = $config['padding'] ?? ['top' => 40, 'right' => 60, 'bottom' => 60, 'left' => 60];

        $plotWidth = $chartWidth - $padding['left'] - $padding['right'];
        $plotHeight = $chartHeight - $padding['top'] - $padding['bottom'];

        $timestamps = array_column($logs, 'timestamp');
        $dataPoints = count($logs);

        return compact('chartWidth', 'chartHeight', 'padding', 'plotWidth', 'plotHeight', 'timestamps', 'dataPoints');
    }

    public static function calculateAxisRange(array $values): array
    {
        if (empty($values)) {
            return ['min' => 0, 'max' => 100, 'range' => 100];
        }

        $min = min($values);
        $max = max($values);
        $range = $max - $min;

        $min = floor(($min - ($range * 0.1)) / 5) * 5;
        $max = ceil(($max + ($range * 0.1)) / 5) * 5;
        $range = $max - $min;

        return compact('min', 'max', 'range');
    }

    public static function getYPosition(float $value, float $min, float $max, float $plotHeight, float $paddingTop): float
    {
        $range = $max - $min;
        if ($range == 0) return $paddingTop + $plotHeight / 2;

        $normalized = ($value - $min) / $range;
        return $paddingTop + $plotHeight - ($normalized * $plotHeight);
    }

    public static function getXPosition(int $index, int $totalPoints, float $plotWidth, float $paddingLeft): float
    {
        if ($totalPoints <= 1) return $paddingLeft;
        return $paddingLeft + ($index / ($totalPoints - 1)) * $plotWidth;
    }

    public static function prepareDataset(array $logs, string $type, array $thresholds = []): array
    {
        $values = array_filter(array_column($logs, $type), fn($v) => $v !== null);

        return [
            'values' => array_column($logs, $type),
            'filtered' => $values,
            'minThreshold' => $thresholds['min'] ?? null,
            'maxThreshold' => $thresholds['max'] ?? null,
        ];
    }
}
