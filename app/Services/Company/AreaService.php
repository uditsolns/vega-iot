<?php

namespace App\Services\Company;

use App\Models\Alert;
use App\Models\Area;
use App\Models\Hub;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class AreaService
{

    /**
     * Get paginated list of areas.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Area::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::partial("description"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("hub_id"),
            ])
            ->allowedSorts(["name", "created_at"])
            ->allowedIncludes(["hub"])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new area.
     */
    public function create(array $data): Area
    {
        $hub = Hub::with('location')->findOrFail($data['hub_id']);
        $user = Auth::user();

        if (isset($user->company_id) && ($hub->location->company_id != $user->company_id)) {
            throw new UnauthorizedException("Provided hub doesn't belong to you.");
        }

        return Area::create($data);
    }

    /**
     * Update an area.
     */
    public function update(Area $area, array $data): Area
    {
        $area->update($data);

        return $area->fresh();
    }

    /**
     * Delete an area (soft delete).
     */
    public function delete(Area $area): void
    {
        $area->delete();
    }

    /**
     * Activate an area.
     */
    public function activate(Area $area): Area
    {
        $area->update(["is_active" => true]);

        activity('area')
            ->performedOn($area)
            ->event('activated')
            ->withProperties(['area_id' => $area->id])
            ->log("Activated area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Deactivate an area.
     */
    public function deactivate(Area $area): Area
    {
        $area->update(["is_active" => false]);

        activity('area')
            ->performedOn($area)
            ->event('deactivated')
            ->withProperties(['area_id' => $area->id])
            ->log("Deactivated area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Restore a soft-deleted area.
     */
    public function restore(Area $area): Area
    {
        $area->restore();

        activity('area')
            ->performedOn($area)
            ->event('restored')
            ->withProperties(['area_id' => $area->id])
            ->log("Restored area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Update alert configuration for an area.
     */
    public function updateAlertConfig(Area $area, array $data): Area
    {
        $alertFields = [
            "alert_email_enabled",
            "alert_sms_enabled",
            "alert_voice_enabled",
            "alert_push_enabled",
            "alert_warning_enabled",
            "alert_critical_enabled",
            "alert_back_in_range_enabled",
            "alert_device_status_enabled",
            "acknowledged_alert_notification_interval",
        ];

        $alertData = array_intersect_key($data, array_flip($alertFields));

        $area->update($alertData);

        // Audit log
        // TODO:  include updated context
        activity('area')
            ->event('alert_configuration_updated')
            ->performedOn($area)
            ->withProperties(['area_id' => $area->id])
            ->log("Updated alert configuration for area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Copy alert configuration from source area to target areas.
     */
    public function copyAlertConfig(
        Area $sourceArea,
        array $targetAreaIds,
    ): array {
        $alertConfig = [
            "alert_email_enabled" => $sourceArea->alert_email_enabled,
            "alert_sms_enabled" => $sourceArea->alert_sms_enabled,
            "alert_voice_enabled" => $sourceArea->alert_voice_enabled,
            "alert_push_enabled" => $sourceArea->alert_push_enabled,
            "alert_warning_enabled" => $sourceArea->alert_warning_enabled,
            "alert_critical_enabled" => $sourceArea->alert_critical_enabled,
            "alert_back_in_range_enabled" =>
                $sourceArea->alert_back_in_range_enabled,
            "alert_device_status_enabled" =>
                $sourceArea->alert_device_status_enabled,
            "acknowledged_alert_notification_interval" =>
                $sourceArea->acknowledged_alert_notification_interval,
        ];

        $updated = [];
        $failed = [];

        foreach ($targetAreaIds as $areaId) {
            $targetArea = Area::find($areaId);

            if (!$targetArea) {
                $failed[] = ['id' => $areaId, 'reason' => "Area not found"];
                continue;
            }

            $targetArea->update($alertConfig);

            activity('area')
                ->event('alert_configuration_copied')
                ->performedOn($targetArea)
                ->withProperties([
                    'source_area_id' => $sourceArea->id,
                    'target_area_id' => $targetArea->id,
                ])
                ->log("Copied alert configuration from \"{$sourceArea->name}\" to \"{$targetArea->name}\"");

            $updated[] = $targetArea->id;
        }

        return [
            "updated" => $updated,
            "failed" => $failed,
        ];
    }

    /**
     * Get devices for an area.
     */
    public function getDevices(
        Area $area,
        User $user,
    ): Collection {
        return $area
            ->devices()
            ->forUser($user)
            ->with(["area", "currentConfiguration"])
            ->get();
    }

    /**
     * Get statistics for an area.
     */
    public function getStats(Area $area): array
    {
        // Get device IDs in this area
        $deviceIds = $area->devices()->pluck("id");

        return [
            "devices_count" => $area->devices()->count(),
            "active_devices" => $area
                ->devices()
                ->where("is_active", true)
                ->count(),
            "alerts_count" => Alert::whereIn(
                "device_id",
                $deviceIds,
            )->count(),
            "active_alerts" => Alert::whereIn(
                "device_id",
                $deviceIds,
            )
                ->active()
                ->count(),
            "open_alerts" => Alert::whereIn("device_id", $deviceIds)
                ->open()
                ->count(),
        ];
    }
}
