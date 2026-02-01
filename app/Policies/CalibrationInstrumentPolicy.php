<?php

namespace App\Policies;

use App\Models\CalibrationInstrument;
use App\Models\User;
use function Laravel\Prompts\select;

class CalibrationInstrumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assets.view');
    }

    public function view(User $user, CalibrationInstrument $instrument): bool
    {
        if (!$user->hasPermission('assets.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->company_id === $instrument->company_id;
    }

    public function create(User $user): bool
    {
//        // Only company users can create locations (not super admins)
//        return $user->hasPermission('assets.create') &&
//            !$user->isSuperAdmin();

        return $user->hasPermission('assets.create');
    }

    public function update(User $user, CalibrationInstrument $instrument): bool
    {
        if (!$user->hasPermission('assets.update')) {
            return false;
        }

        // Super admin can update all assets
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can only update assets in their company
        return $user->company_id === $instrument->company_id;
    }

    public function delete(User $user, CalibrationInstrument $instrument): bool
    {
        if (!$user->hasPermission('assets.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->company_id === $instrument->company_id;
    }
}
