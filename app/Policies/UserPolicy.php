<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("users.view");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermission("users.view")) {
            return false;
        }

        // Super admin can view all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only view users in their own company
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (!$user->hasPermission("users.create")) {
            return false;
        }

        // Super admins can create users in any company
        // Company users can only create users in their own company
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if (!$user->hasPermission("users.update")) {
            return false;
        }

        // Super admin can update any user
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must be in same company
        if ($user->company_id !== $model->company_id) {
            return false;
        }

        // Cannot update users with higher or equal hierarchy level
        return $user->role->hierarchy_level < $model->role->hierarchy_level;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if (!$user->hasPermission("users.delete")) {
            return false;
        }

        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Super admin can delete any user
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must be in same company
        if ($user->company_id !== $model->company_id) {
            return false;
        }

        // Cannot delete users with higher or equal hierarchy level
        return $user->role->hierarchy_level < $model->role->hierarchy_level;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Same rules as delete
        return $this->delete($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Same rules as delete
        return $this->delete($user, $model);
    }

    /**
     * Determine whether the user can change the role of the model.
     */
    public function changeRole(User $user, User $model): bool
    {
        if (!$user->hasPermission("users.assign_roles")) {
            return false;
        }

        // Cannot change your own role
        if ($user->id === $model->id) {
            return false;
        }

        // Super admin can change any role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must be in same company
        if ($user->company_id !== $model->company_id) {
            return false;
        }

        // Cannot change role of users with higher or equal hierarchy level
        return $user->role->hierarchy_level < $model->role->hierarchy_level;
    }

    /**
     * Determine whether the user can assign areas to the model.
     */
    public function assignAreas(User $user, User $model): bool
    {
        if (!$user->hasPermission("users.assign_areas")) {
            return false;
        }

        // Super admin can assign areas to any user
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only assign areas to users in their own company
        return $user->company_id === $model->company_id;
    }
}
