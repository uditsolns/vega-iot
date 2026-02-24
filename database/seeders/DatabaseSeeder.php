<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            CompanySeeder::class,
            LocationSeeder::class,
            UserSeeder::class,
            SensorTypeSeeder::class,
            DeviceModelSeeder::class,
            DeviceSeeder::class,
            SensorReadingSeeder::class,
        ]);
    }
}
