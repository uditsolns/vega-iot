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
                "name" => "tickets.delete",
                "resource" => "tickets",
                "action" => "delete",
                "description" => "Delete tickets",
            ],
            [
                "name" => "tickets.assign",
                "resource" => "tickets",
                "action" => "assign",
                "description" => "Assign tickets to users",
            ],
            [
                "name" => "tickets.resolve",
                "resource" => "tickets",
                "action" => "resolve",
                "description" => "Resolve tickets",
            ],
            [
                "name" => "tickets.close",
                "resource" => "tickets",
                "action" => "close",
                "description" => "Close tickets",
            ],
            [
                "name" => "tickets.reopen",
                "resource" => "tickets",
                "action" => "reopen",
                "description" => "Reopen closed tickets",
            ],
            [
                "name" => "tickets.add_internal_comments",
                "resource" => "tickets",
                "action" => "add_internal_comments",
                "description" => "Add internal comments (not visible to customers)",
            ],
            [
                "name" => "tickets.view_internal_comments",
                "resource" => "tickets",
                "action" => "view_internal_comments",
                "description" => "View internal comments",
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

            // Scheduled Reports
            [
                "name" => "scheduled_reports.view",
                "resource" => "scheduled_reports",
                "action" => "view",
                "description" => "View scheduled reports",
            ],
            [
                "name" => "scheduled_reports.create",
                "resource" => "scheduled_reports",
                "action" => "create",
                "description" => "Create scheduled reports",
            ],
            [
                "name" => "scheduled_reports.update",
                "resource" => "scheduled_reports",
                "action" => "update",
                "description" => "Update scheduled reports",
            ],
            [
                "name" => "scheduled_reports.delete",
                "resource" => "scheduled_reports",
                "action" => "delete",
                "description" => "Delete scheduled reports",
            ],

            // Audit
            [
                "name" => "audit.view",
                "resource" => "audit",
                "action" => "view",
                "description" => "View audit logs",
            ],
            [
                "name" => "audit.generate-report",
                "resource" => "audit",
                "action" => "generate-report",
                "description" => "Generate audit reports",
            ],

            // Assets (Calibration Instruments)
            [
                "name" => "assets.view",
                "resource" => "assets",
                "action" => "view",
                "description" => "View calibration instruments",
            ],
            [
                "name" => "assets.create",
                "resource" => "assets",
                "action" => "create",
                "description" => "Create calibration instruments",
            ],
            [
                "name" => "assets.update",
                "resource" => "assets",
                "action" => "update",
                "description" => "Update calibration instruments",
            ],
            [
                "name" => "assets.delete",
                "resource" => "assets",
                "action" => "delete",
                "description" => "Delete calibration instruments",
            ],

            // Validation Studies
            [
                "name" => "validation_studies.view",
                "resource" => "validation_studies",
                "action" => "view",
                "description" => "View validation studies",
            ],
            [
                "name" => "validation_studies.create",
                "resource" => "validation_studies",
                "action" => "create",
                "description" => "Create validation studies",
            ],
            [
                "name" => "validation_studies.update",
                "resource" => "validation_studies",
                "action" => "update",
                "description" => "Update validation studies",
            ],
            [
                "name" => "validation_studies.delete",
                "resource" => "validation_studies",
                "action" => "delete",
                "description" => "Delete validation studies",
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
