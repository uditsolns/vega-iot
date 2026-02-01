<?php

namespace App\Policies;

use App\Models\Hub;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class HubPolicy
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
    public function view(User $user, Hub $hub): bool
    {
        if (!$user->hasPermission('locations.view')) {
            return false;
        }

        // Super admin can view all hubs
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only view hubs in their company
        return $user->company_id === $hub->location->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
//        // Only company users can create hubs (not super admins)
//        return $user->hasPermission('locations.create') &&
//            !$user->isSuperAdmin();
        return $user->hasPermission('locations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Hub $hub): bool
    {
        if (!$user->hasPermission('locations.update')) {
            return false;
        }

        // Super admin can update all hubs
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update hubs in their company
        return $user->company_id === $hub->location->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Hub $hub): bool
    {
        if (!$user->hasPermission('locations.delete')) {
            return false;
        }

        // Super admin can delete all hubs
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only delete hubs in their company
        return $user->company_id === $hub->location->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Hub $hub): bool
    {
        if (!$user->hasPermission('locations.delete')) {
            return false;
        }

        // Super admin can restore all hubs
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only restore hubs in their company
        return $user->company_id === $hub->location->company_id;
    }
}
