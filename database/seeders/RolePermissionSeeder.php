<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = DB::table("roles")->where("name", "Super Admin")->first();
        $companyAdmin = DB::table("roles")->where("name", "Company Admin")->first();
        $companyUser = DB::table("roles")->where("name", "Manager")->first();

        $allPermissions = DB::table("permissions")->pluck("id", "name");
        $rolePermissions = [];

        // Super Admin - ALL permissions
        foreach ($allPermissions as $permissionId) {
            $rolePermissions[] = [
                "role_id" => $superAdmin->id,
                "permission_id" => $permissionId,
            ];
        }

        // Company Admin - All except system-level operations
        $companyAdminExclude = [
            "companies.create",
            "companies.delete",
            "devices.create", // Only super admin can create devices
            "devices.delete", // Only super admin can delete devices
            "devices.assign_to_company",
            "devices.bulk_assign_to_company",
            "tickets.assign",
            "tickets.resolve",
            "tickets.close",
            "tickets.add_internal_comments",
            "tickets.view_internal_comments"
        ];

        foreach ($allPermissions as $name => $permissionId) {
            if (!in_array($name, $companyAdminExclude)) {
                $rolePermissions[] = [
                    "role_id" => $companyAdmin->id,
                    "permission_id" => $permissionId,
                ];
            }
        }

        // Company User - Operational permissions only
        $companyUserPermissions = [
            "locations.view",
            "users.view",
            "devices.view",
            "devices.configure",
            "devices.assign_to_area",
            "devices.bulk_assign_to_area",
            "readings.view",
            "readings.export",
            "alerts.view",
            "alerts.acknowledge",
            "alerts.resolve",
            "tickets.view",
            "tickets.create",
            "tickets.update",
            "reports.view",
            "reports.generate",
            "scheduled_reports.view",
            "audit_reports.view",
            "assets.view",
            "validation_studies.view",
            "roles.view",
        ];

        foreach ($companyUserPermissions as $permission) {
            if (isset($allPermissions[$permission])) {
                $rolePermissions[] = [
                    "role_id" => $companyUser->id,
                    "permission_id" => $allPermissions[$permission],
                ];
            }
        }

        DB::table("role_permissions")->insert($rolePermissions);
    }
}
