<?php

namespace App\Services\Device;

use App\Models\Device;
use App\Models\DeviceConfiguration;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class DeviceConfigurationService
{
    public function __construct(private AuditService $auditService)
    {
    }

    /**
     * Get current configuration for a device
     */
    public function getCurrent(Device $device): ?DeviceConfiguration
    {
        return $device->currentConfiguration;
    }

    /**
     * Get configuration history for a device
     */
    public function getHistory(Device $device): Collection
    {
        return $device->configurations()->latest()->get();
    }

    /**
     * Create default configuration for a new device
     */
    public function createDefault(Device $device): DeviceConfiguration
    {
        return DeviceConfiguration::create([
            "device_id" => $device->id,
            "is_current" => true,
            // All other fields will use migration defaults
        ]);
    }

    /**
     * Update device configuration with transaction
     * Creates new config and marks old as not current
     * @throws Throwable
     */
    public function update(
        Device $device,
        array $data,
        User $user,
    ): DeviceConfiguration {
        return DB::transaction(function () use ($device, $data, $user) {
            // Step 1: Set current config to is_current = false
            DeviceConfiguration::where("device_id", $device->id)
                ->where("is_current", true)
                ->update(["is_current" => false]);

            // Step 2: Create new configuration with is_current = true
            $newConfig = DeviceConfiguration::create([
                ...$data,
                "device_id" => $device->id,
                "is_current" => true,
                "updated_by" => $user->id,
            ]);

            $this->auditService->log(
                "device_configuration.updated",
                DeviceConfiguration::class,
                $newConfig,
                ['device_code' => $device->device_code]
            );

            return $newConfig;
        });
    }

    /**
     * Bulk update configurations for multiple devices
     * @throws Throwable
     */
    public function bulkUpdate(
        array $deviceIds,
        array $configData,
        User $user,
    ): void {
        // Step 1: Fetch all devices at once
        $devices = Device::whereIn("id", $deviceIds)->get();

        // Step 2: Authorize all devices
        foreach ($devices as $device) {
            if (!$user->can("configure", $device)) {
                throw new AuthorizationException(
                    "Unauthorized to configure device $device->device_code.",
                );
            }
        }

        // perform bulk action within transaction
        DB::transaction(function () use ($deviceIds, $configData, $user) {
            // set all current configs to is_current = false
            DeviceConfiguration::whereIn("device_id", $deviceIds)
                ->current()
                ->update(["is_current" => false]);

            // Create new configurations for all devices
            $newConfigs = [];
            foreach ($deviceIds as $deviceId) {
                $newConfigs[] = [
                    ...$configData,
                    "device_id" => $deviceId,
                    "is_current" => true,
                    "updated_by" => $user->id,
                    "created_at" => now(),
                    "updated_at" => now(),
                ];
            }

            DeviceConfiguration::insert($newConfigs);
        });
    }
}
