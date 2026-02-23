<?php

namespace App\Services\Reading;

use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReadingQueryService
{
    /**
     * Get the latest reading for each sensor of a device.
     * Used for the device-list / dashboard "current status" view.
     *
     * Returns a keyed collection:
     *   [device_sensor_id => ['value' => float|null, 'recorded_at' => string, 'unit' => string]]
     */
    public function latestForDevice(Device $device): array
    {
        $device->loadMissing(['sensors.sensorType']);

        if ($device->sensors->isEmpty()) {
            return [];
        }

        $sensorIds = $device->sensors->pluck('id')->all();

        // TimescaleDB last() aggregate is fastest; fall back to subquery for standard PG
        $rows = DB::select("
            SELECT DISTINCT ON (device_sensor_id)
                device_sensor_id,
                value_numeric,
                recorded_at,
                metadata
            FROM sensor_readings
            WHERE device_sensor_id = ANY(?)
            ORDER BY device_sensor_id, recorded_at DESC
        ", ['{' . implode(',', $sensorIds) . '}']);

        $byId = collect($rows)->keyBy('device_sensor_id');

        return $device->sensors->mapWithKeys(function ($sensor) use ($byId) {
            $row = $byId->get($sensor->id);

            return [$sensor->id => [
                'slot_number'  => $sensor->slot_number,
                'label'        => $sensor->label ?? $sensor->sensorType->name,
                'sensor_type'  => $sensor->sensorType->name,
                'unit'         => $sensor->sensorType->unit,
                'value'        => $row ? (float) $row->value_numeric : null,
                'recorded_at'  => $row ? $row->recorded_at : null,
                'is_enabled'   => $sensor->is_enabled,
            ]];
        })->all();
    }

    /**
     * Bulk: latest reading snapshot for every device in a list.
     * Avoids N+1 by doing one query for all device_sensor_ids at once.
     *
     * Returns: [device_id => ['sensors' => [...], 'last_reading_at' => string|null]]
     */
    public function latestForDevices(Collection $devices): array
    {
        if ($devices->isEmpty()) {
            return [];
        }

        $devices->loadMissing(['sensors.sensorType']);

        $sensorIds = $devices->flatMap(fn ($d) => $d->sensors->pluck('id'))->all();

        if (empty($sensorIds)) {
            return [];
        }

        $placeholder = '{' . implode(',', $sensorIds) . '}';

        $rows = DB::select("
            SELECT DISTINCT ON (device_sensor_id)
                device_sensor_id,
                value_numeric,
                recorded_at
            FROM sensor_readings
            WHERE device_sensor_id = ANY(?)
            ORDER BY device_sensor_id, recorded_at DESC
        ", [$placeholder]);

        $byId = collect($rows)->keyBy('device_sensor_id');

        $result = [];

        foreach ($devices as $device) {
            $sensorSnapshots = $device->sensors->map(function ($sensor) use ($byId) {
                $row = $byId->get($sensor->id);
                return [
                    'sensor_id'   => $sensor->id,
                    'slot_number' => $sensor->slot_number,
                    'label'       => $sensor->label ?? $sensor->sensorType->name,
                    'sensor_type' => $sensor->sensorType->name,
                    'unit'        => $sensor->sensorType->unit,
                    'value'       => $row ? (float) $row->value_numeric : null,
                    'recorded_at' => $row ? $row->recorded_at : null,
                    'is_enabled'  => $sensor->is_enabled,
                ];
            })->values()->all();

            $result[$device->id] = [
                'device_id'      => $device->id,
                'last_reading_at' => $device->last_reading_at?->toIso8601String(),
                'sensors'        => $sensorSnapshots,
            ];
        }

        return $result;
    }

    /**
     * Historical readings for a single sensor within a time range.
     */
    public function history(int $deviceSensorId, string $from, string $to, int $limit = 1440): Collection
    {
        return SensorReading::forSensor($deviceSensorId)
            ->inRange($from, $to)
            ->latest()
            ->limit($limit)
            ->get(['recorded_at', 'value_numeric', 'metadata']);
    }
}
