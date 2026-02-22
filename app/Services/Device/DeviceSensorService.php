<?php

namespace App\Services\Device;

use App\Models\Device;
use App\Models\DeviceSensor;
use App\Models\SensorConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

readonly class DeviceSensorService
{
    /**
     * Auto-populate device_sensors from model slots for FIXED models.
     */
    public function setupFromModel(Device $device, ?int $createdBy = null): void
    {
        $device->loadMissing(['deviceModel.sensorSlots']);
        $slots = $device->deviceModel->sensorSlots;

        foreach ($slots as $slot) {
            DeviceSensor::create([
                'device_id' => $device->id,
                'slot_number' => $slot->slot_number,
                'sensor_type_id' => $slot->fixed_sensor_type_id,
                'is_enabled' => true,
                'label' => $slot->label,
                'accuracy' => $slot->accuracy,
                'resolution' => $slot->resolution,
                'measurement_range' => $slot->measurement_range,
                'created_by' => $createdBy,
            ]);
        }
    }

    /**
     * Setup device_sensors from admin-provided slot assignments for CONFIGURABLE models.
     */
    public function setupConfigurable(Device $device, array $slotSensors, ?int $createdBy = null): void
    {
        $device->loadMissing(['deviceModel.sensorSlots']);
        $slotMap = $device->deviceModel->sensorSlots->keyBy('slot_number');

        foreach ($slotSensors as $assignment) {
            $slot = $slotMap->get($assignment['slot_number']);

            DeviceSensor::create([
                'device_id' => $device->id,
                'slot_number' => $assignment['slot_number'],
                'sensor_type_id' => $assignment['sensor_type_id'],
                'is_enabled' => $assignment['is_enabled'] ?? true,
                'label' => $assignment['label'] ?? $slot?->label,
                'accuracy' => $slot?->accuracy,
                'resolution' => $slot?->resolution,
                'measurement_range' => $slot?->measurement_range,
                'created_by' => $createdBy,
            ]);
        }
    }

    public function getSensors(Device $device): Collection
    {
        return $device->sensors()->with(['sensorType', 'currentConfiguration'])->get();
    }

    public function update(DeviceSensor $sensor, array $data): DeviceSensor
    {
        $sensor->update(\Arr::only($data, ['is_enabled', 'label']));
        return $sensor->fresh(['sensorType', 'currentConfiguration']);
    }

    public function getCurrentConfiguration(DeviceSensor $sensor): ?SensorConfiguration
    {
        return $sensor->currentConfiguration;
    }

    public function updateConfiguration(DeviceSensor $sensor, array $data, User $user): SensorConfiguration
    {
        return DB::transaction(function () use ($sensor, $data, $user) {
            SensorConfiguration::where('device_sensor_id', $sensor->id)
                ->whereNull('effective_to')
                ->update(['effective_to' => now()]);

            return SensorConfiguration::create([
                'device_sensor_id' => $sensor->id,
                'min_critical' => $data['min_critical'] ?? null,
                'max_critical' => $data['max_critical'] ?? null,
                'min_warning' => $data['min_warning'] ?? null,
                'max_warning' => $data['max_warning'] ?? null,
                'effective_from' => now(),
                'updated_by' => $user->id,
            ])->fresh();
        });
    }

    public function getConfigurationHistory(DeviceSensor $sensor): Collection
    {
        return $sensor->configurations()->with('updatedBy')->get();
    }
}
