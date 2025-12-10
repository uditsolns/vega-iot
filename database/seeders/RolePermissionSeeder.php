<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles
        $superAdmin = DB::table("roles")->where("name", "Super Admin")->first();
        $companyAdmin = DB::table("roles")
            ->where("name", "Company Admin")
            ->first();
        $manager = DB::table("roles")->where("name", "Manager")->first();
        $technician = DB::table("roles")->where("name", "Technician")->first();
        $operator = DB::table("roles")->where("name", "Operator")->first();
        $viewer = DB::table("roles")->where("name", "Viewer")->first();

        // Get all permissions
        $allPermissions = DB::table("permissions")->pluck("id", "name");

        $rolePermissions = [];

        // Super Admin - ALL permissions
        foreach ($allPermissions as $permissionId) {
            $rolePermissions[] = [
                "role_id" => $superAdmin->id,
                "permission_id" => $permissionId,
            ];
        }

        // Company Admin - All except companies.create, companies.delete
        foreach ($allPermissions as $name => $permissionId) {
            if (!in_array($name, [ "companies.create", "companies.delete"])) {
                $rolePermissions[] = [
                    "role_id" => $companyAdmin->id,
                    "permission_id" => $permissionId,
                ];
            }
        }

        // Manager - users (view, create, update), locations, devices, readings, alerts, tickets, reports
        $managerPermissions = [
            "users.view",
            "users.create",
            "users.update",
            "users.assign_roles",
            "users.assign_areas",
            "locations.view",
            "locations.create",
            "locations.update",
            "locations.delete",
            "devices.view",
            "devices.create",
            "devices.update",
            "devices.delete",
            "devices.configure",
            "devices.assign",
            "readings.view",
            "readings.export",
            "alerts.view",
            "alerts.acknowledge",
            "alerts.resolve",
            "tickets.view",
            "tickets.create",
            "tickets.update",
            "tickets.assign",
            "tickets.close",
            "reports.view",
            "reports.export",
            "roles.view",
        ];
        foreach ($managerPermissions as $permission) {
            if (isset($allPermissions[$permission])) {
                $rolePermissions[] = [
                    "role_id" => $manager->id,
                    "permission_id" => $allPermissions[$permission],
                ];
            }
        }

        // Technician - devices (all), readings, alerts (view, acknowledge)
        $technicianPermissions = [
            "devices.view",
            "devices.create",
            "devices.update",
            "devices.delete",
            "devices.configure",
            "devices.assign",
            "readings.view",
            "alerts.view",
            "alerts.acknowledge",
            "locations.view",
        ];
        foreach ($technicianPermissions as $permission) {
            if (isset($allPermissions[$permission])) {
                $rolePermissions[] = [
                    "role_id" => $technician->id,
                    "permission_id" => $allPermissions[$permission],
                ];
            }
        }

        // Operator - readings, alerts (view, acknowledge, resolve), tickets (view, create)
        $operatorPermissions = [
            "readings.view",
            "alerts.view",
            "alerts.acknowledge",
            "alerts.resolve",
            "tickets.view",
            "tickets.create",
            "devices.view",
            "locations.view",
        ];
        foreach ($operatorPermissions as $permission) {
            if (isset($allPermissions[$permission])) {
                $rolePermissions[] = [
                    "role_id" => $operator->id,
                    "permission_id" => $allPermissions[$permission],
                ];
            }
        }

        // Viewer - *.view permissions only
        foreach ($allPermissions as $name => $permissionId) {
            if (str_ends_with($name, ".view")) {
                $rolePermissions[] = [
                    "role_id" => $viewer->id,
                    "permission_id" => $permissionId,
                ];
            }
        }

        DB::table("role_permissions")->insert($rolePermissions);
    }
}
