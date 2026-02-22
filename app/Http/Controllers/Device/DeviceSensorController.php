<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\UpdateDeviceSensorRequest;
use App\Http\Requests\Device\UpdateSensorConfigurationRequest;
use App\Http\Resources\DeviceSensorResource;
use App\Http\Resources\SensorConfigurationResource;
use App\Models\Device;
use App\Models\DeviceSensor;
use App\Services\Device\DeviceSensorService;
use Illuminate\Http\JsonResponse;

class DeviceSensorController extends Controller
{
    public function __construct(private readonly DeviceSensorService $service) {}

    public function index(Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        $sensors = $this->service->getSensors($device);
        return $this->success(DeviceSensorResource::collection($sensors));
    }

    public function update(UpdateDeviceSensorRequest $request, Device $device, DeviceSensor $sensor): JsonResponse
    {
        $this->authorize('configure', $device);
        abort_if($sensor->device_id !== $device->id, 404);

        $sensor = $this->service->update($sensor, $request->validated());
        return $this->success(new DeviceSensorResource($sensor), 'Sensor updated successfully');
    }

    public function showConfiguration(Device $device, DeviceSensor $sensor): JsonResponse
    {
        $this->authorize('view', $device);
        abort_if($sensor->device_id !== $device->id, 404);

        $config = $this->service->getCurrentConfiguration($sensor);
        if (!$config) {
            return $this->success(null, 'No configuration set for this sensor');
        }

        return $this->success(new SensorConfigurationResource($config));
    }

    public function updateConfiguration(
        UpdateSensorConfigurationRequest $request,
        Device $device,
        DeviceSensor $sensor,
    ): JsonResponse {
        $this->authorize('configure', $device);
        abort_if($sensor->device_id !== $device->id, 404);
        abort_if(!$sensor->sensorType->supports_threshold_config, 422, 'Sensor type does not support threshold configuration.');

        $config = $this->service->updateConfiguration($sensor, $request->validated(), $request->user());
        return $this->success(new SensorConfigurationResource($config), 'Sensor configuration updated');
    }

    public function configurationHistory(Device $device, DeviceSensor $sensor): JsonResponse
    {
        $this->authorize('view', $device);
        abort_if($sensor->device_id !== $device->id, 404);

        $history = $this->service->getConfigurationHistory($sensor);
        return $this->success(SensorConfigurationResource::collection($history));
    }
}
