<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("roles.view");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        if (!$user->hasPermission("roles.view")) {
            return false;
        }

        // Super admin can view all roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Can view system roles
        if ($role->is_system_role) {
            return true;
        }

        // Can view roles in own company
        return $user->company_id === $role->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (!$user->hasPermission("roles.create")) {
            return false;
        }

        // Super admins can create system roles (though this would be rare)
        // Company admins can create custom roles in their company
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        if (!$user->hasPermission("roles.update")) {
            return false;
        }

        // Cannot update system roles
        if ($role->is_system_role) {
            return false;
        }

        // Super admin can update any custom role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update roles in their own company
        return $user->company_id === $role->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        if (!$user->hasPermission("roles.delete")) {
            return false;
        }

        // Cannot delete system roles
        if ($role->is_system_role) {
            return false;
        }

        // Super admin can delete any custom role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only delete roles in their own company
        return $user->company_id === $role->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        // Same rules as delete
        return $this->delete($user, $role);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        // Same rules as delete
        return $this->delete($user, $role);
    }
}
