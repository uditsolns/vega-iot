<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get companies
        $acme = DB::table("companies")->where("name", "Acme Corporation")->first();
        $techvista = DB::table("companies")->where("name", "TechVista Solutions")->first();
        $globalFoods = DB::table("companies")->where("name", "Global Foods Inc")->first();

        // Locations
        $locations = [
            // Acme Corporation locations
            [
                "company_id" => $acme->id,
                "name" => "Acme Headquarters",
                "address" => "123 Business St",
                "city" => "New York",
                "state" => "NY",
                "country" => "USA",
                "timezone" => "America/New_York",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $acme->id,
                "name" => "Acme West Coast",
                "address" => "456 Pacific Ave",
                "city" => "Los Angeles",
                "state" => "CA",
                "country" => "USA",
                "timezone" => "America/Los_Angeles",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // TechVista Solutions locations
            [
                "company_id" => $techvista->id,
                "name" => "TechVista Main Campus",
                "address" => "456 Innovation Ave",
                "city" => "San Francisco",
                "state" => "CA",
                "country" => "USA",
                "timezone" => "America/Los_Angeles",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // Global Foods Inc locations
            [
                "company_id" => $globalFoods->id,
                "name" => "Global Foods Warehouse #1",
                "address" => "789 Commerce Blvd",
                "city" => "Chicago",
                "state" => "IL",
                "country" => "USA",
                "timezone" => "America/Chicago",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $globalFoods->id,
                "name" => "Global Foods Distribution Center",
                "address" => "321 Logistics Dr",
                "city" => "Atlanta",
                "state" => "GA",
                "country" => "USA",
                "timezone" => "America/New_York",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        foreach ($locations as $location) {
            $locationId = DB::table("locations")->insertGetId($location);
            $this->createHubsForLocation($locationId);
        }
    }

    private function createHubsForLocation(int $locationId): void
    {
        $hubs = [
            [
                "location_id" => $locationId,
                "name" => "Building A",
                "description" => "Main building",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "location_id" => $locationId,
                "name" => "Building B",
                "description" => "Secondary building",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        foreach ($hubs as $hub) {
            $hubId = DB::table("hubs")->insertGetId($hub);
            $this->createAreasForHub($hubId);
        }
    }

    private function createAreasForHub(int $hubId): void
    {
        $areas = [
            [
                "hub_id" => $hubId,
                "name" => "Cold Storage",
                "description" => "Temperature controlled storage area",
                "is_active" => true,
                // Alert channel configuration
                "alert_email_enabled" => true,
                "alert_sms_enabled" => false,
                "alert_voice_enabled" => false,
                "alert_push_enabled" => false,
                // Notification types enabled
                "alert_warning_enabled" => true,
                "alert_critical_enabled" => true,
                "alert_back_in_range_enabled" => true,
                "alert_device_status_enabled" => true,
                // Notification interval for acknowledged alerts (in hours)
                "acknowledged_alert_notification_interval" => 24,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "hub_id" => $hubId,
                "name" => "Server Room",
                "description" => "IT infrastructure area",
                "is_active" => true,
                // Alert channel configuration
                "alert_email_enabled" => true,
                "alert_sms_enabled" => true,
                "alert_voice_enabled" => false,
                "alert_push_enabled" => false,
                // Notification types enabled
                "alert_warning_enabled" => true,
                "alert_critical_enabled" => true,
                "alert_back_in_range_enabled" => true,
                "alert_device_status_enabled" => true,
                // Notification interval for acknowledged alerts (in hours)
                "acknowledged_alert_notification_interval" => 12,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "hub_id" => $hubId,
                "name" => "Warehouse Floor",
                "description" => "General storage and operations",
                "is_active" => true,
                // Alert channel configuration
                "alert_email_enabled" => true,
                "alert_sms_enabled" => false,
                "alert_voice_enabled" => false,
                "alert_push_enabled" => false,
                // Notification types enabled
                "alert_warning_enabled" => true,
                "alert_critical_enabled" => true,
                "alert_back_in_range_enabled" => true,
                "alert_device_status_enabled" => true,
                // Notification interval for acknowledged alerts (in hours)
                "acknowledged_alert_notification_interval" => 24,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        DB::table("areas")->insert($areas);
    }
}
