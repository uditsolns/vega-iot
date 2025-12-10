<?php

namespace App\Http\Controllers;

use App\Http\Requests\Alert\ListAlertsRequest;
use App\Http\Requests\Area\CreateAreaRequest;
use App\Http\Requests\Area\UpdateAlertConfigRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Http\Requests\Reading\ListReadingsRequest;
use App\Http\Resources\AlertResource;
use App\Http\Resources\AreaResource;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\ReadingResource;
use App\Models\Area;
use App\Models\DeviceReading;
use App\Services\Alert\AlertService;
use App\Services\Company\AreaService;
use App\Services\Reading\ReadingQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function __construct(
        private readonly AreaService $areaService,
        private readonly AlertService $alertService,
        private readonly ReadingQueryService $readingQueryService,
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

        return $this->created(
            new AreaResource($area),
            "Area created successfully",
        );
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

        return $this->success(
            new AreaResource($area),
            "Area updated successfully",
        );
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

        $area = $this->areaService->activate($area);

        return $this->success(
            new AreaResource($area),
            "Area activated successfully",
        );
    }

    public function deactivate(Request $request, Area $area): JsonResponse
    {
        $this->authorize("update", $area);

        $area = $this->areaService->deactivate($area);

        return $this->success(
            new AreaResource($area),
            "Area deactivated successfully",
        );
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $area = Area::onlyTrashed()->findOrFail($id);

        $this->authorize("restore", $area);

        $area = $this->areaService->restore($area);

        return $this->success(
            new AreaResource($area),
            "Area restored successfully",
        );
    }

    public function updateAlertConfig(
        UpdateAlertConfigRequest $request,
        Area $area,
    ): JsonResponse {
        $this->authorize("updateAlertConfig", $area);

        $area = $this->areaService->updateAlertConfig(
            $area,
            $request->validated(),
        );

        return $this->success(
            new AreaResource($area),
            "Alert configuration updated successfully",
        );
    }

    public function copyAlertConfig(
        Request $request,
        Area $sourceArea,
    ): JsonResponse {
        $this->authorize("view", $sourceArea);

        $request->validate([
            "target_area_ids" => ["required", "array", "min:1"],
            "target_area_ids.*" => ["required", "integer", "exists:areas,id"],
        ]);

        $result = $this->areaService->copyAlertConfig(
            $sourceArea,
            $request->input("target_area_ids"),
        );

        return $this->success(
            $result,
            "Alert configuration copied successfully",
        );
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

        $stats = $this->areaService->getStats($area);

        return $this->success($stats);
    }

    /**
     * Get alerts for all devices in the area.
     */
    public function alerts(Area $area, ListAlertsRequest $request): JsonResponse
    {
        $this->authorize("view", $area);

        $alerts = $this->alertService->listForArea(
            $area,
            $request->validated(),
            $request->user(),
        );

        return $this->collection(AlertResource::collection($alerts));
    }

    /**
     * Get readings for all devices in the area.
     */
    public function getReadings(
        Area $area,
        ListReadingsRequest $request,
    ): JsonResponse {
        $this->authorize("viewArea", [DeviceReading::class, $area]);

        $readings = $this->readingQueryService->getAreaReadings(
            $area,
            $request->validated(),
            $request->user(),
        );

        return $this->collection(ReadingResource::collection($readings));
    }
}
