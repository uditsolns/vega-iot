<?php

namespace App\Http\Controllers\Hierarchy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Area\CreateAreaRequest;
use App\Http\Requests\Area\UpdateAlertConfigRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Http\Requests\Area\UploadAreaReportRequest;
use App\Http\Resources\AreaResource;
use App\Http\Resources\DeviceResource;
use App\Models\Area;
use App\Services\Company\AreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AreaController extends Controller
{
    public function __construct(
        private readonly AreaService $areaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Area::class);

        $areas = $this->areaService->list($request->all(), $request->user());

        return $this->collection(AreaResource::collection($areas));
    }

    public function store(CreateAreaRequest $request): JsonResponse
    {
        $this->authorize("create", Area::class);

        $area = $this->areaService->create($request->validated());

        return $this->created(new AreaResource($area), "Area created successfully");
    }

    public function show(Request $request, Area $area): JsonResponse
    {
        $this->authorize("view", $area);

        $area->loadMissing(["hub"]);

        return $this->success(new AreaResource($area));
    }

    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        $area = $this->areaService->update($area, $request->validated());

        return $this->success(new AreaResource($area), "Area updated successfully");
    }

    public function destroy(Request $request, Area $area): JsonResponse
    {
        $this->authorize("delete", $area);

        $this->areaService->delete($area);

        return $this->success(null, "Area deleted successfully");
    }

    public function activate(Request $request, Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        return $this->success(new AreaResource($this->areaService->activate($area)), "Area activated successfully");
    }

    public function deactivate(Request $request, Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        return $this->success(new AreaResource($this->areaService->deactivate($area)), "Area deactivated successfully");
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $area = Area::onlyTrashed()->findOrFail($id);

        $this->authorize("restore", $area);

        return $this->success(new AreaResource($this->areaService->restore($area)), "Area restored successfully");
    }

    public function updateAlertConfig(UpdateAlertConfigRequest $request, Area $area): JsonResponse
    {
        $this->authorize("updateAlertConfig", $area);

        $area = $this->areaService->updateAlertConfig($area, $request->validated());

        return $this->success(new AreaResource($area), "Alert configuration updated successfully");
    }

    public function copyAlertConfig(Request $request, Area $sourceArea): JsonResponse
    {
        $this->authorize("view", $sourceArea);

        $request->validate([
            "target_area_ids"   => ["required", "array", "min:1"],
            "target_area_ids.*" => ["required", "integer", "exists:areas,id"],
        ]);

        $result = $this->areaService->copyAlertConfig($sourceArea, $request->input("target_area_ids"));

        return $this->success($result, "Alert configuration copied successfully");
    }

    public function devices(Request $request, Area $area): JsonResponse
    {
        $this->authorize("view", $area);

        $devices = $this->areaService->getDevices($area, $request->user());

        return $this->success(DeviceResource::collection($devices));
    }

    public function stats(Request $request, Area $area): JsonResponse
    {
        $this->authorize("view", $area);

        return $this->success($this->areaService->getStats($area));
    }

    // -------------------------------------------------------------------------
    // Mapping Report
    // -------------------------------------------------------------------------

    public function uploadMappingReport(UploadAreaReportRequest $request, Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        $area = $this->areaService->uploadMappingReport($area, $request->file('file'));

        return $this->success(new AreaResource($area), "Mapping report uploaded successfully");
    }

    public function downloadMappingReport(Area $area): StreamedResponse
    {
        $this->authorize("view", $area);

        return $this->areaService->downloadMappingReport($area);
    }

    public function deleteMappingReport(Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        $area = $this->areaService->deleteMappingReport($area);

        return $this->success(new AreaResource($area), "Mapping report deleted successfully");
    }

    // -------------------------------------------------------------------------
    // Device Calibration Report
    // -------------------------------------------------------------------------

    public function uploadDeviceCalibrationReport(UploadAreaReportRequest $request, Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        $area = $this->areaService->uploadDeviceCalibrationReport($area, $request->file('file'));

        return $this->success(new AreaResource($area), "Device calibration report uploaded successfully");
    }

    public function downloadDeviceCalibrationReport(Area $area): StreamedResponse
    {
        $this->authorize("view", $area);

        return $this->areaService->downloadDeviceCalibrationReport($area);
    }

    public function deleteDeviceCalibrationReport(Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        $area = $this->areaService->deleteDeviceCalibrationReport($area);

        return $this->success(new AreaResource($area), "Device calibration report deleted successfully");
    }
}
