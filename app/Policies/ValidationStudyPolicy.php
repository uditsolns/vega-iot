<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValidationStudy;

class ValidationStudyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('validation_studies.view');
    }

    public function view(User $user, ValidationStudy $study): bool
    {
        if (!$user->hasPermission('validation_studies.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->company_id === $study->company_id;
    }

    public function create(User $user): bool
    {
//        // Only company users can create locations (not super admins)
//        return $user->hasPermission('validations.create') &&
//            !$user->isSuperAdmin();

        return $user->hasPermission('validation_studies.create');
    }

    public function update(User $user, ValidationStudy $study): bool
    {
        if (!$user->hasPermission('validation_studies.update')) {
            return false;
        }

        // Super admin can update all validations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update validations in their company
        return $user->company_id === $study->company_id;
    }

    public function delete(User $user, ValidationStudy $study): bool
    {
        if (!$user->hasPermission('validations_studies.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->company_id === $study->company_id;
    }
}
