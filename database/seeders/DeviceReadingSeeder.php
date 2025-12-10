<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeviceReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all online devices with their hierarchy
        $devices = DB::table('devices')
            ->join('areas', 'devices.area_id', '=', 'areas.id')
            ->join('hubs', 'areas.hub_id', '=', 'hubs.id')
            ->join('locations', 'hubs.location_id', '=', 'locations.id')
            ->where('devices.status', 'online')
            ->whereNotNull('devices.area_id')
            ->select(
                'devices.id',
                'devices.type',
                'devices.company_id',
                'locations.id as location_id',
                'hubs.id as hub_id',
                'areas.id as area_id',
                'devices.firmware_version'
            )
            ->get();

        if ($devices->isEmpty()) {
            $this->command->warn('No online devices found. Skipping readings seeding.');
            return;
        }

        $this->command->info("Seeding readings for {$devices->count()} online devices...");

        $readings = [];
        $batchSize = 1000;

        foreach ($devices as $device) {
            // Generate readings for the last 7 days
            $readings = array_merge(
                $readings,
                $this->generateReadingsForDevice($device, 7)
            );

            // Insert in batches to avoid memory issues
            if (count($readings) >= $batchSize) {
                DB::table('device_readings')->insert($readings);
                $this->command->info("Inserted " . count($readings) . " readings...");
                $readings = [];
            }
        }

        // Insert remaining readings
        if (!empty($readings)) {
            DB::table('device_readings')->insert($readings);
            $this->command->info("Inserted final " . count($readings) . " readings.");
        }

        $this->command->info('Device readings seeded successfully!');
    }

    /**
     * Generate realistic readings for a device over specified days
     */
    private function generateReadingsForDevice(object $device, int $days): array
    {
        $readings = [];
        $now = Carbon::now();
        $interval = 5; // 5 minutes between readings

        // Calculate total readings: 288 readings per day (every 5 minutes)
        $totalReadings = $days * 24 * 60 / $interval;

        // Base values with some variation
        $baseTemp = 22.0;
        $baseHumidity = 55.0;
        $baseTempProbe = 5.0;
        $baseBatteryVoltage = 3.6;
        $baseBatteryPercentage = 85;
        $baseWifiSignal = -45;

        for ($i = 0; $i < $totalReadings; $i++) {
            $recordedAt = $now->copy()->subMinutes($i * $interval);

            // Add realistic variations
            $tempVariation = sin($i / 12) * 2 + (rand(-10, 10) / 10); // Daily cycle + noise
            $humidityVariation = cos($i / 15) * 5 + (rand(-20, 20) / 10);
            $tempProbeVariation = sin($i / 10) * 1.5 + (rand(-5, 5) / 10);

            // Simulate battery drain (very slow)
            $batteryDrain = ($i / $totalReadings) * 5; // Lose ~5% over the period

            $reading = [
                'device_id' => $device->id,
                'recorded_at' => $recordedAt,
                'received_at' => $recordedAt->copy()->addSeconds(rand(1, 30)),
                'company_id' => $device->company_id,
                'location_id' => $device->location_id,
                'hub_id' => $device->hub_id,
                'area_id' => $device->area_id,
                'temperature' => round($baseTemp + $tempVariation, 2),
                'humidity' => round(max(30, min(90, $baseHumidity + $humidityVariation)), 2),
                'battery_voltage' => round(max(3.0, $baseBatteryVoltage - ($batteryDrain / 100)), 2),
                'battery_percentage' => max(0, (int)($baseBatteryPercentage - $batteryDrain)),
                'wifi_signal_strength' => $baseWifiSignal + rand(-5, 5),
                'firmware_version' => $device->firmware_version,
                'raw_payload' => json_encode([
                    'ts' => $recordedAt->timestamp,
                    't' => round($baseTemp + $tempVariation, 2),
                    'h' => round($baseHumidity + $humidityVariation, 2),
                ]),
            ];

            // Add temp_probe only for dual sensor devices
            if ($device->type === 'dual_temp_humidity') {
                $reading['temp_probe'] = round($baseTempProbe + $tempProbeVariation, 2);
                $reading['raw_payload'] = json_encode([
                    'ts' => $recordedAt->timestamp,
                    't' => round($baseTemp + $tempVariation, 2),
                    'h' => round($baseHumidity + $humidityVariation, 2),
                    'tp' => round($baseTempProbe + $tempProbeVariation, 2),
                ]);
            } else {
                $reading['temp_probe'] = null;
            }

            $readings[] = $reading;

            // Occasionally create readings that breach thresholds (for alert generation)
            // Every ~50th reading might be outside normal range
            if ($i % 50 === 0 && rand(0, 1)) {
                $anomalyReading = $reading;
                $anomalyReading['recorded_at'] = $recordedAt->copy()->addMinutes(2);
                $anomalyReading['received_at'] = $anomalyReading['recorded_at']->copy()->addSeconds(rand(1, 30));

                // Create an anomaly
                if (rand(0, 1)) {
                    // High temperature
                    $anomalyReading['temperature'] = round(32 + rand(0, 50) / 10, 2);
                } else {
                    // Low temperature
                    $anomalyReading['temperature'] = round(3 - rand(0, 30) / 10, 2);
                }

                $readings[] = $anomalyReading;
            }
        }

        return $readings;
    }
}
