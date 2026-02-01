<?php

namespace App\Services\User;

use App\Models\Area;
use App\Models\Hub;
use App\Models\Location;
use App\Models\User;
use App\Models\UserAreaAccess;
use Illuminate\Support\Facades\Auth;

class AreaAccessService
{
    /**
     * List areas that a user has access to
     */
    public function listAreas(User $user): array
    {
        $user->load("areaAccess");

        $areas = Area::whereIn(
            "id",
            $user->areaAccess->pluck("area_id"),
        )
            ->with("hub.location")
            ->get();

        return [
            "areas" => $areas,
            "has_restrictions" => $user->areaAccess->count() > 0,
            "area_count" => $user->areaAccess->count(),
        ];
    }

    /**
     * Grant area access to a user.
     */
    public function grantAccess(
        User $user,
        int $areaId,
    ): void {
        UserAreaAccess::firstOrCreate(
            [
                "user_id" => $user->id,
                "area_id" => $areaId,
            ],
            [
                "granted_by" => Auth::user()->id,
            ],
        );

        $area = Area::find($areaId);

        activity("user")
            ->event("granted_area")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "area_id" => $area->id,
            ])
            ->log("Granted area \"{$area->name}\" to user \"{$user->email}\"");
    }

    /**
     * Revoke area access from a user.
     */
    public function revokeAccess(User $user, int $areaId): void
    {
        UserAreaAccess::where("user_id", $user->id)
            ->where("area_id", $areaId)
            ->delete();

        $area = Area::find($areaId);

        activity("user")
            ->event("revoked_area")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "area_id" => $area->id,
            ])
            ->log("Revoked area \"{$area->name}\" from user \"{$user->email}\"");
    }

    /**
     * Grant access to all areas in a location.
     */
    public function grantByLocation(
        User $user,
        int $locationId
    ): array {
        $location = Location::find($locationId);
        $areaIds = Area::whereHas("hub", function ($query) use ($locationId) {
            $query->where("location_id", $locationId);
        })->pluck("id")->toArray();

        foreach ($areaIds as $areaId) {
            UserAreaAccess::firstOrCreate(
                ['user_id' => $user->id, 'area_id' => $areaId],
                ['granted_by' => Auth::user()->id],
            );
        }

        activity("user")
            ->event("granted_areas_by_location")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "location_id" => $location->id,
                "area_ids" => $areaIds,
                "area_count" => count($areaIds),
            ])
            ->log("Granted all areas of location \"{$location->name}\" to user \"{$user->email}\"");


        return [
            "granted_count" => count($areaIds),
            "area_ids" => $areaIds,
        ];
    }

    /**
     * Grant access to all areas in a hub.
     */
    public function grantByHub(
        User $user,
        int $hubId,
    ): array {
        $hub = Hub::find($hubId);
        $areaIds = Area::where("hub_id", $hubId)
            ->pluck("id")
            ->toArray();

        foreach ($areaIds as $areaId) {
            UserAreaAccess::firstOrCreate(
                ['user_id' => $user->id, 'area_id' => $areaId],
                ['granted_by' => Auth::id()],
            );
        }

        activity("user")
            ->event("granted_areas_by_hub")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "hub_id" => $hub->id,
                "area_ids" => $areaIds,
                "area_count" => count($areaIds),
            ])
            ->log("Granted all areas of hub \"{$hub->name}\" to user \"{$user->email}\"");

        return [
            "granted_count" => count($areaIds),
            "area_ids" => $areaIds,
        ];
    }

    /**
     * Clear all area access for a user.
     */
    public function clearAll(User $user): void
    {
        UserAreaAccess::where("user_id", $user->id)->delete();

        activity("user")
            ->event("revoked_all_areas")
            ->performedOn($user)
            ->withProperties(['user_id' => $user->id])
            ->log("Revoked all area access from user \"{$user->email}\"");
    }
}
