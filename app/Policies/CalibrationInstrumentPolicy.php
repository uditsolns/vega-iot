<?php

namespace App\Policies;

use App\Models\CalibrationInstrument;
use App\Models\User;

class CalibrationInstrumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assets.view');
    }

    public function view(User $user, CalibrationInstrument $instrument): bool
    {
        return $user->isSuperAdmin()
            || $user->company_id === $instrument->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('assets.create');
    }

    public function update(User $user, CalibrationInstrument $instrument): bool
    {
        return $user->hasPermission('assets.update')
            && ($user->isSuperAdmin() || $user->company_id === $instrument->company_id);
    }

    public function delete(User $user, CalibrationInstrument $instrument): bool
    {
        return $user->hasPermission('assets.delete')
            && ($user->isSuperAdmin() || $user->company_id === $instrument->company_id);
    }
}
