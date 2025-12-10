<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                "company_id" => null,
                "name" => "Super Admin",
                "description" => "System administrator with full access",
                "hierarchy_level" => 1,
                "is_system_role" => true,
                "is_editable" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => null,
                "name" => "Company Admin",
                "description" =>
                    "Company administrator with full company access",
                "hierarchy_level" => 10,
                "is_system_role" => true,
                "is_editable" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => null,
                "name" => "Manager",
                "description" => "Location/area manager",
                "hierarchy_level" => 20,
                "is_system_role" => true,
                "is_editable" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => null,
                "name" => "Technician",
                "description" => "Device technician",
                "hierarchy_level" => 30,
                "is_system_role" => true,
                "is_editable" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => null,
                "name" => "Operator",
                "description" => "Alert operator",
                "hierarchy_level" => 40,
                "is_system_role" => true,
                "is_editable" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "company_id" => null,
                "name" => "Viewer",
                "description" => "Read-only access",
                "hierarchy_level" => 50,
                "is_system_role" => true,
                "is_editable" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        DB::table("roles")->insert($roles);
    }
}
