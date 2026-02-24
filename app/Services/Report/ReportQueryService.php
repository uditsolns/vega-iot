<?php

namespace App\Services\Report;

use App\DTOs\ReportGenerationDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates all TimescaleDB hyperfunction queries for report generation.
 */
class ReportQueryService
{
    /**
     * Fetch interval-bucketed readings for all selected sensors.
     *
     * @param int[] $sensorIds
     */
    public function getBucketedReadings(ReportGenerationDTO $dto, array $sensorIds): Collection
    {
        $intervalExpr = "{$dto->interval} minutes";
        $from = $dto->fromDatetime->toDateTimeString();
        $to = $dto->toDatetime->toDateTimeString();

        // Build the dynamic CASE pivot columns for each sensor
        $pivotCols = $this->buildPivotColumns($sensorIds, useLocf: true);

        $sql = "
            SELECT
                time_bucket_gapfill(
                    interval '{$intervalExpr}',
                    recorded_at,
                    start => :from::timestamptz,
                    finish => :to::timestamptz
                ) AS bucket,
                {$pivotCols}
            FROM sensor_readings
            WHERE device_id     = :device_id
              AND device_sensor_id IN (" . implode(',', $sensorIds) . ")
              AND recorded_at  >= :from2::timestamptz
              AND recorded_at   < :to2::timestamptz
            GROUP BY bucket
            ORDER BY bucket ASC
        ";

        $rows = DB::select($sql, [
            'device_id' => $dto->deviceId,
            'from' => $from,
            'to' => $to,
            'from2' => $from,
            'to2' => $to,
        ]);

        return collect($rows)->map(function ($row) {
            $row->bucket = Carbon::parse($row->bucket);
            return $row;
        });
    }

    /**
     * Compute per-sensor statistics in a single DB pass using stats_agg().
     *
     * @param int[] $sensorIds
     */
    public function getSensorStatistics(ReportGenerationDTO $dto, array $sensorIds): Collection
    {
        $from = $dto->fromDatetime->toDateTimeString();
        $to = $dto->toDatetime->toDateTimeString();

        $placeholders = implode(',', $sensorIds);

        $sql = "
            SELECT
                device_sensor_id,
                MIN(value_numeric)                   AS min_val,
                MAX(value_numeric)                   AS max_val,
                average(stats_agg(value_numeric))    AS avg_val,
                stddev(stats_agg(value_numeric))     AS stddev_val,
                num_vals(stats_agg(value_numeric))   AS count_val,
                first(value_numeric, recorded_at)    AS first_val,
                last(value_numeric, recorded_at)     AS last_val
            FROM sensor_readings
            WHERE device_id          = :device_id
              AND device_sensor_id   IN ({$placeholders})
              AND recorded_at       >= :from::timestamptz
              AND recorded_at        < :to::timestamptz
              AND value_numeric     IS NOT NULL
            GROUP BY device_sensor_id
        ";

        $rows = DB::select($sql, [
            'device_id' => $dto->deviceId,
            'from' => $from,
            'to' => $to,
        ]);

        return collect($rows)->keyBy('device_sensor_id');
    }

    /**
     * Build dynamic SQL CASE pivot columns.
     * @param int[] $sensorIds
     */
    private function buildPivotColumns(array $sensorIds, bool $useLocf = false): string
    {
        $cols = [];

        foreach ($sensorIds as $id) {
            $id = (int)$id;
            $expr = "AVG(CASE WHEN device_sensor_id = {$id} THEN value_numeric END)";

            if ($useLocf) {
                $expr = "locf({$expr})";
            }

            $cols[] = "{$expr} AS sensor_{$id}";
        }

        return implode(",\n                ", $cols);
    }
}
