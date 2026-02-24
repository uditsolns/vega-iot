<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleService
{

    /**
     * Get paginated list of roles.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Role::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::exact("is_system_role"),
                AllowedFilter::exact("company_id"),
            ])
            ->allowedSorts(["name", "hierarchy_level", "created_at"])
            ->allowedIncludes(["permissions", "company"])
            ->defaultSort("hierarchy_level");

        return $query->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new role.
     */
    public function create(array $data, User $user): Role
    {
        // Add company_id if not system user
        if (!$user->ofSystem()) {
            $data["company_id"] = $user->company_id;
        }

        // company_id should be provided by controller
        $role = Role::create($data);

        // Attach permissions if provided
        if (isset($data["permission_ids"])) {
            $role->permissions()->sync($data["permission_ids"]);
        }

        return $role->fresh(["permissions"]);
    }

    /**
     * Update a role.
     * @throws Exception
     */
    public function update(Role $role, array $data): Role
    {
        // Prevent editing system roles
        if ($role->is_system_role) {
            throw new Exception("System roles cannot be edited.");
        }

        $role->update($data);

        // Update permissions if provided
        if (isset($data["permission_ids"])) {
            $role->permissions()->sync($data["permission_ids"]);
        }

        return $role->fresh(["permissions"]);
    }

    /**
     * Delete a role.
     */
    public function delete(Role $role): void
    {
        // Prevent deleting system roles
        if ($role->is_system_role) {
            throw new Exception("System roles cannot be deleted.");
        }

        // Check if role is in use
        if ($role->users()->count() > 0) {
            throw new Exception(
                "Cannot delete role that is assigned to users.",
            );
        }

        $role->delete();
    }

    /**
     * Clone a role with new name.
     */
    public function clone(Role $role, array $data): Role
    {
        // Create new role with same attributes
        $newRole = Role::create([
            "company_id" => $data["company_id"] ?? $role->company_id,
            "name" => $data["name"],
            "description" => $data["description"] ?? $role->description,
            "hierarchy_level" =>
                $data["hierarchy_level"] ?? $role->hierarchy_level,
            "is_system_role" => false,
            "is_editable" => true,
        ]);

        // Copy permissions from original role
        $permissionIds = $role
            ->permissions()
            ->pluck("permissions.id")
            ->toArray();
        $newRole->permissions()->sync($permissionIds);

        activity('role')
            ->event("cloned")
            ->withProperties([
                "source_role_id" => $role->id,
                "new_role_id" => $newRole->id,
            ])
            ->log("Cloned role \"{$role->name}\" to \"{$newRole->name}\"");

        return $newRole->fresh(["permissions"]);
    }
}
