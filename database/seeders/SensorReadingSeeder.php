<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seeds the sensor_readings hypertable with realistic time-series data.
 *
 * Key design decisions:
 *
 * 1. METADATA PER BATCH (not per sensor, not per day):
 *    A real device sends ONE HTTP/MQTT packet per interval containing readings
 *    for ALL its sensors simultaneously. Battery voltage and RSSI are device-level
 *    telemetry — sent once per packet, not repeated per sensor value.
 *    We mirror this by storing metadata ONLY on the FIRST sensor reading for a
 *    given (device_id, recorded_at) timestamp. Every other sensor in that same
 *    batch timestamp gets metadata = NULL. This matches the architecture note:
 *    "Metadata Once Per Batch: Store battery/signal on first sensor only."
 *
 * 2. Iteration order — time outer, sensor inner:
 *    Iterating timestamps in the outer loop and sensors in the inner loop is what
 *    makes the per-batch metadata logic correct. At each timestamp we know exactly
 *    which sensor slot is "first in the batch" and tag only that one.
 *
 * 3. GPS via raw SQL:
 *    PostgreSQL POINT columns cannot be bound via standard PDO parameter binding —
 *    the driver has no type hint for POINT and treats it as a plain string, causing
 *    a type mismatch. GPS rows are collected separately and flushed via a raw SQL
 *    statement with inline POINT(lng, lat) literals.
 */
class SensorReadingSeeder extends Seeder
{
    private const INTERVAL_MINUTES = 5;
    private const DAYS             = 7;
    private const BATCH_SIZE       = 2000;

    /**
     * Numeric profiles keyed by sensor_types.name.
     *
     * daily_amplitude : peak-to-trough °C / unit swing over a 24 h sinusoidal cycle
     * noise           : max Gaussian-approximated random deviation per reading
     * breach_high/low : values used for occasional threshold-breach simulation
     * decimals        : decimal precision stored in value_numeric
     */
    private array $numericProfiles = [
        'temperature' => [
            'base'            => 22.0,
            'daily_amplitude' => 2.5,
            'noise'           => 0.3,
            'breach_high'     => 36.0,   // above max_critical = 35
            'breach_low'      => -1.0,   // below min_critical = 0
            'decimals'        => 2,
        ],
        'humidity' => [
            'base'            => 58.0,
            'daily_amplitude' => 6.0,
            'noise'           => 1.5,
            'breach_high'     => 92.0,   // above max_critical = 90
            'breach_low'      => 28.0,   // below min_critical = 30
            'decimals'        => 1,
        ],
        'lux' => [
            'base'            => 3000.0,
            'daily_amplitude' => 2800.0, // simulates lights on at noon, off at midnight
            'noise'           => 200.0,
            'breach_high'     => null,
            'breach_low'      => null,
            'decimals'        => 0,
        ],
        'vibration' => [
            'base'            => 0.08,
            'daily_amplitude' => 0.02,
            'noise'           => 0.03,
            'breach_high'     => 2.5,
            'breach_low'      => null,
            'decimals'        => 3,
        ],
        'air_quality' => [
            'base'            => 420.0,   // ppm CO2 — typical indoor baseline
            'daily_amplitude' => 150.0,
            'noise'           => 30.0,
            'breach_high'     => 1050.0,  // above max_critical = 1000
            'breach_low'      => null,
            'decimals'        => 0,
        ],
        'sound' => [
            'base'            => 42.0,    // dB — quiet office baseline
            'daily_amplitude' => 8.0,
            'noise'           => 5.0,
            'breach_high'     => 93.0,    // above max_critical = 90
            'breach_low'      => null,
            'decimals'        => 1,
        ],
    ];

    /**
     * GPS anchor coordinates assigned round-robin to GPS-capable devices.
     * Format: [longitude, latitude] — matches PostgreSQL POINT(x, y) convention.
     * Small random drift (±0.001°, ~100 m) is applied per reading to simulate GPS noise.
     */
    private array $gpsAnchors = [
        [72.8777, 19.0760],  // Mumbai
        [77.5946, 12.9716],  // Bangalore
        [80.2707, 13.0827],  // Chennai
        [88.3639, 22.5726],  // Kolkata
        [73.8567, 18.5204],  // Pune
    ];

