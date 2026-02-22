<?php

namespace App\Services\Device;

use App\Models\DeviceModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class DeviceModelService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return QueryBuilder::for(DeviceModel::class)
            ->with(['sensorSlots.sensorType', 'availableSensorTypes'])
            ->allowedFilters([
                AllowedFilter::exact('vendor'),
                AllowedFilter::exact('is_configurable'),
                AllowedFilter::partial('model_name'),
            ])
            ->allowedSorts(['vendor', 'model_name', 'created_at'])
            ->defaultSort('vendor', 'model_name')
            ->paginate($filters['per_page'] ?? 50);
    }

    public function create(array $data): DeviceModel
    {
        $model = DeviceModel::create([
            'vendor' => $data['vendor'],
            'model_name' => $data['model_name'],
            'description' => $data['description'] ?? null,
            'max_slots' => $data['max_slots'],
            'is_configurable' => $data['is_configurable'] ?? false,
            'data_format' => $data['data_format'] ?? null,
        ]);

        foreach ($data['sensor_slots'] as $slot) {
            $model->sensorSlots()->create([
                'slot_number' => $slot['slot_number'],
                'fixed_sensor_type_id' => $slot['fixed_sensor_type_id'] ?? null,
                'label' => $slot['label'] ?? null,
                'accuracy' => $slot['accuracy'] ?? null,
                'resolution' => $slot['resolution'] ?? null,
                'measurement_range' => $slot['measurement_range'] ?? null,
            ]);
        }

        if ($model->is_configurable && !empty($data['available_sensor_type_ids'])) {
            $model->availableSensorTypes()->sync($data['available_sensor_type_ids']);
        }

        return $model->load(['sensorSlots.sensorType', 'availableSensorTypes']);
    }

    public function update(DeviceModel $model, array $data): DeviceModel
    {
        $model->update(\Arr::only($data, ['description', 'data_format']));
        return $model->fresh(['sensorSlots.sensorType', 'availableSensorTypes']);
    }

    public function delete(DeviceModel $model): void
    {
        if ($model->devices()->exists()) {
            throw new \InvalidArgumentException('Cannot delete a device model that has assigned devices.');
        }

        $model->delete();
    }
}
