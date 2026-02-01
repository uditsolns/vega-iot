<?php

namespace App\Services\Company;

use App\Models\Device;
use App\Models\Hub;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class HubService
{
    /**
     * Get paginated list of hubs.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Hub::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::partial("description"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("location_id"),
            ])
            ->allowedSorts(["name", "created_at"])
            ->allowedIncludes(["location"])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new hub.
     */
    public function create(array $data): Hub
    {
        $location = Location::find($data['location_id']);
        $user = Auth::user();

        if (isset($user->company_id) && ($location->company_id != $user->company_id)) {
            throw new UnauthorizedException("Provided location doesn't belong to you.");
        }

        return Hub::create($data);
    }

    /**
     * Update a hub.
     */
    public function update(Hub $hub, array $data): Hub
    {
        $hub->update($data);

        return $hub->fresh();
    }

    /**
     * Delete a hub (soft delete).
     */
    public function delete(Hub $hub): void
    {
        $hub->delete();
    }

    /**
     * Activate a hub.
     */
    public function activate(Hub $hub): Hub
    {
        $hub->update(["is_active" => true]);

        // Audit log
        activity('hub')
            ->event('activated')
            ->performedOn($hub)
            ->withProperties(['hub_id' => $hub->id])
            ->log("Activated hub \"{$hub->name}\"");

        return $hub->fresh();
    }

    /**
     * Deactivate a hub.
     */
    public function deactivate(Hub $hub): Hub
    {
        $hub->update(["is_active" => false]);

        // Audit log
        activity('hub')
            ->event('deactivated')
            ->performedOn($hub)
            ->withProperties(['hub_id' => $hub->id])
            ->log("Deactivated hub \"{$hub->name}\"");

        return $hub->fresh();
    }

    /**
     * Restore a soft-deleted hub.
     *
     * @param Hub $hub
     * @return Hub
     */
    public function restore(Hub $hub): Hub
    {
        $hub->restore();

        // Audit log
        activity('hub')
            ->event('restored')
            ->performedOn($hub)
            ->withProperties(['hub_id' => $hub->id])
            ->log("Restored hub \"{$hub->name}\"");

        return $hub->fresh();
    }

    /**
     * Get devices for a hub.
     */
    public function getDevices(
        Hub $hub,
        User $user,
    ): Collection {
        return Device::whereHas("area", function ($query) use (
            $hub,
        ) {
            $query->where("hub_id", $hub->id);
        })
            ->forUser($user)
            ->with(["area", "currentConfiguration"])
            ->get();
    }

    /**
     * Get statistics for a hub.
     */
    public function getStats(Hub $hub): array
    {
        // Get area IDs for this hub
        $areaIds = $hub->areas()->pluck("id");

        return [
            "areas_count" => $hub->areas()->count(),
            "devices_count" => Device::whereIn("area_id", $areaIds)->count(),
            "active_devices" => Device::whereIn("area_id", $areaIds)->active()->count(),
            "active_areas" => $hub->areas()->active()->count(),
        ];
    }
}
