<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    /**
     * Determine if the user can view any devices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("devices.view");
    }

    /**
     * Determine if the user can view a specific device.
     */
    public function view(User $user, Device $device): bool
    {
        if (!$user->hasPermission("devices.view")) {
            return false;
        }

        return $this->userCanAccessDevice($user, $device);
    }

    /**
     * Determine if the user can create devices.
     * Only super admins can create devices in system inventory.
     */
    public function create(User $user): bool
    {
        if (!$user->hasPermission("devices.create")) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can update a device.
     */
    public function update(User $user, Device $device): bool
    {
        if (!$user->hasPermission("devices.update")) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Device must be in user's company
        if ($device->company_id !== $user->company_id) {
            return false;
        }

        return $this->userCanAccessDevice($user, $device);
    }

    /**
     * Determine if the user can delete a device.
     * Only super admins can delete devices.
     */
    public function delete(User $user, Device $device): bool
    {
        if (!$user->hasPermission("devices.delete")) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can assign a device to a company.
     */
    public function assignToCompany(User $user, Device $device): bool
    {
        if (!$user->hasPermission("devices.assign_to_company")) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company users cannot assign devices to companies
        return false;
    }

    /**
     * Determine if the user can assign a device to an area.
     */
    public function assignToArea(
        User $user,
        Device $device,
        ?int $areaId = null,
    ): bool {
        if (!$user->hasPermission("devices.assign_to_area")) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Device must be in user's company
        if (!$device->company_id || $device->company_id !== $user->company_id) {
            return false;
        }

        // If area_id is provided and user has area restrictions, validate access
        if ($areaId && $user->hasAreaRestrictions) {
            return in_array($areaId, $user->allowedAreas);
        }

        return true;
    }

    /**
     * Determine if the user can bulk assign devices to a company.
     */
    public function bulkAssignToCompany(User $user): bool
    {
        if (!$user->hasPermission("devices.bulk_assign_to_company")) {
            return false;
        }

        // Only super admins can bulk assign to companies
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can bulk assign devices to an area.
     */
    public function bulkAssignToArea(User $user): bool
    {
        if (!$user->hasPermission("devices.bulk_assign_to_area")) {
            return false;
        }

        // Super admins and company users with permission can bulk assign to areas
        return true;
    }

    /**
     * Determine if the user can configure a device.
     */
    public function configure(User $user, Device $device): bool
    {
        if (!$user->hasPermission("devices.configure")) {
            return false;
        }

        return $this->userCanAccessDevice($user, $device);
    }

    /**
     * Determine if the user can regenerate device API key.
     * Only super admins can regenerate API keys.
     */
    public function regenerateApiKey(User $user, Device $device): bool
    {
        if (!$user->hasPermission("devices.update")) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Helper method to check if user can access a device.
     */
    private function userCanAccessDevice(User $user, Device $device): bool
    {
        // Super admins can access all devices
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
}
