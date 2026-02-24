<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seeds the sensor_readings hypertable with realistic time-series data.
 *
 * Design notes:
 * - One row per (device_sensor_id, recorded_at) — sensor-centric schema
 * - Generates 7 days of readings at 5-minute intervals per sensor
 * - Simulates daily sinusoidal cycles + random noise per sensor type
 * - Occasional threshold breaches for alert testing
 * - Inserts in batches of 2000 to avoid memory issues
 */
class SensorReadingSeeder extends Seeder
{
    private const INTERVAL_MINUTES = 5;
    private const DAYS             = 7;
    private const BATCH_SIZE       = 2000;

    /**
     * Realistic base values and variation configs per sensor type name.
     * Keys match the `sensor_types.name` values from SensorTypeSeeder.
     */
    private array $sensorProfiles = [
        'temperature' => [
            'base'            => 22.0,
            'daily_amplitude' => 2.5,   // °C swing across the day
            'noise'           => 0.3,   // max random noise
            'breach_high'     => 36.0,  // above max_critical (35) for alert testing
            'breach_low'      => -1.0,  // below min_critical (0)
            'decimals'        => 2,
        ],
        'humidity' => [
            'base'            => 58.0,
            'daily_amplitude' => 6.0,
            'noise'           => 1.5,
            'breach_high'     => 92.0,  // above max_critical (90)
            'breach_low'      => 28.0,  // below min_critical (30)
            'decimals'        => 1,
        ],
        'lux' => [
            'base'            => 3000.0,
            'daily_amplitude' => 2800.0, // lights on/off cycle
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
            'base'            => 420.0,   // ppm CO2
            'daily_amplitude' => 150.0,
            'noise'           => 30.0,
            'breach_high'     => 1050.0,  // above max_critical (1000)
            'breach_low'      => null,
            'decimals'        => 0,
        ],
        'sound' => [
            'base'            => 42.0,    // dB
            'daily_amplitude' => 8.0,
            'noise'           => 5.0,
            'breach_high'     => 93.0,    // above max_critical (90)
            'breach_low'      => null,
            'decimals'        => 1,
        ],
        // GPS is stored as value_point, not value_numeric — skip numeric seeding
        'gps' => null,
    ];

    public function run(): void
    {
        // ── Fetch all deployed online devices with their sensors ──────────────
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

        $totalReadings    = 0;
        $batch            = [];
        $now              = Carbon::now();
        $totalIntervals   = (int) (self::DAYS * 24 * 60 / self::INTERVAL_MINUTES);

        foreach ($devices as $device) {
            // Load enabled sensors with their type names
            $sensors = DB::table('device_sensors')
                ->join('sensor_types', 'device_sensors.sensor_type_id', '=', 'sensor_types.id')
                ->where('device_sensors.device_id', $device->id)
                ->where('device_sensors.is_enabled', true)
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

            foreach ($sensors as $sensor) {
                // GPS uses point storage, not numeric — skip
                if ($sensor->data_type === 'point') {
                    continue;
                }

                $profile = $this->sensorProfiles[$sensor->type_name] ?? null;

                // Unknown sensor type — generate generic 0-100 values
                if ($profile === null) {
                    $profile = [
                        'base'            => 50.0,
                        'daily_amplitude' => 10.0,
                        'noise'           => 3.0,
                        'breach_high'     => null,
                        'breach_low'      => null,
                        'decimals'        => 2,
                    ];
                }

                for ($i = $totalIntervals - 1; $i >= 0; $i--) {
                    $recordedAt = $now->copy()->subMinutes($i * self::INTERVAL_MINUTES);
                    $value      = $this->generateValue($profile, $i, $totalIntervals);

                    // Metadata only on first reading of the day (to store battery etc.)
                    $metadata = null;
                    if ($i % (24 * 60 / self::INTERVAL_MINUTES) === 0) {
                        $batteryPct = max(0, 85 - (($totalIntervals - $i) / $totalIntervals) * 10);
                        $metadata   = json_encode([
                            'battery_pct'  => round($batteryPct),
                            'rssi'         => -45 + rand(-8, 8),
                        ]);
                    }

                    $batch[] = [
                        'device_id'        => $device->id,
                        'device_sensor_id' => $sensor->device_sensor_id,
                        'recorded_at'      => $recordedAt->toDateTimeString(),
                        'received_at'      => $recordedAt->copy()->addSeconds(rand(1, 20))->toDateTimeString(),
                        'company_id'       => $device->company_id,
                        'area_id'          => $device->area_id,
                        'value_numeric'    => $value,
                        'metadata'         => $metadata,
                    ];

                    if (count($batch) >= self::BATCH_SIZE) {
                        DB::table('sensor_readings')->insert($batch);
                        $totalReadings += count($batch);
                        $this->command->line("    Inserted {$totalReadings} readings so far...");
                        $batch = [];
                    }
                }
            }
        }

        // Flush remaining
        if (!empty($batch)) {
            DB::table('sensor_readings')->insert($batch);
            $totalReadings += count($batch);
        }

        $this->command->info("✓ Sensor readings seeded: {$totalReadings} total rows.");
        $this->command->info("  Covering " . self::DAYS . " days at " . self::INTERVAL_MINUTES . "-min intervals.");
    }

    /**
     * Generate a realistic value for a given sensor profile and time index.
     *
     * Uses a sinusoidal daily cycle + Gaussian-approximated noise.
     * Every ~72nd reading (6h) has a 5% chance of breaching a threshold,
     * so alert evaluation has something to fire on.
     */
    private function generateValue(array $profile, int $index, int $total): float
    {
        $base      = $profile['base'];
        $amplitude = $profile['daily_amplitude'];
        $noise     = $profile['noise'];
        $decimals  = $profile['decimals'];

        // Sinusoidal daily cycle (period = 288 readings = 24h)
        $radians = ($index % 288) * (2 * M_PI / 288);
        $cycle   = sin($radians - M_PI / 2); // starts low at midnight, peaks at noon

        // Gaussian-approximated noise (sum of 3 uniform random vars)
        $gaussNoise = (rand(-1000, 1000) + rand(-1000, 1000) + rand(-1000, 1000)) / 3000.0;

        $value = $base + ($amplitude * $cycle) + ($gaussNoise * $noise);

        // Threshold breaches: ~5% of every-72nd sample
        if ($index % 72 === 0 && rand(1, 100) <= 5) {
            if ($profile['breach_high'] !== null && rand(0, 1)) {
                $value = $profile['breach_high'] + rand(0, 100) / 100.0;
            } elseif ($profile['breach_low'] !== null) {
                $value = $profile['breach_low'] - rand(0, 100) / 100.0;
            }
        }

        return round($value, $decimals);
    }
}
