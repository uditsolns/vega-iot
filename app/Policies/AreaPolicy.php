<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('locations.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Area $area): bool
    {
        if (!$user->hasPermission('locations.view')) {
            return false;
        }

        // Super admin can view all areas
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only view areas in their company
        return $user->company_id === $area->hub->location->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
//        // Only company users can create areas (not super admins)
//        return $user->hasPermission('locations.create') &&
//            !$user->isSuperAdmin();
        return $user->hasPermission('locations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Area $area): bool
    {
        if (!$user->hasPermission('locations.update')) {
            return false;
        }

        // Super admin can update all areas
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update areas in their company
        return $user->company_id === $area->hub->location->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Area $area): bool
    {
        if (!$user->hasPermission('locations.delete')) {
            return false;
        }

        // Super admin can delete all areas
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only delete areas in their company
        return $user->company_id === $area->hub->location->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Area $area): bool
    {
        if (!$user->hasPermission('locations.delete')) {
            return false;
        }

        // Super admin can restore all areas
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only restore areas in their company
        return $user->company_id === $area->hub->location->company_id;
    }

    /**
     * Determine whether the user can update alert configuration.
     */
    public function updateAlertConfig(User $user, Area $area): bool
    {
        if (!$user->hasPermission('locations.update')) {
            return false;
        }

        // Super admin can update alert config for all areas
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update alert config for areas in their company
        return $user->company_id === $area->hub->location->company_id;
    }
}
