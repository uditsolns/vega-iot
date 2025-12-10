<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Companies
            [
                "name" => "companies.view",
                "resource" => "companies",
                "action" => "view",
                "description" => "View companies",
            ],
            [
                "name" => "companies.create",
                "resource" => "companies",
                "action" => "create",
                "description" => "Create companies",
            ],
            [
                "name" => "companies.update",
                "resource" => "companies",
                "action" => "update",
                "description" => "Update companies",
            ],
            [
                "name" => "companies.delete",
                "resource" => "companies",
                "action" => "delete",
                "description" => "Delete companies",
            ],

            // Locations
            [
                "name" => "locations.view",
                "resource" => "locations",
                "action" => "view",
                "description" => "View locations",
            ],
            [
                "name" => "locations.create",
                "resource" => "locations",
                "action" => "create",
                "description" => "Create locations",
            ],
            [
                "name" => "locations.update",
                "resource" => "locations",
                "action" => "update",
                "description" => "Update locations",
            ],
            [
                "name" => "locations.delete",
                "resource" => "locations",
                "action" => "delete",
                "description" => "Delete locations",
            ],

            // Users
            [
                "name" => "users.view",
                "resource" => "users",
                "action" => "view",
                "description" => "View users",
            ],
            [
                "name" => "users.create",
                "resource" => "users",
                "action" => "create",
                "description" => "Create users",
            ],
            [
                "name" => "users.update",
                "resource" => "users",
                "action" => "update",
                "description" => "Update users",
            ],
            [
                "name" => "users.delete",
                "resource" => "users",
                "action" => "delete",
                "description" => "Delete users",
            ],
            [
                "name" => "users.assign_roles",
                "resource" => "users",
                "action" => "assign_roles",
                "description" => "Assign roles to users",
            ],
            [
                "name" => "users.assign_areas",
                "resource" => "users",
                "action" => "assign_areas",
                "description" => "Assign area access to users",
            ],

            // Devices
            [
                "name" => "devices.view",
                "resource" => "devices",
                "action" => "view",
                "description" => "View devices",
            ],
            [
                "name" => "devices.create",
                "resource" => "devices",
                "action" => "create",
                "description" => "Create devices",
            ],
            [
                "name" => "devices.update",
                "resource" => "devices",
                "action" => "update",
                "description" => "Update devices",
            ],
            [
                "name" => "devices.delete",
                "resource" => "devices",
                "action" => "delete",
                "description" => "Delete devices",
            ],
            [
                "name" => "devices.configure",
                "resource" => "devices",
                "action" => "configure",
                "description" => "Configure device thresholds",
            ],
            [
                "name" => "devices.assign_to_company",
                "resource" => "devices",
                "action" => "assign_to_company",
                "description" => "Assign devices to companies",
            ],
            [
                "name" => "devices.assign_to_area",
                "resource" => "devices",
                "action" => "assign_to_area",
                "description" => "Assign devices to areas",
            ],
            [
                "name" => "devices.bulk_assign_to_company",
                "resource" => "devices",
                "action" => "bulk_assign_to_company",
                "description" => "Bulk assign devices to companies",
            ],
            [
                "name" => "devices.bulk_assign_to_area",
                "resource" => "devices",
                "action" => "bulk_assign_to_area",
                "description" => "Bulk assign devices to areas",
            ],

            // Readings
            [
                "name" => "readings.view",
                "resource" => "readings",
                "action" => "view",
                "description" => "View readings",
            ],
            [
                "name" => "readings.export",
                "resource" => "readings",
                "action" => "export",
                "description" => "Export readings",
            ],

            // Alerts
            [
                "name" => "alerts.view",
                "resource" => "alerts",
                "action" => "view",
                "description" => "View alerts",
            ],
            [
                "name" => "alerts.acknowledge",
                "resource" => "alerts",
                "action" => "acknowledge",
                "description" => "Acknowledge alerts",
            ],
            [
                "name" => "alerts.resolve",
                "resource" => "alerts",
                "action" => "resolve",
                "description" => "Resolve alerts",
            ],

            // Tickets
            [
                "name" => "tickets.view",
                "resource" => "tickets",
                "action" => "view",
                "description" => "View tickets",
            ],
            [
                "name" => "tickets.create",
                "resource" => "tickets",
                "action" => "create",
                "description" => "Create tickets",
            ],
            [
                "name" => "tickets.update",
                "resource" => "tickets",
                "action" => "update",
                "description" => "Update tickets",
            ],
            [
                "name" => "tickets.assign",
                "resource" => "tickets",
                "action" => "assign",
                "description" => "Assign tickets",
            ],
            [
                "name" => "tickets.close",
                "resource" => "tickets",
                "action" => "close",
                "description" => "Close tickets",
            ],

            // Reports
            [
                "name" => "reports.view",
                "resource" => "reports",
                "action" => "view",
                "description" => "View reports",
            ],
            [
                "name" => "reports.export",
                "resource" => "reports",
                "action" => "export",
                "description" => "Export reports",
            ],

            // Audit
            [
                "name" => "audit.view",
                "resource" => "audit",
                "action" => "view",
                "description" => "View audit logs",
            ],

            // Roles
            [
                "name" => "roles.view",
                "resource" => "roles",
                "action" => "view",
                "description" => "View roles",
            ],
            [
                "name" => "roles.create",
                "resource" => "roles",
                "action" => "create",
                "description" => "Create custom roles",
            ],
            [
                "name" => "roles.update",
                "resource" => "roles",
                "action" => "update",
                "description" => "Update custom roles",
            ],
            [
                "name" => "roles.delete",
                "resource" => "roles",
                "action" => "delete",
                "description" => "Delete custom roles",
            ],
        ];

        DB::table("permissions")->insert($permissions);
    }
}
