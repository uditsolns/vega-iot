<?php

namespace App\Http\Controllers\Device;

use App\Exceptions\DeviceAssignmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\BulkAssignDevicesToAreaRequest;
use App\Http\Requests\Device\BulkAssignDevicesToCompanyRequest;
use App\Http\Requests\Device\BulkChangeStatusRequest;
use App\Http\Requests\Device\BulkConfigureDevicesRequest;
use App\Http\Requests\Device\BulkDeviceRequest;
use App\Models\Device;
use App\Services\Device\DeviceConfigurationService;
use App\Services\Device\DeviceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class DeviceBulkController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly DeviceConfigurationService $configService,
    ) {}

    /**
     * Bulk assign devices to company.
     */
    public function bulkAssignToCompany(
        BulkAssignDevicesToCompanyRequest $request,
    ): JsonResponse {
        $this->authorize("bulkAssignToCompany", Device::class);

        $validated = $request->validated();

        $this->deviceService->bulkAssignToCompany(
            $validated["device_ids"],
            $validated["company_id"],
            $request->user(),
        );

        return $this->success(
            null,
            "Successfully assigned " .
                count($validated["device_ids"]) .
                " devices to company",
        );
    }

    /**
     * Bulk assign devices to area.
     * @throws DeviceAssignmentException
     */
    public function bulkAssignToArea(
        BulkAssignDevicesToAreaRequest $request,
    ): JsonResponse {
        $this->authorize("bulkAssignToArea", Device::class);

        $validated = $request->validated();

        $this->deviceService->bulkAssignToArea(
            $validated["device_ids"],
            $validated["area_id"],
            $request->user(),
        );

        return $this->success(
            null,
            "Successfully assigned " .
                count($validated["device_ids"]) .
                " devices to area",
        );
    }

    /**
     * Bulk unassign devices (return to system inventory).
     */
    public function bulkUnassign(BulkDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->deviceService->bulkUnassign(
            $validated["device_ids"],
            $request->user(),
        );

        return $this->success(
            null,
            "Successfully unassigned " .
                count($validated["device_ids"]) .
                " devices",
        );
    }

    /**
     * Bulk configure devices (apply same configuration to multiple devices).
     * @throws Throwable
     */
    public function bulkConfigure(
        BulkConfigureDevicesRequest $request,
    ): JsonResponse {
        $validated = $request->validated();
        $configData = $request->safe()->except(["device_ids"]);

        $this->configService->bulkUpdate(
            $validated["device_ids"],
            $configData,
            $request->user(),
        );

        return $this->success(
            null,
            "Successfully configured " .
                count($validated["device_ids"]) .
                " devices",
        );
    }

    /**
     * Bulk change device status.
     */
    public function bulkChangeStatus(
        BulkChangeStatusRequest $request,
    ): JsonResponse {
        $validated = $request->validated();

        $this->deviceService->bulkChangeStatus(
            $validated["device_ids"],
            $validated["status"],
            $request->user(),
        );

        return $this->success(
            null,
            "Successfully changed status for " .
                count($validated["device_ids"]) .
                " devices",
        );
    }

    /**
     * Bulk delete devices (set is_active = false).
     */
    public function bulkDelete(BulkDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->deviceService->bulkDelete(
            $validated["device_ids"],
            $request->user(),
        );

        return $this->success(
            null,
            "Successfully deleted " .
                count($validated["device_ids"]) .
                " devices",
        );
    }
}
