<?php

namespace App\Services\Company;

use App\Models\Device;
use App\Models\Hub;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class HubService
{
    public function __construct(private AuditService $auditService) {}
    /**
     * Get paginated list of hubs.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
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
     *
     * @param array $data
     * @return Hub
     */
    public function create(array $data): Hub
    {
        $hub = Hub::create($data);

        // Audit log
        $this->auditService->log("hub.created", Hub::class, $hub);

        return $hub;
    }

    /**
     * Update a hub.
     *
     * @param Hub $hub
     * @param array $data
     * @return Hub
     */
    public function update(Hub $hub, array $data): Hub
    {
        $hub->update($data);

        // Audit log
        $this->auditService->log("hub.updated", Hub::class, $hub);

        return $hub->fresh();
    }

    /**
     * Delete a hub (soft delete).
     *
     * @param Hub $hub
     * @return void
     */
    public function delete(Hub $hub): void
    {
        $hub->delete();

        // Audit log
        $this->auditService->log("hub.deleted", Hub::class, $hub);
    }

    /**
     * Activate a hub.
     *
     * @param Hub $hub
     * @return Hub
     */
    public function activate(Hub $hub): Hub
    {
        $hub->update(["is_active" => true]);

        // Audit log
        $this->auditService->log("hub.activated", Hub::class, $hub);

        return $hub->fresh();
    }

    /**
     * Deactivate a hub.
     *
     * @param Hub $hub
     * @return Hub
     */
    public function deactivate(Hub $hub): Hub
    {
        $hub->update(["is_active" => false]);

        // Audit log
        $this->auditService->log("hub.deactivated", Hub::class, $hub);

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
        $this->auditService->log("hub.restored", Hub::class, $hub);

        return $hub->fresh();
    }

    /**
     * Get devices for a hub.
     *
     * @param Hub $hub
     * @param User $user
     * @return Collection
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
     *
     * @param Hub $hub
     * @return array
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
