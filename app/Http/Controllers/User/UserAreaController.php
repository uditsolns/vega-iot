<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Models\User;
use App\Services\User\AreaAccessService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAreaController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AreaAccessService $areaAccessService,
    ) {}

    public function index(Request $request, User $user): JsonResponse
    {
        $this->authorize("assignAreas", $user);

        $result = $this->areaAccessService->listAreas($user);

        return $this->success([
            "areas" => AreaResource::collection($result["areas"]),
            "has_restrictions" => $result["has_restrictions"],
            "area_count" => $result["area_count"],
        ]);
    }

    public function grantAreas(Request $request, User $user): JsonResponse
    {
        $this->authorize("assignAreas", $user);

        $validated = $request->validate([
            "area_ids" => ["required", "array", "min:1"],
            "area_ids.*" => ["required", "integer", "exists:areas,id"],
        ]);

        foreach ($validated["area_ids"] as $areaId) {
            $this->areaAccessService->grantAccess($user, $areaId);
        }

        return $this->success(null, "Area access granted successfully");
    }

    public function revokeArea(
        Request $request,
        User $user,
        Area $area,
    ): JsonResponse {
        $this->authorize("assignAreas", $user);

        $this->areaAccessService->revokeAccess($user, $area->id);

        return $this->success(null, "Area access revoked successfully");
    }

    public function grantAreasByLocation(
        Request $request,
        User $user,
    ): JsonResponse {
        $this->authorize("assignAreas", $user);

        $validated = $request->validate([
            "location_id" => ["required", "integer", "exists:locations,id"],
        ]);

        $result = $this->areaAccessService->grantByLocation($user, $validated["location_id"]);

        return $this->success(
            $result,
            "Granted access to {$result["granted_count"]} areas in location",
        );
    }

    public function grantAreasByHub(Request $request, User $user): JsonResponse
    {
        $this->authorize("assignAreas", $user);

        $validated = $request->validate([
            "hub_id" => ["required", "integer", "exists:hubs,id"],
        ]);

        $result = $this->areaAccessService->grantByHub($user, $validated["hub_id"]);

        return $this->success(
            $result,
            "Granted access to {$result["granted_count"]} areas in hub",
        );
    }

    public function clearAreas(Request $request, User $user): JsonResponse
    {
        $this->authorize("assignAreas", $user);

        $this->areaAccessService->clearAll($user);

        return $this->success(null, "All area access cleared successfully");
    }
}
