<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $sa = DB::table("roles")->where("name", "Super Admin")->first();
        $ca = DB::table("roles")->where("name", "Company Admin")->first();
        $mg = DB::table("roles")->where("name", "Manager")->first();

        $acme = DB::table("companies")->where("name", "Acme Corporation")->first();
        $tech = DB::table("companies")->where("name", "TechVista Solutions")->first();
        $food = DB::table("companies")->where("name", "Global Foods Inc")->first();

        $users = [
            // System Admin
            ["company_id" => null, "role_id" => $sa->id, "first_name" => "System", "last_name" => "Admin", "email" => "admin@system.com", "password" => Hash::make("password"), "phone" => "+1-555-0001", "is_active" => true, "created_at" => now(), "updated_at" => now()],

            // Acme
            ["company_id" => $acme->id, "role_id" => $ca->id, "first_name" => "John", "last_name" => "Smith", "email" => "john.smith@acme.com", "password" => Hash::make("password"), "phone" => "+1-555-0101", "is_active" => true, "created_at" => now(), "updated_at" => now()],
            ["company_id" => $acme->id, "role_id" => $mg->id, "first_name" => "Sarah", "last_name" => "Johnson", "email" => "sarah.johnson@acme.com", "password" => Hash::make("password"), "phone" => "+1-555-0102", "is_active" => true, "created_at" => now(), "updated_at" => now()],
            ["company_id" => $acme->id, "role_id" => $mg->id, "first_name" => "Mike", "last_name" => "Davis", "email" => "mike.davis@acme.com", "password" => Hash::make("password"), "phone" => "+1-555-0103", "is_active" => true, "created_at" => now(), "updated_at" => now()],

            // TechVista
            ["company_id" => $tech->id, "role_id" => $ca->id, "first_name" => "Jennifer", "last_name" => "Lee", "email" => "jennifer.lee@techvista.com", "password" => Hash::make("password"), "phone" => "+1-555-0201", "is_active" => true, "created_at" => now(), "updated_at" => now()],
            ["company_id" => $tech->id, "role_id" => $mg->id, "first_name" => "David", "last_name" => "Chen", "email" => "david.chen@techvista.com", "password" => Hash::make("password"), "phone" => "+1-555-0202", "is_active" => true, "created_at" => now(), "updated_at" => now()],

            // Global Foods
            ["company_id" => $food->id, "role_id" => $ca->id, "first_name" => "Maria", "last_name" => "Garcia", "email" => "maria.garcia@globalfoods.com", "password" => Hash::make("password"), "phone" => "+1-555-0301", "is_active" => true, "created_at" => now(), "updated_at" => now()],
            ["company_id" => $food->id, "role_id" => $mg->id, "first_name" => "James", "last_name" => "Martinez", "email" => "james.martinez@globalfoods.com", "password" => Hash::make("password"), "phone" => "+1-555-0302", "is_active" => true, "created_at" => now(), "updated_at" => now()],
            ["company_id" => $food->id, "role_id" => $mg->id, "first_name" => "Lisa", "last_name" => "Anderson", "email" => "lisa.anderson@globalfoods.com", "password" => Hash::make("password"), "phone" => "+1-555-0303", "is_active" => true, "created_at" => now(), "updated_at" => now()],
        ];

        foreach ($users as $user) {
            DB::table("users")->insert($user);
        }

        // Grant area access (restrictions)
        $sarah = DB::table("users")->where("email", "sarah.johnson@acme.com")->first();
        $acmeAreas = DB::table("areas")->join("hubs", "areas.hub_id", "=", "hubs.id")->join("locations", "hubs.location_id", "=", "locations.id")->join("companies", "locations.company_id", "=", "companies.id")->where("companies.name", "Acme Corporation")->select("areas.id")->limit(3)->get();
        foreach ($acmeAreas as $area) {
            DB::table("user_area_access")->insert(["user_id" => $sarah->id, "area_id" => $area->id, "granted_at" => now(), "granted_by" => null]);
        }
    }
}
