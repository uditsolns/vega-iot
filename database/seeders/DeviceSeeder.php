<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $acme = DB::table('companies')->where('name', 'Acme Corporation')->first();
        $techvista = DB::table('companies')->where('name', 'TechVista Solutions')->first();
        $globalFoods = DB::table('companies')->where('name', 'Global Foods Inc')->first();

        $acmeAreas = DB::table('areas')
            ->join('hubs', 'areas.hub_id', '=', 'hubs.id')
            ->join('locations', 'hubs.location_id', '=', 'locations.id')
            ->where('locations.company_id', $acme->id)
            ->select('areas.id as area_id')
            ->limit(5)->get();

        $techvistaAreas = DB::table('areas')
            ->join('hubs', 'areas.hub_id', '=', 'hubs.id')
            ->join('locations', 'hubs.location_id', '=', 'locations.id')
            ->where('locations.company_id', $techvista->id)
            ->select('areas.id as area_id')
            ->limit(3)->get();

        $globalFoodsAreas = DB::table('areas')
            ->join('hubs', 'areas.hub_id', '=', 'hubs.id')
            ->join('locations', 'hubs.location_id', '=', 'locations.id')
            ->where('locations.company_id', $globalFoods->id)
            ->select('areas.id as area_id')
            ->limit(5)->get();

        // System inventory — Zion devices (model 1)
        for ($i = 1; $i <= 3; $i++) {
            $this->createDevice("SYS-{$i}", "SYS-INV-" . str_pad($i, 4, '0', STR_PAD_LEFT), 1);
        }

        // Acme deployed — Zion (model 1) and Ideabyte (model 3)
        foreach ($acmeAreas as $idx => $area) {
            $modelId = $idx % 2 === 0 ? 1 : 3;
            $deviceId = $this->createDevice(
                "ACME-{$idx}",
                'ACME-DEV-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                $modelId,
                $acme->id,
                $area->area_id,
                'Acme Device ' . ($idx + 1),
                'online'
            );
            $this->createSensors($deviceId, $modelId);
            $this->createDefaultConfig($deviceId);
        }

        // TechVista deployed — TZone TT19 (model 2)
        foreach ($techvistaAreas as $idx => $area) {
            $deviceId = $this->createDevice(
                "TECH-{$idx}",
                'TECH-DEV-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                2,
                $techvista->id,
                $area->area_id,
                'TechVista Sensor ' . ($idx + 1),
                'online'
            );
            $this->createSensors($deviceId, 2);
            $this->createDefaultConfig($deviceId);
        }

        // Global Foods deployed — Sunsui 4CH (model 5)
        foreach ($globalFoodsAreas as $idx => $area) {
            $deviceId = $this->createDevice(
                "GF-{$idx}",
                'GFOOD-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                5,
                $globalFoods->id,
                $area->area_id,
                'GF Sensor ' . ($idx + 1),
                'online'
            );
            // Sunsui 4CH is configurable — assign sensors manually
            $this->createSunsuiSensors($deviceId);
            $this->createDefaultConfig($deviceId);
        }
    }

    private function createDevice(
        string $uid,
        string $code,
        int $modelId,
        ?int $companyId = null,
        ?int $areaId = null,
        ?string $name = null,
        string $status = 'offline',
    ): int {
        return DB::table('devices')->insertGetId([
            'device_uid' => strtoupper(implode(':', str_split(str_pad(md5($uid), 12, '0'), 2))),
            'device_code' => $code,
            'device_model_id' => $modelId,
            'firmware_version' => 'v1.0.0',
            'company_id' => $companyId,
            'area_id' => $areaId,
            'device_name' => $name,
            'status' => $status,
            'is_active' => true,
            'last_reading_at' => $status === 'online' ? now()->subMinutes(5) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSensors(int $deviceId, int $modelId): void
    {
        $slots = DB::table('device_model_sensor_slots')
            ->where('device_model_id', $modelId)
            ->get();

        foreach ($slots as $slot) {
            DB::table('device_sensors')->insert([
                'device_id' => $deviceId,
                'slot_number' => $slot->slot_number,
                'sensor_type_id' => $slot->fixed_sensor_type_id,
                'is_enabled' => true,
                'label' => $slot->label,
                'accuracy' => $slot->accuracy,
                'resolution' => $slot->resolution,
                'measurement_range' => $slot->measurement_range,
                'created_by' => null,
                'created_at' => now(),
            ]);
        }

        $this->createSensorConfigs($deviceId);
    }

    private function createSunsuiSensors(int $deviceId): void
    {
        $assignments = [
            ['slot_number' => 1, 'sensor_type_id' => 1, 'label' => 'Temp'],
            ['slot_number' => 2, 'sensor_type_id' => 2, 'label' => 'Humidity'],
            ['slot_number' => 3, 'sensor_type_id' => 6, 'label' => 'Air Quality'],
            ['slot_number' => 4, 'sensor_type_id' => 7, 'label' => 'Sound', 'is_enabled' => false],
        ];

        foreach ($assignments as $a) {
            DB::table('device_sensors')->insert([
                'device_id' => $deviceId,
                'slot_number' => $a['slot_number'],
                'sensor_type_id' => $a['sensor_type_id'],
                'is_enabled' => $a['is_enabled'] ?? true,
                'label' => $a['label'],
                'accuracy' => null,
                'resolution' => null,
                'measurement_range' => null,
                'created_by' => null,
                'created_at' => now(),
            ]);
        }

        $this->createSensorConfigs($deviceId);
    }

    private function createSensorConfigs(int $deviceId): void
    {
        $sensors = DB::table('device_sensors')
            ->join('sensor_types', 'device_sensors.sensor_type_id', '=', 'sensor_types.id')
            ->where('device_sensors.device_id', $deviceId)
            ->where('sensor_types.supports_threshold_config', true)
            ->where('device_sensors.is_enabled', true)
            ->select('device_sensors.id', 'sensor_types.name as type_name')
            ->get();

        foreach ($sensors as $sensor) {
            $thresholds = match ($sensor->type_name) {
                'temperature' => ['min_critical' => 0, 'max_critical' => 35, 'min_warning' => 5, 'max_warning' => 30],
                'humidity' => ['min_critical' => 30, 'max_critical' => 90, 'min_warning' => 40, 'max_warning' => 80],
                'air_quality' => ['min_critical' => null, 'max_critical' => 1000, 'min_warning' => null, 'max_warning' => 600],
                'sound' => ['min_critical' => null, 'max_critical' => 90, 'min_warning' => null, 'max_warning' => 70],
                default => ['min_critical' => null, 'max_critical' => null, 'min_warning' => null, 'max_warning' => null],
            };

            DB::table('sensor_configurations')->insert([
                ...$thresholds,
                'device_sensor_id' => $sensor->id,
                'effective_from' => now(),
                'effective_to' => null,
                'updated_by' => null,
                'created_at' => now(),
            ]);
        }
    }

    private function createDefaultConfig(int $deviceId): void
    {
        DB::table('device_configurations')->insert([
            'device_id' => $deviceId,
            'recording_interval' => 5,
            'sending_interval' => 5,
            'wifi_ssid' => null,
            'wifi_password' => null,
            'wifi_mode' => 'WPA2',
            'timezone_offset_minutes' => 330,
            'effective_from' => now(),
            'effective_to' => null,
            'last_synced_at' => null,
            'updated_by' => null,
            'created_at' => now(),
        ]);
    }
}
