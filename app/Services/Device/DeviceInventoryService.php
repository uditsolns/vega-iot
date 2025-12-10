<?php

namespace App\Services\Device;

use App\Models\Area;
use App\Models\Device;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DeviceInventoryService
{
    /**
     * Get system inventory devices (unassigned)
     */
    public function getSystemInventory(array $filters): LengthAwarePaginator
    {
        return QueryBuilder::for(Device::systemInventory())
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::partial('device_name'),
                AllowedFilter::partial('device_code'),
                AllowedFilter::partial('device_uid'),
            ])
            ->allowedSorts([
                'device_code',
                'created_at',
                'status',
            ])
            ->allowedIncludes(['currentConfiguration'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get company inventory devices (assigned to company but not deployed)
     */
    public function getCompanyInventory(int $companyId, array $filters): LengthAwarePaginator
    {
        return QueryBuilder::for(Device::companyInventory()->where('company_id', $companyId))
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::partial('device_name'),
                AllowedFilter::partial('device_code'),
                AllowedFilter::partial('device_uid'),
            ])
            ->allowedSorts([
                'device_code',
                'device_name',
                'created_at',
                'status',
            ])
            ->allowedIncludes(['company', 'currentConfiguration'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get deployed devices (assigned to area)
     */
    public function getDeployedDevices(int $companyId, array $filters): LengthAwarePaginator
    {
        return QueryBuilder::for(Device::deployed()->where('company_id', $companyId))
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('area_id'),
                AllowedFilter::partial('device_name'),
                AllowedFilter::partial('device_code'),
                AllowedFilter::partial('device_uid'),
            ])
            ->allowedSorts([
                'device_code',
                'device_name',
                'created_at',
                'status',
                'last_reading_at',
            ])
            ->allowedIncludes(['area.hub.location', 'company', 'currentConfiguration'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
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
    public function assignToCompany(Device $device, int $companyId, ?string $name = null): Device
    {
        $data = [
            'company_id' => $companyId,
            'area_id' => null,
        ];

        if ($name) {
            $data['device_name'] = $name;
        }

        $device->update($data);
        return $device->fresh(['company']);
    }

    /**
     * Assign device to area (deploy)
     */
    public function assignToArea(Device $device, int $areaId, ?string $name = null): Device
    {
        // Load area with location to validate company
        $area = Area::with('hub.location')->findOrFail($areaId);

        // Validate area belongs to device's company
        if ($device->company_id && $area->hub->location->company_id !== $device->company_id) {
            throw new InvalidArgumentException('Area does not belong to the device\'s company.');
        }

        $data = [
            'area_id' => $areaId,
        ];

        // If device doesn't have company, assign it from the area's location
        if (!$device->company_id) {
            $data['company_id'] = $area->hub->location->company_id;
        }

        if ($name) {
            $data['device_name'] = $name;
        }

        $device->update($data);
        return $device->fresh(['company', 'area.hub.location']);
    }

    /**
     * Bulk assign devices
     */
    public function bulkAssign(array $deviceIds, ?int $companyId = null, ?int $areaId = null): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($deviceIds as $deviceId) {
            try {
                $device = Device::findOrFail($deviceId);

                if ($areaId) {
                    // Deploy to area
                    $device = $this->assignToArea($device, $areaId);
                } elseif ($companyId) {
                    // Assign to company
                    $device = $this->assignToCompany($device, $companyId);
                } else {
                    throw new InvalidArgumentException('Must provide either company_id or area_id.');
                }

                $results['success'][] = [
                    'device_id' => $device->id,
                    'device_code' => $device->device_code,
                    'company_id' => $device->company_id,
                    'area_id' => $device->area_id,
                ];
            } catch (Exception $e) {
                $results['failed'][] = [
                    'device_id' => $deviceId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
