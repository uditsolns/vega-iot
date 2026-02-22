<?php

namespace App\Services\Device;

use App\Enums\DeviceStatus;
use App\Exceptions\DeviceAssignmentException;
use App\Models\Area;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class DeviceService
{
    public function __construct(
        private DeviceConfigurationService $configService,
        private DeviceSensorService $sensorService,
    ) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Device::forUser($user))
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('area_id'),
                AllowedFilter::exact('device_model_id'),
                AllowedFilter::partial('device_name'),
                AllowedFilter::partial('device_code'),
                AllowedFilter::partial('device_uid'),
                AllowedFilter::scope('systemInventory'),
                AllowedFilter::scope('companyInventory'),
                AllowedFilter::scope('deployed'),
            ])
            ->allowedSorts(['device_name', 'device_code', 'created_at', 'status', 'last_reading_at'])
            ->allowedIncludes([
                'deviceModel',
                'area.hub.location',
                'company',
                'currentConfiguration',
                'sensors.sensorType',
                'sensors.currentConfiguration',
            ])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data, ?User $createdBy = null): Device
    {
        $deviceModel = DeviceModel::with(['sensorSlots', 'availableSensorTypes'])
            ->findOrFail($data['device_model_id']);

        $device = Device::create([
            'device_uid' => $data['device_uid'],
            'device_code' => $data['device_code'],
            'device_model_id' => $deviceModel->id,
            'firmware_version' => $data['firmware_version'] ?? null,
        ]);

        if ($deviceModel->is_configurable) {
            $this->sensorService->setupConfigurable($device, $data['slot_sensors'], $createdBy?->id);
        } else {
            $this->sensorService->setupFromModel($device, $createdBy?->id);
        }

        $this->configService->createDefault($device);

        return $device->fresh(['deviceModel', 'sensors.sensorType', 'currentConfiguration']);
    }

    public function update(Device $device, array $data): Device
    {
        $device->update(\Arr::only($data, ['device_name', 'firmware_version', 'is_active', 'status']));
        return $device->fresh(['deviceModel', 'currentConfiguration']);
    }

    public function delete(Device $device): void
    {
        $device->update(['is_active' => false]);
    }

    public function changeStatus(Device $device, string $status): Device
    {
        $statusEnum = DeviceStatus::tryFrom($status)
            ?? throw new InvalidArgumentException("Invalid status: {$status}");

        $device->update(['status' => $statusEnum]);

        activity('device')->event('changed_status')
            ->performedOn($device)
            ->withProperties(['device_id' => $device->id])
            ->log("Changed status to \"{$statusEnum->value}\" for device \"{$device->device_code}\"");

        return $device->fresh();
    }

    public function assignToCompany(Device $device, array $data): Device
    {
        $company = Company::findOrFail($data['company_id']);

        $device->update([
            'company_id' => $company->id,
            'area_id' => null,
            'device_name' => $data['device_name'] ?? $device->device_name,
        ]);

        activity('device')->event('assigned_to_company')
            ->performedOn($device)
            ->withProperties(['device_id' => $device->id, 'company_id' => $company->id])
            ->log("Assigned device \"{$device->device_code}\" to company \"{$company->name}\"");

        return $device->fresh(['company']);
    }

    public function assignToArea(Device $device, array $data): Device
    {
        $area = Area::with('hub.location')->findOrFail($data['area_id']);

        if (!$device->company_id) {
            throw DeviceAssignmentException::deviceRequiresCompany();
        }

        if ($area->hub->location->company_id !== $device->company_id) {
            throw DeviceAssignmentException::areaMismatch($device->device_code, $area->name);
        }

        $device->update([
            'area_id' => $area->id,
            'device_name' => $data['device_name'] ?? $device->device_name,
        ]);

        activity('device')->event('assigned_to_area')
            ->performedOn($device)
            ->withProperties(['device_id' => $device->id, 'area_id' => $area->id])
            ->log("Assigned device \"{$device->device_code}\" to area \"{$area->name}\"");

        return $device->fresh(['company', 'area.hub.location']);
    }

    public function unassign(Device $device): Device
    {
        $device->update(['company_id' => null, 'area_id' => null, 'device_name' => null]);

        activity('device')->event('unassigned')
            ->performedOn($device)
            ->withProperties(['device_id' => $device->id])
            ->log("Unassigned device \"{$device->device_code}\"");

        return $device->fresh();
    }

    public function getStats(int $companyId): array
    {
        $base = Device::where('company_id', $companyId);

        return [
            'total' => (clone $base)->count(),
            'online' => (clone $base)->byStatus(DeviceStatus::Online)->count(),
            'offline' => (clone $base)->byStatus(DeviceStatus::Offline)->count(),
            'maintenance' => (clone $base)->byStatus(DeviceStatus::Maintenance)->count(),
            'company_inventory' => (clone $base)->whereNull('area_id')->count(),
            'deployed' => (clone $base)->deployed()->count(),
        ];
    }
}
