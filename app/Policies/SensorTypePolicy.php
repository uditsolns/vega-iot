<?php

namespace App\Policies;

use App\Models\SensorType;
use App\Models\User;

class SensorTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('devices.view');
    }

    public function view(User $user, SensorType $sensorType): bool
    {
        return $user->hasPermission('devices.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, SensorType $sensorType): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, SensorType $sensorType): bool
    {
        return $user->isSuperAdmin();
    }
}
