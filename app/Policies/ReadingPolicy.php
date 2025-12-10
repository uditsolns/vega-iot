<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Device;
use App\Models\User;

class ReadingPolicy
{
    /**
     * Determine if the user can view any readings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('readings.view');
    }

    /**
     * Determine if the user can view readings for a specific device.
     */
    public function viewDevice(User $user, Device $device): bool
    {
        if (!$user->hasPermission('readings.view')) {
            return false;
        }

        // Super admins can view all device readings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // System inventory: only super admins
        if ($device->isSystemInventory()) {
            return false;
        }

        // Company mismatch
        if ($device->company_id !== $user->company_id) {
            return false;
        }

        // If device is deployed, check area restrictions
        if ($device->area_id && $user->hasAreaRestrictions) {
            return in_array($device->area_id, $user->allowedAreas);
        }

        return true;
    }

    /**
     * Determine if the user can view readings for an area.
     */
    public function viewArea(User $user, Area $area): bool
    {
        if (!$user->hasPermission('readings.view')) {
            return false;
        }

        // Super admins can view all area readings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if area belongs to user's company
        $area->load('hub.location');

        if ($area->hub->location->company_id !== $user->company_id) {
            return false;
        }

        // Check area restrictions
        if ($user->hasAreaRestrictions) {
            return in_array($area->id, $user->allowedAreas);
        }

        return true;
    }

    /**
     * Determine if the user can export readings.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('readings.export');
    }
}
