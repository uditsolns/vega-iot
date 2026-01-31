<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValidationStudy;

class ValidationStudyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('validation.view');
    }

    public function view(User $user, ValidationStudy $study): bool
    {
        return $user->isSuperAdmin()
            || $user->company_id === $study->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('validation.create');
    }

    public function update(User $user, ValidationStudy $study): bool
    {
        return $user->hasPermission('validation.update')
            && ($user->isSuperAdmin() || $user->company_id === $study->company_id);
    }

    public function delete(User $user, ValidationStudy $study): bool
    {
        return $user->hasPermission('validation.delete')
            && ($user->isSuperAdmin() || $user->company_id === $study->company_id);
    }

    public function restore(User $user, ValidationStudy $study): bool
    {
        return $this->delete($user, $study);
    }
}
