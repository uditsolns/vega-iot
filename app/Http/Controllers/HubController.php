<?php

namespace App\Http\Controllers;

use App\Http\Requests\Hub\CreateHubRequest;
use App\Http\Requests\Hub\UpdateHubRequest;
use App\Http\Resources\AreaResource;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\HubResource;
use App\Models\Hub;
use App\Services\Company\HubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __construct(private readonly HubService $hubService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Hub::class);

        $hubs = $this->hubService->list($request->all(), $request->user());

        return $this->collection(HubResource::collection($hubs));
    }

    public function store(CreateHubRequest $request): JsonResponse
    {
        $this->authorize("create", Hub::class);

        $hub = $this->hubService->create($request->validated());

        return $this->created(
            new HubResource($hub),
            "Hub created successfully",
        );
    }

    public function show(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("view", $hub);

        $hub->loadMissing(["location", "areas"])->withCount("areas");

        return $this->success(new HubResource($hub));
    }

    public function update(UpdateHubRequest $request, Hub $hub): JsonResponse
    {
        $this->authorize("update", $hub);

        $hub = $this->hubService->update($hub, $request->validated());

        return $this->success(
            new HubResource($hub),
            "Hub updated successfully",
        );
    }

    public function destroy(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("delete", $hub);

        $this->hubService->delete($hub);

        return $this->success(null, "Hub deleted successfully");
    }

    public function activate(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("update", $hub);

        $hub = $this->hubService->activate($hub);

        return $this->success(
            new HubResource($hub),
            "Hub activated successfully",
        );
    }

    public function deactivate(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("update", $hub);

        $hub = $this->hubService->deactivate($hub);

        return $this->success(
            new HubResource($hub),
            "Hub deactivated successfully",
        );
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $hub = Hub::onlyTrashed()->findOrFail($id);

        $this->authorize("restore", $hub);

        $hub = $this->hubService->restore($hub);

        return $this->success(
            new HubResource($hub),
            "Hub restored successfully",
        );
    }

    public function areas(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("view", $hub);

        $hub->load("areas");

        return $this->success(AreaResource::collection($hub->areas));
    }

    public function devices(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("view", $hub);

        $devices = $this->hubService->getDevices($hub, $request->user());

        return $this->success(DeviceResource::collection($devices));
    }

    public function stats(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("view", $hub);

        $stats = $this->hubService->getStats($hub);

        return $this->success($stats);
    }
}
