<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    /**
     * Determine if the user can view any alerts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('alerts.view');
    }

    /**
     * Determine if the user can view a specific alert.
     */
    public function view(User $user, Alert $alert): bool
    {
        if (!$user->hasPermission('alerts.view')) {
            return false;
        }

        return $this->userCanAccessAlert($user, $alert);
    }

    /**
     * Determine if the user can acknowledge an alert.
     */
    public function acknowledge(User $user, Alert $alert): bool
    {
        if (!$user->hasPermission('alerts.acknowledge')) {
            return false;
        }

        return $this->userCanAccessAlert($user, $alert);
    }

    /**
     * Determine if the user can resolve an alert.
     */
    public function resolve(User $user, Alert $alert): bool
    {
        if (!$user->hasPermission('alerts.resolve')) {
            return false;
        }

        return $this->userCanAccessAlert($user, $alert);
    }

    /**
     * Helper method to check if user can access an alert.
     * Alert access is based on device access.
     */
    private function userCanAccessAlert(User $user, Alert $alert): bool
    {
        // Super admins can access all alerts
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Load device relationship if not already loaded
        if (!$alert->relationLoaded('device')) {
            $alert->load('device');
        }

        $device = $alert->device;

        // If no device, deny access
        if (!$device) {
            return false;
        }

        // Company mismatch
        if ($device->company_id !== $user->company_id) {
            return false;
        }

        // If device is deployed to an area, check area restrictions
        if ($device->area_id && $user->hasAreaRestrictions) {
            return in_array($device->area_id, $user->allowedAreas);
        }

        return true;
    }
}
