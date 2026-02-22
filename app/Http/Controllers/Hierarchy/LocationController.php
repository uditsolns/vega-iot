<?php

namespace App\Http\Controllers\Hierarchy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\CreateLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\HubResource;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\Company\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Location::class);

        $locations = $this->locationService->list(
            $request->all(),
            $request->user(),
        );

        return $this->collection(LocationResource::collection($locations));
    }

    public function store(CreateLocationRequest $request): JsonResponse
    {
        $this->authorize("create", Location::class);

        $location = $this->locationService->create($request->validated());

        return $this->created(
            new LocationResource($location),
            "Location created successfully",
        );
    }

    public function show(Request $request, Location $location): JsonResponse
    {
        $this->authorize("view", $location);

        $location->loadMissing(["company", "hubs"])->loadCount("hubs");

        return $this->success(new LocationResource($location));
    }

    public function update(
        UpdateLocationRequest $request,
        Location $location,
    ): JsonResponse {
        $this->authorize("update", $location);

        $location = $this->locationService->update(
            $location,
            $request->validated(),
        );

        return $this->success(
            new LocationResource($location),
            "Location updated successfully",
        );
    }

    public function destroy(Request $request, Location $location): JsonResponse
    {
        $this->authorize("delete", $location);

        $this->locationService->delete($location);

        return $this->success(null, "Location deleted successfully");
    }

    public function activate(Request $request, Location $location): JsonResponse
    {
        $this->authorize("update", $location);

        $location = $this->locationService->activate($location);

        return $this->success(
            new LocationResource($location),
            "Location activated successfully",
        );
    }

    public function deactivate(
        Request $request,
        Location $location,
    ): JsonResponse {
        $this->authorize("update", $location);

        $location = $this->locationService->deactivate($location);

        return $this->success(
            new LocationResource($location),
            "Location deactivated successfully",
        );
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $location = Location::onlyTrashed()->findOrFail($id);

        $this->authorize("restore", $location);

        $location = $this->locationService->restore($location);

        return $this->success(
            new LocationResource($location),
            "Location restored successfully",
        );
    }

    public function hubs(Request $request, Location $location): JsonResponse
    {
        $this->authorize("view", $location);

        $location->load("hubs");

        return $this->success(HubResource::collection($location->hubs));
    }

    public function devices(Request $request, Location $location): JsonResponse
    {
        $this->authorize("view", $location);

        $devices = $this->locationService->getDevices(
            $location,
            $request->user(),
        );

        return $this->success(
            DeviceResource::collection($devices),
        );
    }

    public function stats(Request $request, Location $location): JsonResponse
    {
        $this->authorize("view", $location);

        $stats = $this->locationService->getStats($location);

        return $this->success($stats);
    }
}
