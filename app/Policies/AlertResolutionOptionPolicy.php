<?php

namespace App\Policies;

use App\Models\AlertResolutionOption;
use App\Models\User;

class AlertResolutionOptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('alert_resolution_options.view');
    }

    public function view(User $user, AlertResolutionOption $option): bool
    {
        if (!$user->hasPermission('alert_resolution_options.view')) {
            return false;
        }

        if ($user->ofSystem()) {
            return true;
        }

        return $option->is_system || $option->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('alert_resolution_options.create');
    }

    public function update(User $user, AlertResolutionOption $option): bool
    {
        if (!$user->hasPermission('alert_resolution_options.update')) {
            return false;
        }

        if ($option->is_system && !$user->ofSystem()) {
            return false;
        }

        if ($user->ofSystem()) {
            return true;
        }

        return $option->company_id === $user->company_id;
    }

    public function delete(User $user, AlertResolutionOption $option): bool
    {
        if (!$user->hasPermission('alert_resolution_options.delete')) {
            return false;
        }

        if ($option->is_system && !$user->ofSystem()) {
            return false;
        }

        if ($user->ofSystem()) {
            return true;
        }

        return $option->company_id === $user->company_id;
    }
}
