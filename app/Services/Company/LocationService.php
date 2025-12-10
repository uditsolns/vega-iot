<?php

namespace App\Services\Company;

use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LocationService
{
    /**
     * Get paginated list of locations.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
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
     *
     * @param array $data
     * @return Location
     */
    public function create(array $data): Location
    {
        return Location::create($data);
    }

    /**
     * Update a location.
     *
     * @param Location $location
     * @param array $data
     * @return Location
     */
    public function update(Location $location, array $data): Location
    {
        $location->update($data);

        return $location->fresh();
    }

    /**
     * Delete a location (soft delete).
     *
     * @param Location $location
     * @return void
     */
    public function delete(Location $location): void
    {
        $location->delete();
    }

    /**
     * Activate a location.
     *
     * @param Location $location
     * @return Location
     */
    public function activate(Location $location): Location
    {
        $location->update(["is_active" => true]);

        return $location->fresh();
    }

    /**
     * Deactivate a location.
     *
     * @param Location $location
     * @return Location
     */
    public function deactivate(Location $location): Location
    {
        $location->update(["is_active" => false]);

        return $location->fresh();
    }

    /**
     * Restore a soft-deleted location.
     *
     * @param Location $location
     * @return Location
     */
    public function restore(Location $location): Location
    {
        $location->restore();

        return $location->fresh();
    }

    /**
     * Get devices for a location.
     *
     * @param Location $location
     * @param User $user
     * @return Collection
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
     *
     * @param Location $location
     * @return array
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
