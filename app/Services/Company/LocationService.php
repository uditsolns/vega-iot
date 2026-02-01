<?php

namespace App\Services\Company;

use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\UnauthorizedException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class LocationService
{
    /**
     * Get paginated list of locations.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Location::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::partial("city"),
                AllowedFilter::partial("state"),
                AllowedFilter::partial("country"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("company_id"),
            ])
            ->allowedSorts(["name", "city", "created_at"])
            ->allowedIncludes(["company", "hubs"])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new location.
     */
    public function create(array $data): Location
    {
        $user = Auth::user();

        if (isset($user->company_id) && ($data["company_id"] !== $user->company_id)) {
            throw new UnauthorizedException("Provided company doesn't belong to you.");
        }

        return Location::create($data);
    }

    /**
     * Update a location.
     */
    public function update(Location $location, array $data): Location
    {
        $location->update($data);

        return $location->fresh();
    }

    /**
     * Delete a location (soft delete).
     */
    public function delete(Location $location): void
    {
        $location->delete();
    }

    /**
     * Activate a location.
     */
    public function activate(Location $location): Location
    {
        $location->update(["is_active" => true]);

        // Audit log
        activity("location")
            ->event("activated")
            ->performedOn($location)
            ->withProperties(["location_id" => $location->id])
            ->log("Activated location \"{$location->name}\"");

        return $location->fresh();
    }

    /**
     * Deactivate a location.
     */
    public function deactivate(Location $location): Location
    {
        $location->update(["is_active" => false]);

        // Audit log
        activity("location")
            ->performedOn($location)
            ->event('deactivated')
            ->withProperties(["location_id" => $location->id])
            ->log("Deactivated location \"{$location->name}\"");

        return $location->fresh();
    }

    /**
     * Restore a soft-deleted location.
     */
    public function restore(Location $location): Location
    {
        $location->restore();

        // Audit log
        activity("location")
            ->event("restored")
            ->performedOn($location)
            ->withProperties(["location_id" => $location->id])
            ->log("Restored location \"{$location->name}\"");

        return $location->fresh();
    }

    /**
     * Get devices for a location.
     */
    public function getDevices(
        Location $location,
        User $user,
    ): Collection {
        return Device::whereHas("area.hub", function ($query) use (
            $location,
        ) {
            $query->where("location_id", $location->id);
        })
            ->forUser($user)
            ->with(["area", "currentConfiguration"])
            ->get();
    }

    /**
     * Get statistics for a location.
     */
    public function getStats(Location $location): array
    {
        // Get area IDs for all hubs in this location
        $areaIds = $location
            ->hubs()
            ->with("areas")
            ->get()
            ->pluck("areas")
            ->flatten()
            ->pluck("id");

        return [
            "hubs_count" => $location->hubs()->count(),
            "areas_count" => $location
                ->hubs()
                ->withCount("areas")
                ->get()
                ->sum("areas_count"),
            "devices_count" => Device::whereIn(
                "area_id",
                $areaIds,
            )->count(),
            "active_devices" => Device::whereIn("area_id", $areaIds)
                ->active()
                ->count(),
            "active_hubs" => $location
                ->hubs()
                ->active()
                ->count(),
        ];
    }
}
