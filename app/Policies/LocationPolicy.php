<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
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
    public function view(User $user, Location $location): bool
    {
        if (!$user->hasPermission('locations.view')) {
            return false;
        }

        // Super admin can view all locations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only view locations in their company
        return $user->company_id === $location->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
//        // Only company users can create locations (not super admins)
//        return $user->hasPermission('locations.create') &&
//            !$user->isSuperAdmin();

        return $user->hasPermission('locations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Location $location): bool
    {
        if (!$user->hasPermission('locations.update')) {
            return false;
        }

        // Super admin can update all locations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update locations in their company
        return $user->company_id === $location->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Location $location): bool
    {
        if (!$user->hasPermission('locations.delete')) {
            return false;
        }

        // Super admin can delete all locations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only delete locations in their company
        return $user->company_id === $location->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Location $location): bool
    {
        if (!$user->hasPermission('locations.delete')) {
            return false;
        }

        // Super admin can restore all locations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only restore locations in their company
        return $user->company_id === $location->company_id;
    }
}
