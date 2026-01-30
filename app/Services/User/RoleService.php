<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleService
{
    public function __construct(private AuditService $auditService)
    {
    }

    /**
     * Get paginated list of roles.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Role::class)
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::exact("is_system_role"),
                AllowedFilter::exact("company_id"),
            ])
            ->allowedSorts(["name", "hierarchy_level", "created_at"])
            ->allowedIncludes(["permissions", "company"])
            ->defaultSort("hierarchy_level");

        // Filter: system roles OR user's company roles
        if (!$user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where("is_system_role", true)->orWhere(
                    "company_id",
                    $user->company_id,
                );
            });
        }

        return $query->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new role.
     *
     * @param array $data
     * @return Role
     */
    public function create(array $data): Role
    {
        // company_id should be provided by controller
        $role = Role::create($data);

        // Attach permissions if provided
        if (isset($data["permission_ids"])) {
            $role->permissions()->sync($data["permission_ids"]);
        }

        $this->auditService->log("role.created", Role::class, $role);

        return $role->fresh(["permissions"]);
    }

    /**
     * Update a role.
     *
     * @param Role $role
     * @param array $data
     * @return Role
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

        $this->auditService->log("role.updated", Role::class, $role);

        return $role->fresh(["permissions"]);
    }

    /**
     * Delete a role.
     *
     * @param Role $role
     * @return void
     * @throws Exception
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

        $this->auditService->log("role.deleted", Role::class, $role);

        $role->delete();
    }

    /**
     * Clone a role with new name.
     *
     * @param Role $role
     * @param array $data
     * @return Role
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

        $this->auditService->log("role.created", Role::class, $newRole, ["permissions" => $permissionIds]);

        return $newRole->fresh(["permissions"]);
    }
}
