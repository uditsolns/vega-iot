<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\UpdateConfigurationRequest;
use App\Http\Resources\DeviceConfigurationResource;
use App\Models\Device;
use App\Services\Device\DeviceConfigurationService;
use Illuminate\Http\JsonResponse;

class DeviceConfigurationController extends Controller
{
    public function __construct(private readonly DeviceConfigurationService $configService) {}

    public function show(Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        $config = $this->configService->getCurrent($device);

        if (!$config) {
            return $this->error('No configuration found for this device', 404);
        }

        return $this->success(new DeviceConfigurationResource($config));
    }

    public function update(UpdateConfigurationRequest $request, Device $device): JsonResponse
    {
        $this->authorize('configure', $device);
        $config = $this->configService->update($device, $request->validated(), $request->user());
        return $this->success(new DeviceConfigurationResource($config), 'Configuration updated successfully');
    }

    public function history(Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        $history = $this->configService->getHistory($device);
        return $this->success(DeviceConfigurationResource::collection($history));
    }
}