    public function run(): void
    {
        $devices = DB::table('devices')
            ->where('status', 'online')
            ->whereNotNull('area_id')
            ->whereNotNull('company_id')
            ->select('id', 'company_id', 'area_id')
            ->get();

        if ($devices->isEmpty()) {
            $this->command->warn('No online deployed devices found. Run DeviceSeeder first.');
            return;
        }

        $this->command->info("Found {$devices->count()} online devices.");

        $now            = Carbon::now();
        $totalIntervals = (int) (self::DAYS * 24 * 60 / self::INTERVAL_MINUTES);
        $totalReadings  = 0;
        $numericBatch   = [];
        $gpsBatch       = [];
        $gpsAnchorIndex = 0;

        foreach ($devices as $device) {
            // Load sensors ordered by slot_number — slot 1 will always be "first in batch"
            $sensors = DB::table('device_sensors')
                ->join('sensor_types', 'device_sensors.sensor_type_id', '=', 'sensor_types.id')
                ->where('device_sensors.device_id', $device->id)
                ->where('device_sensors.is_enabled', true)
                ->orderBy('device_sensors.slot_number')
                ->select(
                    'device_sensors.id as device_sensor_id',
                    'sensor_types.name as type_name',
                    'sensor_types.data_type',
                )
                ->get();

            if ($sensors->isEmpty()) {
                continue;
            }

            $this->command->line("  → Device #{$device->id}: {$sensors->count()} sensor(s)");

            // Assign a GPS anchor for this device (used only if it has a GPS sensor)
            $anchor = $this->gpsAnchors[$gpsAnchorIndex % count($this->gpsAnchors)];
            $gpsAnchorIndex++;

            // ── OUTER LOOP: timestamp ────────────────────────────────────────────────
            // We iterate time first, then sensors. This is the only way to correctly
            // know which sensor is "first in the batch" at each timestamp so we can
            // attach metadata to it and NULL out all subsequent sensors in that batch.
            for ($i = $totalIntervals - 1; $i >= 0; $i--) {
                $recordedAt    = $now->copy()->subMinutes($i * self::INTERVAL_MINUTES);
                $recordedAtStr = $recordedAt->toDateTimeString();
                $receivedAtStr = $recordedAt->copy()->addSeconds(rand(1, 20))->toDateTimeString();

                // Battery drains ~10% over the seeded window; RSSI varies ±8 dBm.
                // This metadata belongs to the DEVICE, not any individual sensor —
                // store it once on the first sensor row for this timestamp batch only.
                $batchMetadata = json_encode([
                    'battery_pct' => (int) max(0, 85 - (($totalIntervals - $i) / $totalIntervals) * 10),
                    'rssi'        => -45 + rand(-8, 8),
                ]);
                $firstSensorInBatch = true;

                // ── INNER LOOP: sensors ──────────────────────────────────────────────
                foreach ($sensors as $sensor) {
                    // Metadata on first sensor in this batch; NULL for all others
                    $metadata           = $firstSensorInBatch ? $batchMetadata : null;
                    $firstSensorInBatch = false;

                    // ── GPS sensor ───────────────────────────────────────────────────
                    if ($sensor->data_type === 'point') {
                        $gpsBatch[] = [
                            'device_id'        => $device->id,
                            'device_sensor_id' => $sensor->device_sensor_id,
                            'recorded_at'      => $recordedAtStr,
                            'received_at'      => $receivedAtStr,
                            'company_id'       => $device->company_id,
                            'area_id'          => $device->area_id,
                            'longitude'        => $anchor[0] + (rand(-100, 100) / 100000),
                            'latitude'         => $anchor[1] + (rand(-100, 100) / 100000),
                            'metadata'         => $metadata,
                        ];

                        if (count($gpsBatch) >= self::BATCH_SIZE) {
                            $this->flushGpsBatch($gpsBatch);
                            $totalReadings += count($gpsBatch);
                            $this->command->line("    GPS batch flushed — {$totalReadings} total");
                            $gpsBatch = [];
                        }

                        continue;
                    }

                    // ── Numeric sensor ───────────────────────────────────────────────
                    $profile = $this->numericProfiles[$sensor->type_name] ?? [
                        'base'            => 50.0,
                        'daily_amplitude' => 10.0,
                        'noise'           => 3.0,
                        'breach_high'     => null,
                        'breach_low'      => null,
                        'decimals'        => 2,
                    ];

                    $numericBatch[] = [
                        'device_id'        => $device->id,
                        'device_sensor_id' => $sensor->device_sensor_id,
                        'recorded_at'      => $recordedAtStr,
                        'received_at'      => $receivedAtStr,
                        'company_id'       => $device->company_id,
                        'area_id'          => $device->area_id,
                        'value_numeric'    => $this->generateNumericValue($profile, $i, $totalIntervals),
                        'metadata'         => $metadata,
                    ];

                    if (count($numericBatch) >= self::BATCH_SIZE) {
                        DB::table('sensor_readings')->insert($numericBatch);
                        $totalReadings += count($numericBatch);
                        $this->command->line("    Numeric batch flushed — {$totalReadings} total");
                        $numericBatch = [];
                    }
                }
            }
        }

        // Flush leftovers
        if (!empty($numericBatch)) {
            DB::table('sensor_readings')->insert($numericBatch);
            $totalReadings += count($numericBatch);
        }

        if (!empty($gpsBatch)) {
            $this->flushGpsBatch($gpsBatch);
            $totalReadings += count($gpsBatch);
        }

        $this->command->info("✓ Seeded {$totalReadings} readings across all sensor types.");
        $this->command->info("  " . self::DAYS . " days · " . self::INTERVAL_MINUTES . "-min intervals.");
    }

