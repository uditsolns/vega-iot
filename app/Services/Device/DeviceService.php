<?php

namespace App\Services\Device;

use App\Enums\DeviceStatus;
use App\Exceptions\DeviceAssignmentException;
use App\Models\Area;
use App\Models\Company;
use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class DeviceService
{
    public function __construct(private DeviceConfigurationService $configService) {}

    /**
     * List devices with filtering, sorting, and includes
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Device::forUser($user))
            ->with("latestReading")
            ->allowedFilters([
                AllowedFilter::exact("status"),
                AllowedFilter::exact("type"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("company_id"),
                AllowedFilter::exact("area_id"),
                AllowedFilter::partial("device_name"),
                AllowedFilter::partial("device_code"),
                AllowedFilter::partial("device_uid"),
                AllowedFilter::scope("systemInventory"),
                AllowedFilter::scope("companyInventory"),
                AllowedFilter::scope("deployed"),
            ])
            ->allowedSorts([
                "device_name",
                "device_code",
                "created_at",
                "status",
                "last_reading_at",
            ])
            ->allowedIncludes([
                "area.hub.location",
                "company",
                "currentConfiguration",
            ])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new device in system inventory
     */
    public function create(array $data): Device
    {
        // Create device (api_key generated in boot method)
        $device = Device::create($data);

        // Generate default configuration
        $this->configService->createDefault($device);

        return $device->fresh(["currentConfiguration"]);
    }

    /**
     * Update an existing device
     */
    public function update(Device $device, array $data): Device
    {
        $device->update($data);

        return $device->fresh(["currentConfiguration", "company", "area"]);
    }

    /**
     * Delete a device (soft delete via is_active flag)
     */
    public function delete(Device $device): void
    {
        $device->update(["is_active" => false]);
    }

    /**
     * Change device status
     */
    public function changeStatus(Device $device, string $status): Device
    {
        // Validate enum value
        $statusEnum = DeviceStatus::tryFrom($status);

        if (!$statusEnum) {
            throw new InvalidArgumentException("Invalid status: $status");
        }

        $oldStatus = $device->status;

        $device->update(["status" => $statusEnum]);

        activity("device")
            ->event("changed_status")
            ->performedOn($device)
            ->withProperties(["device_id" => $device->id])
            ->log("Changed status from \"$oldStatus->value\" to \"$statusEnum->value\" for device \"$device->device_code\"");

        return $device->fresh();
    }

    /**
     * Get device statistics for dashboard
     */
    public function getDeviceStats(int $companyId): array
    {
        $query = Device::where('company_id', $companyId);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
            'online' => (clone $query)->where('status', 'online')->count(),
            'offline' => (clone $query)->where('status', 'offline')->count(),
            'maintenance' => (clone $query)->where('status', 'maintenance')->count(),
            'decommissioned' => (clone $query)->where('status', 'decommissioned')->count(),
            'system_inventory' => Device::systemInventory()->count(),
            'company_inventory' => (clone $query)->whereNull('area_id')->count(),
            'deployed' => (clone $query)->whereNotNull('area_id')->count(),
        ];
    }

    /**
     * Assign device to company
     */
    public function assignToCompany(Device $device, array $data): Device
    {
        $company = Company::find($data['company_id']);

        $updateData = [
            "company_id" => $data["company_id"],
            "area_id" => null, // Unassign from area when assigning to company
        ];

        if (isset($data["device_name"])) {
            $updateData["device_name"] = $data["device_name"];
        }

        $device->update($updateData);

        // Audit log
        activity("device")
            ->event('assigned_to_company')
            ->performedOn($device)
            ->withProperties([
                'device_id' => $device->id,
                'company_id' => $company->id,
            ])
            ->log("Assigned device \"{$device->device_code}\" to company \"{$company->name}\"");

        return $device->fresh(["company"]);
    }

    /**
     * @throws DeviceAssignmentException
     */
    public function assignToArea(Device $device, array $data): Device
    {
        $area = Area::with("hub.location")->findOrFail($data["area_id"]);

        // Validate device has a company assignment
        if (!$device->company_id) {
            throw DeviceAssignmentException::deviceRequiresCompany();
        }

        // Validate area belongs to same company as device
        if ($area->hub->location->company_id !== $device->company_id) {
            throw DeviceAssignmentException::areaMismatch(
                $device->device_code,
                $area->name,
            );
        }

        $updateData = [
            "area_id" => $data["area_id"],
        ];

        if (isset($data["device_name"])) {
            $updateData["device_name"] = $data["device_name"];
        }

        $device->update($updateData);

        // Audit log
        activity("device")
            ->event('assigned_to_area')
            ->performedOn($device)
            ->withProperties([
                'device_id' => $device->id,
                'area_id' => $area->id,
            ])
            ->log("Assigned device \"{$device->device_code}\" to area \"{$area->name}\"");

        return $device->fresh(["company", "area.hub.location"]);
    }

    /**
     * Unassign device (return to system inventory)
     */
    public function unassign(Device $device): Device
    {
        $device->update([
            "company_id" => null,
            "area_id" => null,
            "device_name" => null,
        ]);

        // Audit log
        activity('device')
            ->event('unassigned')
            ->performedOn($device)
            ->withProperties(['device_id' => $device->id])
            ->log("Unassigned device \"{$device->device_code}\"");

        return $device->fresh();
    }

    /**
     * Bulk assign devices to company
     */
    public function bulkAssignToCompany(
        array $deviceIds,
        int $companyId,
        User $user,
    ): void {
        // Fetch all devices at once
        $devices = Device::whereIn("id", $deviceIds)->get();

        // Authorize all devices
        foreach ($devices as $device) {
            if (!$user->can("assignToCompany", $device)) {
                throw new AuthorizationException(
                    "Unauthorized to assign device $device->device_code to a company.",
                );
            }
        }

        // Perform bulk action
        Device::whereIn("id", $deviceIds)->update([
            "company_id" => $companyId,
            "area_id" => null, // Unassign from area when assigning to company
        ]);

        $company = Company::find($companyId);

        // Audit log for bulk operation
        activity("device")
            ->event("bulk_assigned_to_company")
            ->withProperties([
                "company_id" => $company->id,
                "device_ids" => $deviceIds,
                "device_count" => count($deviceIds),
            ])
            ->log("Bulk assigned " . count($deviceIds) . " devices to company \"{$company->name}\"");
    }

    /**
     * Bulk assign devices to area
     * @throws DeviceAssignmentException
     */
    public function bulkAssignToArea(
        array $deviceIds,
        int $areaId,
        User $user,
    ): void {
        // Fetch all devices and area at once
        $devices = Device::whereIn("id", $deviceIds)->get();
        $area = Area::with("hub.location")->findOrFail($areaId);

        // Authorize all devices and validate business rules
        foreach ($devices as $device) {
            // Use policy method for authorization (includes area restriction check)
            if (!$user->can("assignToArea", [$device, $areaId])) {
                throw new AuthorizationException("Unauthorized to assign device $device->device_code to this area.");
            }

            // Validate device has a company assignment
            if (!$device->company_id) {
                throw DeviceAssignmentException::deviceRequiresCompany();
            }

            // Validate area belongs to same company as device
            if ($area->hub->location->company_id !== $device->company_id) {
                throw DeviceAssignmentException::areaMismatch($device->device_code, $area->name);
            }
        }

        // Perform bulk action
        Device::whereIn("id", $deviceIds)->update([
            "area_id" => $areaId,
        ]);

        // Audit log for bulk operation
        activity("device")
            ->event('bulk_assigned_to_area')
            ->withProperties([
                'area_id' => $area->id,
                'device_ids' => $deviceIds,
                'device_count' => count($deviceIds),
            ])
            ->log("Bulk assigned " . count($deviceIds) . " devices to area \"{$area->name}\"");
    }

    /**
     * Bulk unassign devices
     */
    public function bulkUnassign(array $deviceIds, User $user): void
    {
        // Fetch all devices at once
        $devices = Device::whereIn("id", $deviceIds)->get();

        // Authorize all devices
        foreach ($devices as $device) {
            if (!$user->can("assignToCompany", $device)) {
                throw new AuthorizationException(
                    "Unauthorized to unassign device $device->device_code.",
                );
            }
        }

        // Perform bulk action
        Device::whereIn("id", $deviceIds)->update([
            "company_id" => null,
            "area_id" => null,
            "device_name" => null,
        ]);

        // Audit log for bulk operation
        activity("device")
            ->event('bulk_unassigned')
            ->withProperties([
                'device_ids' => $deviceIds,
                'device_count' => count($deviceIds)
            ])
            ->log("Bulk unassigned " . count($deviceIds) . " devices");
    }

    /**
     * Bulk change device status
     */
    public function bulkChangeStatus(
        array $deviceIds,
        string $status,
        User $user,
    ): void {
        // Validate enum value
        $statusEnum = DeviceStatus::tryFrom($status);

        if (!$statusEnum) {
            throw new InvalidArgumentException("Invalid status: $status");
        }

        // Fetch all devices at once
        $devices = Device::whereIn("id", $deviceIds)->get();

        // Authorize all devices
        foreach ($devices as $device) {
            if (!$user->can("update", $device)) {
                throw new AuthorizationException("Unauthorized to update device $device->device_code.");
            }
        }

        // Perform bulk action
        Device::whereIn("id", $deviceIds)->update([
            "status" => $statusEnum,
        ]);

        // Audit log for bulk operation
        activity("device")
            ->event('bulk_status_changed')
            ->withProperties([
                'new_status' => $status,
                'device_ids' => $deviceIds,
                'device_count' => count($deviceIds),
            ])
            ->log("Bulk changed status to \"{$status}\" for " . count($deviceIds) . " devices");
    }

    /**
     * Bulk delete devices
     */
    public function bulkDelete(array $deviceIds, User $user): void
    {
        // Fetch all devices at once
        $devices = Device::whereIn("id", $deviceIds)->get();

        // Authorize all devices
        foreach ($devices as $device) {
            if (!$user->can("delete", $device)) {
                throw new AuthorizationException(
                    "Unauthorized to delete device $device->device_code.",
                );
            }
        }

        // Perform bulk action
        Device::whereIn("id", $deviceIds)->update([
            "is_active" => false,
        ]);

        // Audit log for bulk operation
        activity("device")
            ->event('bulk_deleted')
            ->withProperties([
                'device_ids' => $deviceIds,
                'device_count' => count($deviceIds)
            ])
            ->log("Bulk deleted " . count($deviceIds) . " devices");
    }

    /**
     * Regenerate device API key
     */
    public function regenerateApiKey(Device $device): array
    {
        $newApiKey = Str::random(64);

        $device->update(["api_key" => $newApiKey]);

        return [
            "api_key" => $newApiKey,
        ];
    }
}
