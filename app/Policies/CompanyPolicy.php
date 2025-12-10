<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("companies.view");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        if (!$user->hasPermission("companies.view")) {
            return false;
        }

        // Super admin can view all companies
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only view their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super admins can create companies
        return $user->hasPermission("companies.create") &&
            $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        if (!$user->hasPermission("companies.update")) {
            return false;
        }

        // Super admin can update all companies
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only super admins can delete companies
        return $user->hasPermission("companies.delete") &&
            $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        // Only super admins can restore companies
        return $user->hasPermission("companies.delete") &&
            $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        // Only super admins can force delete companies
        return $user->hasPermission("companies.delete") &&
            $user->isSuperAdmin();
    }
}