    /**
     * Flush GPS readings via raw SQL with inline POINT literals.
     *
     * WHY: PDO has no native binding type for PostgreSQL POINT. Passing a string
     * like "(72.8,19.0)" results in a "column is of type point but expression is
     * of type text" error. The only clean solution is to embed POINT(lng, lat)
     * directly in the VALUES clause while still binding all other columns normally.
     *
     * Convention: PostgreSQL POINT(x, y) = POINT(longitude, latitude).
     */
    private function flushGpsBatch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        $valueSets = [];
        $bindings  = [];

        foreach ($batch as $row) {
            // POINT(?, ?) uses two bound floats; everything else is a normal binding
            $valueSets[] = '(?, ?, ?, ?, ?, ?, POINT(?, ?), ?)';
            $bindings[]  = $row['device_id'];
            $bindings[]  = $row['device_sensor_id'];
            $bindings[]  = $row['recorded_at'];
            $bindings[]  = $row['received_at'];
            $bindings[]  = $row['company_id'];
            $bindings[]  = $row['area_id'];
            $bindings[]  = $row['longitude'];
            $bindings[]  = $row['latitude'];
            $bindings[]  = $row['metadata'];
        }

        DB::statement(
            'INSERT INTO sensor_readings
                (device_id, device_sensor_id, recorded_at, received_at,
                 company_id, area_id, value_point, metadata)
             VALUES ' . implode(', ', $valueSets),
            $bindings
        );
    }

    /**
     * Generate a realistic numeric value using a sinusoidal daily cycle + noise.
     *
     * Cycle period = 288 ticks = 24 h at 5-min intervals.
     * Phase shift of -π/2 makes values lowest at midnight and peak at noon.
     *
     * Gaussian-approximated noise: summing three uniform random variables
     * produces a ~normal distribution (central limit theorem) cheaply.
     *
     * Threshold breaches: every 72nd index (~6 h) has a 5% chance of emitting
     * an out-of-range value so alert evaluation has real data to fire on.
     */
    private function generateNumericValue(array $profile, int $index, int $total): float
    {
        $radians    = ($index % 288) * (2 * M_PI / 288);
        $cycle      = sin($radians - M_PI / 2);
        $gaussNoise = (rand(-1000, 1000) + rand(-1000, 1000) + rand(-1000, 1000)) / 3000.0;

        $value = $profile['base']
            + ($profile['daily_amplitude'] * $cycle)
            + ($gaussNoise * $profile['noise']);

        if ($index % 72 === 0 && rand(1, 100) <= 5) {
            if ($profile['breach_high'] !== null && rand(0, 1)) {
                $value = $profile['breach_high'] + rand(0, 100) / 100.0;
            } elseif ($profile['breach_low'] !== null) {
                $value = $profile['breach_low'] - rand(0, 100) / 100.0;
            }
        }

        return round($value, $profile['decimals']);
    }
}
