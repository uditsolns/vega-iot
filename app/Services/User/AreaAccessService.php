<?php

namespace App\Services\User;

use App\Models\Area;
use App\Models\User;
use App\Models\UserAreaAccess;
use Exception;
use Illuminate\Support\Facades\Auth;

class AreaAccessService
{
    /**
     * List areas that a user has access to
     *
     * @param User $user
     * @return array
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
     *
     * @param User $user
     * @param int $areaId
     * @return void
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
    }

    /**
     * Revoke area access from a user.
     *
     * @param User $user
     * @param int $areaId
     * @return void
     */
    public function revokeAccess(User $user, int $areaId): void
    {
        UserAreaAccess::where("user_id", $user->id)
            ->where("area_id", $areaId)
            ->delete();
    }

    /**
     * Grant access to all areas in a location.
     *
     * @param User $user
     * @param int $locationId
     * @return array
     */
    public function grantByLocation(
        User $user,
        int $locationId
    ): array {
        $areaIds = Area::whereHas("hub", function ($query) use (
            $locationId,
        ) {
            $query->where("location_id", $locationId);
        })
            ->pluck("id")
            ->toArray();

        foreach ($areaIds as $areaId) {
            $this->grantAccess($user, $areaId);
        }

        return [
            "granted_count" => count($areaIds),
            "area_ids" => $areaIds,
        ];
    }

    /**
     * Grant access to all areas in a hub.
     *
     * @param User $user
     * @param int $hubId
     * @return array
     */
    public function grantByHub(
        User $user,
        int $hubId,
    ): array {
        $areaIds = Area::where("hub_id", $hubId)
            ->pluck("id")
            ->toArray();

        foreach ($areaIds as $areaId) {
            $this->grantAccess($user, $areaId);
        }

        return [
            "granted_count" => count($areaIds),
            "area_ids" => $areaIds,
        ];
    }

    /**
     * Clear all area access for a user.
     *
     * @param User $user
     * @return void
     */
    public function clearAll(User $user): void
    {
        UserAreaAccess::where("user_id", $user->id)->delete();
    }
}
