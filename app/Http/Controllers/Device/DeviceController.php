<?php

namespace App\Http\Controllers\Device;

use App\Exceptions\DeviceAssignmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\AssignDeviceToAreaRequest;
use App\Http\Requests\Device\AssignDeviceToCompanyRequest;
use App\Http\Requests\Device\ChangeStatusRequest;
use App\Http\Requests\Device\CreateDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\Device\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(private readonly DeviceService $deviceService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Device::class);
        $devices = $this->deviceService->list($request->all(), $request->user());
        return $this->collection(DeviceResource::collection($devices));
    }

    public function store(CreateDeviceRequest $request): JsonResponse
    {
        $this->authorize('create', Device::class);
        $device = $this->deviceService->create($request->validated(), $request->user());
        return $this->created(new DeviceResource($device), 'Device created successfully');
    }

    public function show(Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        $device->load(['deviceModel', 'company', 'area.hub.location', 'sensors.sensorType', 'sensors.currentConfiguration', 'currentConfiguration']);
        return $this->success(new DeviceResource($device));
    }

    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);
        $device = $this->deviceService->update($device, $request->validated());
        return $this->success(new DeviceResource($device), 'Device updated successfully');
    }

    public function destroy(Device $device): JsonResponse
    {
        $this->authorize('delete', $device);
        $this->deviceService->delete($device);
        return $this->success(null, 'Device deleted successfully');
    }

    public function changeStatus(ChangeStatusRequest $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);
        $device = $this->deviceService->changeStatus($device, $request->validated()['status']);
        return $this->success(new DeviceResource($device), 'Device status updated successfully');
    }

    public function assignToCompany(AssignDeviceToCompanyRequest $request, Device $device): JsonResponse
    {
        $this->authorize('assignToCompany', $device);
        $device = $this->deviceService->assignToCompany($device, $request->validated());
        return $this->success(new DeviceResource($device), 'Device assigned to company successfully');
    }

    public function assignToArea(AssignDeviceToAreaRequest $request, Device $device): JsonResponse
    {
        $validated = $request->validated();
        $this->authorize('assignToArea', [$device, $validated['area_id']]);

        try {
            $device = $this->deviceService->assignToArea($device, $validated);
        } catch (DeviceAssignmentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new DeviceResource($device), 'Device assigned to area successfully');
    }

    public function unassign(Device $device): JsonResponse
    {
        $this->authorize('assignToCompany', $device);
        $device = $this->deviceService->unassign($device);
        return $this->success(new DeviceResource($device), 'Device unassigned successfully');
    }

    public function activate(Device $device): JsonResponse
    {
        $this->authorize('update', $device);
        $device = $this->deviceService->update($device, ['is_active' => true]);
        return $this->success(new DeviceResource($device), 'Device activated successfully');
    }

    public function deactivate(Device $device): JsonResponse
    {
        $this->authorize('update', $device);
        $device = $this->deviceService->update($device, ['is_active' => false]);
        return $this->success(new DeviceResource($device), 'Device deactivated successfully');
    }

    public function getStats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Device::class);

        if (!$request->user()->company_id) {
            return $this->error('Statistics only available for company users', 403);
        }

        return $this->success($this->deviceService->getStats($request->user()->company_id));
    }
}
