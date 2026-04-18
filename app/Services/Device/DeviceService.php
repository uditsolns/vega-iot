<?php

namespace App\Services\Device;

use App\Enums\DeviceStatus;
use App\Exceptions\DeviceAssignmentException;
use App\Models\Area;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class DeviceService
{
    public function __construct(
        private DeviceConfigurationService $configService,
        private DeviceSensorService        $sensorService,
    )
    {
    }

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
            ->allowedIncludes($this->showIncludes())
            ->with(['sensors', 'sensors.sensorType', 'sensors.latestReading'])
            ->defaultSort('-last_reading_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function showIncludes(): array
    {
        return [
            'deviceModel',
            'company',
            'area.hub.location',
            'sensors.sensorType',
            'sensors.currentConfiguration',
            'sensors.latestReading',
            'currentConfiguration',
            'assignedBy',
        ];
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
        $device->update(\Arr::only($data, ['device_uid', 'device_name', 'firmware_version', 'is_active', 'status']));
        return $device->fresh(['deviceModel', 'currentConfiguration']);
    }

    public function updateAssetInfo(Device $device, array $data): Device
    {
        $device->update(\Arr::only($data, [
            'device_name',
            'installation_date',
            'subscription_start_date',
            'subscription_end_date',
            'warranty_start_date',
            'warranty_end_date',
            'calibration_start_date',
            'calibration_end_date',
        ]));

        return $device->fresh();
    }

    public function updateCalibrationInfo(Device $device, array $data): Device
    {
        $device->update(\Arr::only($data, ['calibration_start_date', 'calibration_end_date']));
        return $device->fresh();
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

    public function assignToCompany(Device $device, array $data, User $user): Device
    {
        $company = Company::findOrFail($data['company_id']);

        $device->update([
            'company_id' => $company->id,
            'area_id' => null,
            'device_name' => $data['device_name'] ?? $device->device_name,
            'assigned_at' => now(),
            'assigned_by' => $user->id,
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
        if (!$device->isDeployed()) {
            throw DeviceAssignmentException::deviceNotAssigned($device->device_code);
        }

        $area = $device->area;
        $device->update(['area_id' => null, 'device_name' => 'Unassigned']);

        activity('device')
            ->event('unassigned')
            ->performedOn($device)
            ->withProperties(['device_id' => $device->id, 'area_id' => $area->id])
            ->log("Unassigned device \"{$device->device_code}\" from area \"{$area->name}\"");

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

    public function getReadings(Device $device, array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);

        $query = SensorReading::where('device_id', $device->id)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderByDesc('recorded_at');

        if (!empty($filters['sensor_id'])) {
            $query->where('device_sensor_id', (int)$filters['sensor_id']);
        }

        return $query->get();
    }

    private function resolveRange(array $filters): array
    {
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $from = Carbon::parse($filters['from'])->startOfDay()->utc();
            $to = Carbon::parse($filters['to'])->endOfDay()->utc();

            if ($from->diffInDays($to) > 31) {
                $to = $from->copy()->addDays(31)->endOfDay();
            }

            return [$from, $to];
        }

        if (!empty($filters['date'])) {
            $day = Carbon::parse($filters['date'])->utc();
            return [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
        }

        $today = Carbon::today('UTC');
        return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
    }
}
