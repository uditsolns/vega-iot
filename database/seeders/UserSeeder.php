<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $superAdmin = DB::table("roles")->where("name", "Super Admin")->first();
        $companyAdmin = DB::table("roles")
            ->where("name", "Company Admin")
            ->first();
        $manager = DB::table("roles")->where("name", "Manager")->first();
        $technician = DB::table("roles")->where("name", "Technician")->first();
        $operator = DB::table("roles")->where("name", "Operator")->first();
        $viewer = DB::table("roles")->where("name", "Viewer")->first();

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

        $users = [
            // Super Admin - No company affiliation
            [
                "company_id" => null,
                "role_id" => $superAdmin->id,
                "first_name" => "System",
                "last_name" => "Administrator",
                "email" => "admin@system.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0001",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // Acme Corporation Users
            [
                "company_id" => $acme->id,
                "role_id" => $companyAdmin->id,
                "first_name" => "John",
                "last_name" => "Smith",
                "email" => "john.smith@acme.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0101",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $acme->id,
                "role_id" => $manager->id,
                "first_name" => "Sarah",
                "last_name" => "Johnson",
                "email" => "sarah.johnson@acme.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0102",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $acme->id,
                "role_id" => $technician->id,
                "first_name" => "Mike",
                "last_name" => "Davis",
                "email" => "mike.davis@acme.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0103",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $acme->id,
                "role_id" => $operator->id,
                "first_name" => "Emily",
                "last_name" => "Wilson",
                "email" => "emily.wilson@acme.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0104",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $acme->id,
                "role_id" => $viewer->id,
                "first_name" => "Robert",
                "last_name" => "Brown",
                "email" => "robert.brown@acme.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0105",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // TechVista Solutions Users
            [
                "company_id" => $techvista->id,
                "role_id" => $companyAdmin->id,
                "first_name" => "Jennifer",
                "last_name" => "Lee",
                "email" => "jennifer.lee@techvista.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0201",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $techvista->id,
                "role_id" => $manager->id,
                "first_name" => "David",
                "last_name" => "Chen",
                "email" => "david.chen@techvista.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0202",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // Global Foods Inc Users
            [
                "company_id" => $globalFoods->id,
                "role_id" => $companyAdmin->id,
                "first_name" => "Maria",
                "last_name" => "Garcia",
                "email" => "maria.garcia@globalfoods.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0301",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $globalFoods->id,
                "role_id" => $manager->id,
                "first_name" => "James",
                "last_name" => "Martinez",
                "email" => "james.martinez@globalfoods.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0302",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => $globalFoods->id,
                "role_id" => $technician->id,
                "first_name" => "Lisa",
                "last_name" => "Anderson",
                "email" => "lisa.anderson@globalfoods.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-0303",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // Test user for notifications (Acme Manager with email and phone for testing)
            [
                "company_id" => $acme->id,
                "role_id" => $manager->id,
                "first_name" => "Tarachand",
                "last_name" => "Khorwal",
                "email" => "web.tarachand@gmail.com",
                "password" => Hash::make("password"),
                "phone" => "9136248458",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // Inactive user for testing
            [
                "company_id" => $acme->id,
                "role_id" => $viewer->id,
                "first_name" => "Inactive",
                "last_name" => "User",
                "email" => "inactive@acme.com",
                "password" => Hash::make("password"),
                "phone" => "+1-555-9999",
                "is_active" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table("users")->insert($user);
        }

        // Grant area access to some users (area restrictions)
        $this->grantAreaAccess();
    }

    private function grantAreaAccess(): void
    {
        // Get Sarah Johnson (Acme Manager) and restrict her to specific areas
        $sarah = DB::table("users")
            ->where("email", "sarah.johnson@acme.com")
            ->first();

        // Get some areas from Acme - areas -> hubs -> locations -> companies
        $acmeAreas = DB::table("areas")
            ->join("hubs", "areas.hub_id", "=", "hubs.id")
            ->join("locations", "hubs.location_id", "=", "locations.id")
            ->join("companies", "locations.company_id", "=", "companies.id")
            ->where("companies.name", "Acme Corporation")
            ->select("areas.id")
            ->limit(3)
            ->get();

        foreach ($acmeAreas as $area) {
            DB::table("user_area_access")->insert([
                "user_id" => $sarah->id,
                "area_id" => $area->id,
                "granted_at" => now(),
                "granted_by" => null,
            ]);
        }

        // Get David Chen (TechVista Manager) and restrict him to specific areas
        $david = DB::table("users")
            ->where("email", "david.chen@techvista.com")
            ->first();

        $techvistaAreas = DB::table("areas")
            ->join("hubs", "areas.hub_id", "=", "hubs.id")
            ->join("locations", "hubs.location_id", "=", "locations.id")
            ->join("companies", "locations.company_id", "=", "companies.id")
            ->where("companies.name", "TechVista Solutions")
            ->select("areas.id")
            ->limit(2)
            ->get();

        foreach ($techvistaAreas as $area) {
            DB::table("user_area_access")->insert([
                "user_id" => $david->id,
                "area_id" => $area->id,
                "granted_at" => now(),
                "granted_by" => null,
            ]);
        }
    }
}
