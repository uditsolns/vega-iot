<?php

namespace App\Http\Controllers\Device;

use App\Exceptions\DeviceAssignmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\AssignDeviceToAreaRequest;
use App\Http\Requests\Device\AssignDeviceToCompanyRequest;
use App\Http\Requests\Device\ChangeStatusRequest;
use App\Http\Requests\Device\CreateDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Http\Requests\Reading\ListReadingsRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\ReadingResource;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Services\Device\DeviceInventoryService;
use App\Services\Device\DeviceService;
use App\Services\Reading\ReadingQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly ReadingQueryService $readingQueryService,
    ) {}

    /**
     * Display a listing of devices.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Device::class);

        $devices = $this->deviceService->list(
            $request->all(),
            $request->user(),
        );

        return $this->collection(DeviceResource::collection($devices));
    }

    /**
     * Store a newly created device.
     */
    public function store(CreateDeviceRequest $request): JsonResponse
    {
        $this->authorize("create", Device::class);

        $device = $this->deviceService->create($request->validated());

        return $this->created(
            new DeviceResource($device),
            "Device created successfully",
        );
    }

    /**
     * Display the specified device.
     */
    public function show(Device $device): JsonResponse
    {
        $this->authorize("view", $device);

        $device->load(["company", "area.hub.location", "currentConfiguration"]);

        return $this->success(new DeviceResource($device));
    }

    /**
     * Update the specified device.
     */
    public function update(
        UpdateDeviceRequest $request,
        Device $device,
    ): JsonResponse {
        $this->authorize("update", $device);

        $device = $this->deviceService->update($device, $request->validated());

        return $this->success(
            new DeviceResource($device),
            "Device updated successfully",
        );
    }

    /**
     * Remove the specified device.
     */
    public function destroy(Device $device): JsonResponse
    {
        $this->authorize("delete", $device);

        $this->deviceService->delete($device);

        return $this->success(null, "Device deleted successfully");
    }

    /**
     * Activate a device.
     */
    public function activate(Device $device): JsonResponse
    {
        $this->authorize("update", $device);

        $device = $this->deviceService->update($device, ["is_active" => true]);

        return $this->success(
            new DeviceResource($device),
            "Device activated successfully",
        );
    }

    /**
     * Deactivate a device.
     */
    public function deactivate(Device $device): JsonResponse
    {
        $this->authorize("update", $device);

        $device = $this->deviceService->update($device, ["is_active" => false]);

        return $this->success(
            new DeviceResource($device),
            "Device deactivated successfully",
        );
    }

    /**
     * Restore is not applicable (no soft deletes).
     */
    public function restore(int $id): JsonResponse
    {
        return $this->error("Device restoration not supported", 404);
    }

    /**
     * Change device status.
     */
    public function changeStatus(
        ChangeStatusRequest $request,
        Device $device,
    ): JsonResponse {
        $this->authorize("update", $device);

        $device = $this->deviceService->changeStatus(
            $device,
            $request->validated()["status"],
        );

        return $this->success(
            new DeviceResource($device),
            "Device status updated successfully",
        );
    }

    /**
     * Assign device to company.
     */
    public function assignToCompany(
        AssignDeviceToCompanyRequest $request,
        Device $device,
    ): JsonResponse {
        $this->authorize("assignToCompany", $device);

        $device = $this->deviceService->assignToCompany(
            $device,
            $request->validated(),
        );

        return $this->success(
            new DeviceResource($device),
            "Device assigned to company successfully",
        );
    }

    /**
     * Assign device to area.
     * @throws DeviceAssignmentException
     */
    public function assignToArea(
        AssignDeviceToAreaRequest $request,
        Device $device,
    ): JsonResponse {
        $validated = $request->validated();
        $this->authorize("assignToArea", [$device, $validated["area_id"]]);

        $device = $this->deviceService->assignToArea($device, $validated);

        return $this->success(
            new DeviceResource($device),
            "Device assigned to area successfully",
        );
    }

    /**
     * Unassign device (return to system inventory).
     */
    public function unassign(Device $device): JsonResponse
    {
        $this->authorize("assign-to-company", $device);

        $device = $this->deviceService->unassign($device);

        return $this->success(
            new DeviceResource($device),
            "Device unassigned successfully",
        );
    }

    /**
     * Regenerate device API key.
     */
    public function regenerateApiKey(Device $device): JsonResponse
    {
        $this->authorize("regenerateApiKey", $device);

        $result = $this->deviceService->regenerateApiKey($device);

        return $this->success($result, "API key regenerated successfully");
    }

    /**
     * Get readings for a specific device.
     */
    public function getReadings(
        ListReadingsRequest $request,
        Device $device,
    ): JsonResponse {
        $this->authorize("viewDevice", [DeviceReading::class, $device]);

        $readings = $this->readingQueryService->getDeviceReadings(
            $device,
            $request->validated(),
            $request->user(),
        );

        return $this->collection(ReadingResource::collection($readings));
    }

    /**
     * Get the latest reading for a device.
     */
    public function getLatestReading(Device $device): JsonResponse
    {
        $this->authorize("viewDevice", [DeviceReading::class, $device]);

        $reading = $this->readingQueryService->getLatestReading(
            $device,
            request()->user(),
        );

        if (!$reading) {
            return $this->success(null, "No readings found for this device");
        }

        return $this->success(new ReadingResource($reading));
    }

    /**
     * Get the latest reading for a device.
     */
    public function getReadingsAvailableDates(Device $device): JsonResponse
    {
        $this->authorize("viewDevice", [DeviceReading::class, $device]);

        $dates = $this->readingQueryService->getReadingsAvailableDates(
            $device,
            request()->user(),
        );

        if (!$dates) {
            return $this->success(null, "No readings found for this device");
        }

        return $this->success($dates);
    }

    /**
     * Get device alerts (stub for Phase 5).
     */
    public function getAlerts(Device $device): JsonResponse
    {
        $this->authorize("view", $device);

        return $this->success([], "TODO: Phase 5 - Device alerts endpoint");
    }

    /**
     * Get device statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Device::class);

        $user = $request->user();

        if (!$user->company_id) {
            return $this->error(
                "Statistics only available for company users",
                403,
            );
        }

        $stats = $this->deviceService->getDeviceStats($user->company_id);

        return $this->success($stats);
    }
}
