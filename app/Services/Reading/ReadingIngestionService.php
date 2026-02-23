<?php

namespace App\Services\Reading;

use App\Events\ReadingReceived;
use App\Models\Device;
use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReadingIngestionService
{
    /**
     * Ingest a single reading batch produced by a vendor adapter.
     *
     * $parsedBatch shape:
     * [
     *   'recorded_at'      => Carbon,
     *   'firmware_version' => string|null,
     *   'battery_voltage'  => float|null,
     *   'signal_strength'  => int|null,
     *   'readings'         => [
     *       ['slot_number' => int, 'value' => float|null, 'metadata' => array],
     *       ...
     *   ],
     *   'extra_metadata'   => array   // optional – merged into first reading metadata
     * ]
     */
    public function ingest(Device $device, array $parsedBatch): array
    {
        $device->loadMissing(['sensors.sensorType']);

        /** @var Carbon $recordedAt */
        $recordedAt  = $parsedBatch['recorded_at'];
        $receivedAt  = now();

        $batchMeta = array_filter([
            'battery_voltage'  => $parsedBatch['battery_voltage']  ?? null,
            'signal_strength'  => $parsedBatch['signal_strength']  ?? null,
            'firmware_version' => $parsedBatch['firmware_version'] ?? null,
        ]);

        // Merge any adapter-level extra metadata (battery %, IMEI, etc.)
        if (!empty($parsedBatch['extra_metadata'])) {
            $batchMeta = array_merge($batchMeta, array_filter($parsedBatch['extra_metadata']));
        }

        $insertedCount = 0;
        $sensorReadings = [];

        DB::transaction(function () use (
            $device, $parsedBatch, $recordedAt, $receivedAt, $batchMeta,
            &$insertedCount, &$sensorReadings
        ) {
            foreach ($parsedBatch['readings'] as $index => $reading) {
                $slotNumber    = $reading['slot_number'];
                $value         = $reading['value'] ?? null;
                $readingMeta   = $reading['metadata'] ?? [];

                $sensor = $device->sensors->firstWhere('slot_number', $slotNumber);

                if (!$sensor || !$sensor->is_enabled) {
                    continue;
                }

                // Batch-level metadata only on first reading
                $finalMeta = $index === 0
                    ? array_merge($batchMeta, $readingMeta)
                    : $readingMeta;

                $isGps   = !empty($readingMeta['gps_point']);
                $pointSql = null;

                if ($isGps && isset($readingMeta['longitude'], $readingMeta['latitude'])) {
                    $lng = (float) $readingMeta['longitude'];
                    $lat = (float) $readingMeta['latitude'];
                    // PostgreSQL POINT(lng, lat) — longitude first by convention
                    $pointSql = DB::raw("point({$lng}, {$lat})");
                }

                $row = [
                    'device_id'        => $device->id,
                    'device_sensor_id' => $sensor->id,
                    'recorded_at'      => $recordedAt,
                    'received_at'      => $receivedAt,
                    'company_id'       => $device->company_id,
                    'area_id'          => $device->area_id,
                    'value_numeric'    => $isGps ? null : $value,
                    'metadata'         => empty($finalMeta) ? null : json_encode($finalMeta),
                ];

                if ($isGps && $pointSql !== null) {
                    // value_point cannot be set via Eloquent's insert() with raw; use direct query
                    DB::statement(
                        'INSERT INTO sensor_readings
                         (device_id, device_sensor_id, recorded_at, received_at,
                          company_id, area_id, value_numeric, value_point, metadata)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ' . "point({$lng}, {$lat})" . ', ?)
                         ON CONFLICT DO NOTHING',
                        [
                            $device->id,
                            $sensor->id,
                            $recordedAt,
                            $receivedAt,
                            $device->company_id,
                            $device->area_id,
                            null,
                            empty($finalMeta) ? null : json_encode($finalMeta),
                        ]
                    );
                } else {
                    DB::table('sensor_readings')->insertOrIgnore($row);
                }

                $insertedCount++;

                $sensorReadings[] = [
                    'sensor_id' => $sensor->id,
                    'value'     => $isGps ? null : $value,
                    'metadata'  => $finalMeta,
                ];
            }

            $device->updateQuietly(['last_reading_at' => $recordedAt]);
        });

        if ($insertedCount > 0) {
            ReadingReceived::dispatch(
                $device->id,
                $recordedAt->toIso8601String(),
                $sensorReadings
            );
        }

        Log::info('Readings ingested', [
            'device_id'      => $device->id,
            'device_code'    => $device->device_code,
            'recorded_at'    => $recordedAt->toIso8601String(),
            'inserted_count' => $insertedCount,
        ]);

        return [
            'success'          => true,
            'inserted_count'   => $insertedCount,
            'recorded_at'      => $recordedAt->toIso8601String(),
        ];
    }

    /**
     * Ingest multiple batches from offline/bulk packet payloads.
     * Returns aggregate stats.
     */
    public function ingestBatches(Device $device, array $batches): array
    {
        $totalInserted = 0;

        foreach ($batches as $batch) {
            $result = $this->ingest($device, $batch);
            $totalInserted += $result['inserted_count'] ?? 0;
        }

        return [
            'success'        => true,
            'batch_count'    => count($batches),
            'inserted_count' => $totalInserted,
        ];
    }
}
