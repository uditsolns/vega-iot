<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get companies
        $acme = DB::table("companies")
            ->where("name", "Acme Corporation")
            ->first();
        $techvista = DB::table("companies")
            ->where("name", "TechVista Solutions")
            ->first();
        $globalFoods = DB::table("companies")
            ->where("name", "Global Foods Inc")
            ->first();

        // Get some areas for device assignment
        $acmeAreas = DB::table("areas")
            ->join("hubs", "areas.hub_id", "=", "hubs.id")
            ->join("locations", "hubs.location_id", "=", "locations.id")
            ->where("locations.company_id", $acme->id)
            ->select(
                "areas.id as area_id",
                "hubs.id as hub_id",
                "locations.id as location_id",
            )
            ->limit(5)
            ->get();

        $techvistaAreas = DB::table("areas")
            ->join("hubs", "areas.hub_id", "=", "hubs.id")
            ->join("locations", "hubs.location_id", "=", "locations.id")
            ->where("locations.company_id", $techvista->id)
            ->select(
                "areas.id as area_id",
                "hubs.id as hub_id",
                "locations.id as location_id",
            )
            ->limit(3)
            ->get();

        $globalFoodsAreas = DB::table("areas")
            ->join("hubs", "areas.hub_id", "=", "hubs.id")
            ->join("locations", "hubs.location_id", "=", "locations.id")
            ->where("locations.company_id", $globalFoods->id)
            ->select(
                "areas.id as area_id",
                "hubs.id as hub_id",
                "locations.id as location_id",
            )
            ->limit(5)
            ->get();

        $devices = [];

        // System Inventory Devices (not assigned to any company)
        for ($i = 1; $i <= 5; $i++) {
            $devices[] = [
                "device_uid" => $this->generateDeviceUid(),
                "device_code" => "SYS-INV-" . str_pad($i, 4, "0", STR_PAD_LEFT),
                "make" => "VEGA",
                "model" => "Alpha",
                "type" =>
                    $i % 2 === 0
                        ? "dual_temp_humidity"
                        : "single_temp_humidity",
                "firmware_version" => "1.0.0",
                "api_key" => Str::random(64),
                "temp_resolution" => 0.1,
                "temp_accuracy" => 0.5,
                "humidity_resolution" => 1.0,
                "humidity_accuracy" => 3.0,
                "temp_probe_resolution" => $i % 2 === 0 ? 0.1 : null,
                "temp_probe_accuracy" => $i % 2 === 0 ? 0.5 : null,
                "company_id" => null,
                "area_id" => null,
                "device_name" => null,
                "status" => "offline",
                "is_active" => true,
                "last_reading_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ];
        }

        // Acme Company Inventory Devices (assigned to company but not to area)
        for ($i = 1; $i <= 3; $i++) {
            $devices[] = [
                "device_uid" => $this->generateDeviceUid(),
                "device_code" =>
                    "ACME-INV-" . str_pad($i, 4, "0", STR_PAD_LEFT),
                "make" => "VEGA",
                "model" => "Alpha",
                "type" =>
                    $i % 2 === 0
                        ? "dual_temp_humidity"
                        : "single_temp_humidity",
                "firmware_version" => "1.0.0",
                "api_key" => Str::random(64),
                "temp_resolution" => 0.1,
                "temp_accuracy" => 0.5,
                "humidity_resolution" => 1.0,
                "humidity_accuracy" => 3.0,
                "temp_probe_resolution" => $i % 2 === 0 ? 0.1 : null,
                "temp_probe_accuracy" => $i % 2 === 0 ? 0.5 : null,
                "company_id" => $acme->id,
                "area_id" => null,
                "device_name" => null,
                "status" => "offline",
                "is_active" => true,
                "last_reading_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ];
        }

        // Acme Deployed Devices (assigned to areas)
        foreach ($acmeAreas as $index => $areaData) {
            $deviceNum = $index + 1;

            $deviceId = DB::table("devices")->insertGetId([
                "device_uid" => $this->generateDeviceUid(),
                "device_code" =>
                    "ACME-DEV-" . str_pad($deviceNum, 4, "0", STR_PAD_LEFT),
                "make" => "VEGA",
                "model" => "Alpha",
                "type" => "dual_temp_humidity",
                "firmware_version" => "1.0.0",
                "api_key" => Str::random(64),
                "temp_resolution" => 0.1,
                "temp_accuracy" => 0.5,
                "humidity_resolution" => 1.0,
                "humidity_accuracy" => 3.0,
                "temp_probe_resolution" => 0.1,
                "temp_probe_accuracy" => 0.5,
                "company_id" => $acme->id,
                "area_id" => $areaData->area_id,
                "device_name" => "Acme Device " . $deviceNum,
                "status" => $deviceNum % 3 === 0 ? "offline" : "online",
                "is_active" => true,
                "last_reading_at" =>
                    $deviceNum % 3 === 0
                        ? now()->subHours(2)
                        : now()->subMinutes(5),
                "created_at" => now(),
                "updated_at" => now(),
            ]);

            // Create device configuration
            $this->createDeviceConfiguration($deviceId);
        }

        // TechVista Deployed Devices
        foreach ($techvistaAreas as $index => $areaData) {
            $deviceNum = $index + 1;

            $deviceId = DB::table("devices")->insertGetId([
                "device_uid" => $this->generateDeviceUid(),
                "device_code" =>
                    "TECH-DEV-" . str_pad($deviceNum, 4, "0", STR_PAD_LEFT),
                "make" => "VEGA",
                "model" => "Alpha",
                "type" =>
                    $deviceNum % 2 === 0
                        ? "dual_temp_humidity"
                        : "single_temp_humidity",
                "firmware_version" => "1.0.0",
                "api_key" => Str::random(64),
                "temp_resolution" => 0.1,
                "temp_accuracy" => 0.5,
                "humidity_resolution" => 1.0,
                "humidity_accuracy" => 3.0,
                "temp_probe_resolution" => $deviceNum % 2 === 0 ? 0.1 : null,
                "temp_probe_accuracy" => $deviceNum % 2 === 0 ? 0.5 : null,
                "company_id" => $techvista->id,
                "area_id" => $areaData->area_id,
                "device_name" => "TechVista Sensor " . $deviceNum,
                "status" => "online",
                "is_active" => true,
                "last_reading_at" => now()->subMinutes(2),
                "created_at" => now(),
                "updated_at" => now(),
            ]);

            // Create device configuration
            $this->createDeviceConfiguration($deviceId);
        }

        // Global Foods Deployed Devices
        foreach ($globalFoodsAreas as $index => $areaData) {
            $deviceNum = $index + 1;

            $deviceId = DB::table("devices")->insertGetId([
                "device_uid" => $this->generateDeviceUid(),
                "device_code" =>
                    "GFOOD-" . str_pad($deviceNum, 4, "0", STR_PAD_LEFT),
                "make" => "VEGA",
                "model" => "Alpha",
                "type" => "dual_temp_humidity",
                "firmware_version" => "1.0.0",
                "api_key" => Str::random(64),
                "temp_resolution" => 0.1,
                "temp_accuracy" => 0.5,
                "humidity_resolution" => 1.0,
                "humidity_accuracy" => 3.0,
                "temp_probe_resolution" => 0.1,
                "temp_probe_accuracy" => 0.5,
                "company_id" => $globalFoods->id,
                "area_id" => $areaData->area_id,
                "device_name" => "GF Sensor " . $deviceNum,
                "status" => "online",
                "is_active" => true,
                "last_reading_at" => now()->subMinutes(10),
                "created_at" => now(),
                "updated_at" => now(),
            ]);

            // Create device configuration
            $this->createDeviceConfiguration($deviceId);
        }

        // Insert non-deployed devices
        DB::table("devices")->insert($devices);
    }

    private function generateDeviceUid(): string
    {
        $parts = [];
        for ($i = 0; $i < 6; $i++) {
            $parts[] = strtoupper(Str::random(2));
        }
        return implode(":", $parts);
    }

    private function createDeviceConfiguration(int $deviceId): void
    {
        DB::table("device_configurations")->insert([
            "device_id" => $deviceId,
            // Temperature thresholds (internal sensor)
            "temp_min_critical" => 0.0,
            "temp_max_critical" => 35.0,
            "temp_min_warning" => 5.0,
            "temp_max_warning" => 30.0,
            // Humidity thresholds
            "humidity_min_critical" => 30.0,
            "humidity_max_critical" => 90.0,
            "humidity_min_warning" => 40.0,
            "humidity_max_warning" => 80.0,
            // Temperature probe thresholds
            "temp_probe_min_critical" => -5.0,
            "temp_probe_max_critical" => 10.0,
            "temp_probe_min_warning" => 0.0,
            "temp_probe_max_warning" => 8.0,
            // Recording intervals (in minutes)
            "record_interval" => 5,
            "send_interval" => 15,
            // WiFi configuration
            "wifi_ssid" => null,
            "wifi_password" => null,
            // Active sensor selection
            "active_temp_sensor" => "INT",
            // Tracking
            "is_current" => true,
            "updated_by" => null,
            "created_at" => now(),
            "updated_at" => now(),
        ]);
    }
}
