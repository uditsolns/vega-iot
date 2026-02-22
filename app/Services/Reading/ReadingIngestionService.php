<?php

namespace App\Services\Reading;

use App\Events\ReadingReceived;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReadingIngestionService
{
    /**
     * Ingest readings from vendor adapter output
     */
    public function ingest(Device $device, array $parsedData): array
    {
        $device->loadMissing(['sensors.sensorType']);

        $recordedAt = $parsedData['recorded_at'];
        $receivedAt = now();

        // Build metadata (stored once per batch in first reading)
        $metadata = array_filter([
            'battery_voltage'  => $parsedData['battery_voltage'] ?? null,
            'signal_strength'  => $parsedData['signal_strength'] ?? null,
            'firmware_version' => $parsedData['firmware_version'] ?? null,
        ]);

        $insertedCount = 0;
        $sensorReadings = [];

        DB::transaction(function () use ($device, $parsedData, $recordedAt, $receivedAt, $metadata, &$insertedCount, &$sensorReadings) {
            foreach ($parsedData['readings'] as $index => $reading) {
                $slotNumber = $reading['slot_number'];
                $value = $reading['value'] ?? null;
                $readingMetadata = $reading['metadata'] ?? [];

                $sensor = $device->sensors->firstWhere('slot_number', $slotNumber);
                if (!$sensor || !$sensor->is_enabled) {
                    continue;
                }

                // Merge batch metadata into first reading only
                $finalMetadata = $index === 0 ? array_merge($metadata, $readingMetadata) : $readingMetadata;

                // Insert into sensor_readings (hypertable)
                SensorReading::insert([
                    'device_id'        => $device->id,
                    'device_sensor_id' => $sensor->id,
                    'recorded_at'      => $recordedAt,
                    'received_at'      => $receivedAt,
                    'company_id'       => $device->company_id,
                    'area_id'          => $device->area_id,
                    'value_numeric'    => $value,
                    'metadata'         => json_encode($finalMetadata),
                ]);

                $insertedCount++;

                // Collect for event
                $sensorReadings[] = [
                    'sensor_id' => $sensor->id,
                    'value'     => $value,
                    'metadata'  => $finalMetadata,
                ];
            }

            // Update device last_reading_at
            $device->update(['last_reading_at' => $recordedAt]);
        });

        // Dispatch event for async alert processing
        ReadingReceived::dispatch(
            $device->id,
            $recordedAt->toIso8601String(),
            $sensorReadings
        );

        Log::info('Readings ingested', [
            'device_id'      => $device->id,
            'device_code'    => $device->device_code,
            'inserted_count' => $insertedCount,
        ]);

        return [
            'success'     => true,
            'inserted'    => $insertedCount,
            'recorded_at' => $recordedAt->toIso8601String(),
        ];
    }
}
