<?php

namespace App\Policies;

use App\Models\DeviceModel;
use App\Models\User;

class DeviceModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('devices.view');
    }

    public function view(User $user, DeviceModel $deviceModel): bool
    {
        return $user->hasPermission('devices.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('devices.create');
    }

    public function update(User $user, DeviceModel $deviceModel): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('devices.update');
    }

    public function delete(User $user, DeviceModel $deviceModel): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('devices.delete');
    }
}
