<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceModelSeeder extends Seeder
{
    public function run(): void
    {
        // --- Device Models ---
        DB::table('device_models')->insert([
            ['vendor' => 'zion', 'model_name' => 'Z-TH-Probe', 'description' => 'Temp + Humidity + External Probe', 'max_slots' => 3, 'is_configurable' => false, 'data_format' => null, 'created_at' => now(), 'updated_at' => now()],
            ['vendor' => 'tzone', 'model_name' => 'TT19', 'description' => 'Temp x2, Humidity, GPS, LUX, Vibration', 'max_slots' => 6, 'is_configurable' => false, 'data_format' => null, 'created_at' => now(), 'updated_at' => now()],
            ['vendor' => 'ideabyte', 'model_name' => 'DT-TH', 'description' => 'Dual Temp + Dual Humidity', 'max_slots' => 4, 'is_configurable' => false, 'data_format' => null, 'created_at' => now(), 'updated_at' => now()],
            ['vendor' => 'aliter', 'model_name' => '4CH', 'description' => '4 Configurable Channels (Temp/Humidity)', 'max_slots' => 4, 'is_configurable' => true, 'data_format' => null, 'created_at' => now(), 'updated_at' => now()],
            ['vendor' => 'sunsui', 'model_name' => '4CH', 'description' => '4 Configurable Channels (Temp/Humidity/Air/Sound)', 'max_slots' => 4, 'is_configurable' => true, 'data_format' => null, 'created_at' => now(), 'updated_at' => now()],
            ['vendor' => 'sunsui', 'model_name' => '8CH', 'description' => '8 Configurable Channels (Temp/Humidity)', 'max_slots' => 8, 'is_configurable' => true, 'data_format' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- Sensor Slots ---
        $slots = [
            // Zion Z-TH-Probe (fixed)
            ['device_model_id' => 1, 'slot_number' => 1, 'fixed_sensor_type_id' => 1, 'label' => 'Temp Internal', 'accuracy' => '±0.3°C', 'resolution' => '0.05°C', 'measurement_range' => '-40 to 125°C'],
            ['device_model_id' => 1, 'slot_number' => 2, 'fixed_sensor_type_id' => 2, 'label' => 'Humidity', 'accuracy' => '±2% RH', 'resolution' => '1%', 'measurement_range' => '0-100% RH'],
            ['device_model_id' => 1, 'slot_number' => 3, 'fixed_sensor_type_id' => 1, 'label' => 'Temp Probe', 'accuracy' => '±0.5°C', 'resolution' => '0.1°C', 'measurement_range' => '-40 to 125°C'],
            // TZone TT19 (fixed)
            ['device_model_id' => 2, 'slot_number' => 1, 'fixed_sensor_type_id' => 1, 'label' => 'Temp 1', 'accuracy' => '±0.3°C', 'resolution' => '0.1°C', 'measurement_range' => '-40 to 85°C'],
            ['device_model_id' => 2, 'slot_number' => 2, 'fixed_sensor_type_id' => 1, 'label' => 'Temp 2', 'accuracy' => '±0.3°C', 'resolution' => '0.1°C', 'measurement_range' => '-40 to 85°C'],
            ['device_model_id' => 2, 'slot_number' => 3, 'fixed_sensor_type_id' => 2, 'label' => 'Humidity', 'accuracy' => '±3% RH', 'resolution' => '1%', 'measurement_range' => '0-100% RH'],
            ['device_model_id' => 2, 'slot_number' => 4, 'fixed_sensor_type_id' => 3, 'label' => 'GPS', 'accuracy' => '±2.5m', 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 2, 'slot_number' => 5, 'fixed_sensor_type_id' => 4, 'label' => 'LUX', 'accuracy' => '±5%', 'resolution' => '1 lux', 'measurement_range' => '0-100000 lux'],
            ['device_model_id' => 2, 'slot_number' => 6, 'fixed_sensor_type_id' => 5, 'label' => 'Vibration', 'accuracy' => '±0.1g', 'resolution' => '0.01g', 'measurement_range' => '0-16g'],
            // Ideabyte DT-TH (fixed)
            ['device_model_id' => 3, 'slot_number' => 1, 'fixed_sensor_type_id' => 1, 'label' => 'Temp 1', 'accuracy' => '±0.3°C', 'resolution' => '0.1°C', 'measurement_range' => '-40 to 85°C'],
            ['device_model_id' => 3, 'slot_number' => 2, 'fixed_sensor_type_id' => 1, 'label' => 'Temp 2', 'accuracy' => '±0.3°C', 'resolution' => '0.1°C', 'measurement_range' => '-40 to 85°C'],
            ['device_model_id' => 3, 'slot_number' => 3, 'fixed_sensor_type_id' => 2, 'label' => 'Humidity 1', 'accuracy' => '±2% RH', 'resolution' => '1%', 'measurement_range' => '0-100% RH'],
            ['device_model_id' => 3, 'slot_number' => 4, 'fixed_sensor_type_id' => 2, 'label' => 'Humidity 2', 'accuracy' => '±2% RH', 'resolution' => '1%', 'measurement_range' => '0-100% RH'],
            // Aliter 4CH (configurable — null fixed_sensor_type_id)
            ['device_model_id' => 4, 'slot_number' => 1, 'fixed_sensor_type_id' => null, 'label' => 'Channel 1', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 4, 'slot_number' => 2, 'fixed_sensor_type_id' => null, 'label' => 'Channel 2', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 4, 'slot_number' => 3, 'fixed_sensor_type_id' => null, 'label' => 'Channel 3', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 4, 'slot_number' => 4, 'fixed_sensor_type_id' => null, 'label' => 'Channel 4', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            // Sunsui 4CH (configurable)
            ['device_model_id' => 5, 'slot_number' => 1, 'fixed_sensor_type_id' => null, 'label' => 'Channel 1', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 5, 'slot_number' => 2, 'fixed_sensor_type_id' => null, 'label' => 'Channel 2', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 5, 'slot_number' => 3, 'fixed_sensor_type_id' => null, 'label' => 'Channel 3', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            ['device_model_id' => 5, 'slot_number' => 4, 'fixed_sensor_type_id' => null, 'label' => 'Channel 4', 'accuracy' => null, 'resolution' => null, 'measurement_range' => null],
            // Sunsui 8CH (configurable)
            ...array_map(fn($i) => ['device_model_id' => 6, 'slot_number' => $i, 'fixed_sensor_type_id' => null, 'label' => "Channel {$i}", 'accuracy' => null, 'resolution' => null, 'measurement_range' => null], range(1, 8)),
        ];

        DB::table('device_model_sensor_slots')->insert(array_map(fn($s) => [...$s, 'created_at' => now()], $slots));

        // --- Available Sensors for Configurable Models ---
        DB::table('device_model_available_sensors')->insert([
            // Aliter 4CH: temp, humidity
            ['device_model_id' => 4, 'sensor_type_id' => 1],
            ['device_model_id' => 4, 'sensor_type_id' => 2],
            // Sunsui 4CH: temp, humidity, air_quality, sound
            ['device_model_id' => 5, 'sensor_type_id' => 1],
            ['device_model_id' => 5, 'sensor_type_id' => 2],
            ['device_model_id' => 5, 'sensor_type_id' => 6],
            ['device_model_id' => 5, 'sensor_type_id' => 7],
            // Sunsui 8CH: temp, humidity
            ['device_model_id' => 6, 'sensor_type_id' => 1],
            ['device_model_id' => 6, 'sensor_type_id' => 2],
        ]);
    }
}
