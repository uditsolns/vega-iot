<?php

namespace App\Policies;

use App\Models\AlertResolutionOption;
use App\Models\User;

class AlertResolutionOptionPolicy
{
    /**
     * Any authenticated user can view options (needed to populate dropdowns).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only system users can mutate options.
     */
    public function create(User $user): bool
    {
        return $user->ofSystem();
    }

    public function update(User $user, AlertResolutionOption $option): bool
    {
        return $user->ofSystem();
    }

    public function delete(User $user, AlertResolutionOption $option): bool
    {
        return $user->ofSystem();
    }
}
