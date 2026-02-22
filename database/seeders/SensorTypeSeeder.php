<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sensor_types')->insert([
            ['id' => 1, 'name' => 'temperature', 'unit' => '°C', 'data_type' => 'decimal', 'min_value' => -100, 'max_value' => 200, 'supports_threshold_config' => true, 'created_at' => now()],
            ['id' => 2, 'name' => 'humidity', 'unit' => '%', 'data_type' => 'decimal', 'min_value' => 0, 'max_value' => 100, 'supports_threshold_config' => true, 'created_at' => now()],
            ['id' => 3, 'name' => 'gps', 'unit' => null, 'data_type' => 'point', 'min_value' => null, 'max_value' => null, 'supports_threshold_config' => false, 'created_at' => now()],
            ['id' => 4, 'name' => 'lux', 'unit' => 'lux', 'data_type' => 'decimal', 'min_value' => 0, 'max_value' => 100000, 'supports_threshold_config' => true, 'created_at' => now()],
            ['id' => 5, 'name' => 'vibration', 'unit' => 'g', 'data_type' => 'decimal', 'min_value' => 0, 'max_value' => 100, 'supports_threshold_config' => true, 'created_at' => now()],
            ['id' => 6, 'name' => 'air_quality', 'unit' => 'ppm', 'data_type' => 'decimal', 'min_value' => 0, 'max_value' => 10000, 'supports_threshold_config' => true, 'created_at' => now()],
            ['id' => 7, 'name' => 'sound', 'unit' => 'dB', 'data_type' => 'decimal', 'min_value' => 0, 'max_value' => 200, 'supports_threshold_config' => true, 'created_at' => now()],
        ]);
    }
}
