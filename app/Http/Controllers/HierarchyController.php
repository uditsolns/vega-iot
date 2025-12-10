<?php

namespace App\Http\Controllers;

use App\Http\Resources\AreaResource;
use App\Http\Resources\HierarchyTreeResource;
use App\Http\Resources\HubResource;
use App\Http\Resources\LocationResource;
use App\Models\Area;
use App\Models\Device;
use App\Models\Hub;
use App\Models\Location;
use App\Services\Company\HierarchyService;
use App\Services\Company\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HierarchyController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly HierarchyService $hierarchyService,
    ) {}

    /**
     * Get full hierarchy tree: company -> locations -> hubs -> areas
     */
    public function tree(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Location::class);

        $locations = $this->hierarchyService->getTree($request->user());

        return $this->success(HierarchyTreeResource::collection($locations));
    }

    /**
     * List locations with optional filtering
     */
    public function locations(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Location::class);

        $locations = $this->locationService->list(
            $request->all(),
            $request->user(),
        );

        return $this->collection(LocationResource::collection($locations));
    }

    /**
     * Get hubs for a specific location
     */
    public function locationHubs(
        Request $request,
        Location $location,
    ): JsonResponse {
        $this->authorize("view", $location);

        $hubs = $this->hierarchyService->getLocationHubs($location);

        return $this->success(HubResource::collection($hubs));
    }

    /**
     * Get areas for a specific hub
     */
    public function hubAreas(Request $request, Hub $hub): JsonResponse
    {
        $this->authorize("view", $hub);

        $areas = $this->hierarchyService->getHubAreas($hub);

        return $this->success(AreaResource::collection($areas));
    }

    /**
     * Get breadcrumb trail for an area: location > hub > area
     */
    public function breadcrumbArea(Request $request, Area $area): JsonResponse
    {
        $this->authorize("view", $area);

        $breadcrumb = $this->hierarchyService->getAreaBreadcrumb($area);

        return $this->success($breadcrumb);
    }

    /**
     * Get breadcrumb trail for a device: location > hub > area > device
     */
    public function breadcrumbDevice(
        Request $request,
        Device $device,
    ): JsonResponse {
        $this->authorize("view", $device);

        $breadcrumb = $this->hierarchyService->getDeviceBreadcrumb($device);

        return $this->success($breadcrumb);
    }

    /**
     * Search across hierarchy (locations, hubs, areas)
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Location::class);

//        $request->validate([
//            "query" => ["required", "string", "min:2"],
//        ]);

//        $query = $request->input("query");
//        $results = $this->hierarchyService->search($query, $request->user());
//
//        return $this->success([
//            "locations" => LocationResource::collection($results["locations"]),
//            "hubs" => HubResource::collection($results["hubs"]),
//            "areas" => AreaResource::collection($results["areas"]),
//        ]);

        return $this->error("TODO: pending feature", 501);
    }
}
