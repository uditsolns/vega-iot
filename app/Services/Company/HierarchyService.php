<?php

namespace App\Services\Company;

use App\Models\Area;
use App\Models\Device;
use App\Models\Hub;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class HierarchyService
{
    /**
     * Get full hierarchy tree: company -> locations -> hubs -> areas
     *
     * @param User $user
     * @return Collection
     */
    public function getTree(User $user): Collection
    {
        return Location::forUser($user)
            ->with(['hubs.areas'])
            ->withCount('hubs')
            ->get();
    }

    /**
     * Get hubs for a specific location
     *
     * @param Location $location
     * @return Collection
     */
    public function getLocationHubs(Location $location): Collection
    {
        return $location->hubs;
    }

    /**
     * Get areas for a specific hub
     *
     * @param Hub $hub
     * @return Collection
     */
    public function getHubAreas(Hub $hub): Collection
    {
        return $hub->areas;
    }

    /**
     * Search across hierarchy (locations, hubs, areas)
     *
     * @param string $query
     * @param User $user
     * @return array
     */
    public function search(string $query, User $user): array
    {
        $query = '%' . $query . '%';

        // Search locations
        $locations = Location::forUser($user)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', $query)
                    ->orWhere('address', 'like', $query)
                    ->orWhere('city', 'like', $query);
            })
            ->with('company')
            ->limit(10)
            ->get();

        // Search hubs
        $hubs = Hub::forUser($user)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', $query)
                    ->orWhere('id', 'like', $query);
            })
            ->with('location')
            ->limit(10)
            ->get();

        // Search areas
        $areas = Area::forUser($user)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', $query)
                    ->orWhere('id', 'like', $query);
            })
            ->with('hub.location')
            ->limit(10)
            ->get();

        return [
            'locations' => $locations,
            'hubs' => $hubs,
            'areas' => $areas,
        ];
    }

    /**
     * Get breadcrumb for an area
     *
     * @param Area $area
     * @return array
     */
    public function getAreaBreadcrumb(Area $area): array
    {
        $area->load('hub.location.company');

        return [
            'company' => [
                'id' => $area->hub->location->company->id ?? null,
                'name' => $area->hub->location->company->name ?? null,
            ],
            'location' => [
                'id' => $area->hub->location->id,
                'name' => $area->hub->location->name,
            ],
            'hub' => [
                'id' => $area->hub->id,
                'name' => $area->hub->name,
            ],
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
            ],
        ];
    }

    /**
     * Get breadcrumb for a device
     *
     * @param Device $device
     * @return array
     */
    public function getDeviceBreadcrumb(Device $device): array
    {
        $device->load('area.hub.location.company');

        return [
            'company' => [
                'id' => $device->area->hub->location->company->id ?? null,
                'name' => $device->area->hub->location->company->name ?? null,
            ],
            'location' => [
                'id' => $device->area->hub->location->id,
                'name' => $device->area->hub->location->name,
            ],
            'hub' => [
                'id' => $device->area->hub->id,
                'name' => $device->area->hub->name,
            ],
            'area' => [
                'id' => $device->area->id,
                'name' => $device->area->name,
            ],
            'device' => [
                'id' => $device->id,
                'code' => $device->device_code,
                'name' => $device->device_name,
            ],
        ];
    }
}
