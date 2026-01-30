<?php

namespace App\Services\Company;

use App\Models\Alert;
use App\Models\Area;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class AreaService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Get paginated list of areas.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
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
     *
     * @param array $data
     * @return Area
     */
    public function create(array $data): Area
    {
        $area = Area::create($data);

        // Audit log
        $this->auditService->log("area.created", Area::class, $area);

        return $area;
    }

    /**
     * Update an area.
     *
     * @param Area $area
     * @param array $data
     * @return Area
     */
    public function update(Area $area, array $data): Area
    {
        $area->update($data);

        $this->auditService->log("area.updated", Area::class, $area);

        return $area->fresh();
    }

    /**
     * Delete an area (soft delete).
     *
     * @param Area $area
     * @return void
     */
    public function delete(Area $area): void
    {
        $area->delete();

        // Audit log
        $this->auditService->log("area.deleted", Area::class, $area);
    }

    /**
     * Activate an area.
     *
     * @param Area $area
     * @return Area
     */
    public function activate(Area $area): Area
    {
        $area->update(["is_active" => true]);

        return $area->fresh();
    }

    /**
     * Deactivate an area.
     *
     * @param Area $area
     * @return Area
     */
    public function deactivate(Area $area): Area
    {
        $area->update(["is_active" => false]);

        return $area->fresh();
    }

    /**
     * Restore a soft-deleted area.
     *
     * @param Area $area
     * @return Area
     */
    public function restore(Area $area): Area
    {
        $area->restore();

        return $area->fresh();
    }

    /**
     * Update alert configuration for an area.
     *
     * @param Area $area
     * @param array $data
     * @return Area
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
        $this->auditService->log("area.alert_config_updated", Area::class, $area);

        return $area->fresh();
    }

    /**
     * Copy alert configuration from source area to target areas.
     *
     * @param Area $sourceArea
     * @param array $targetAreaIds
     * @return array
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
                $failed[] = [
                    "id" => $areaId,
                    "reason" => "Area not found",
                ];
                continue;
            }

            $targetArea->update($alertConfig);
            $updated[] = $targetArea->id;
        }

        return [
            "updated" => $updated,
            "failed" => $failed,
        ];
    }

    /**
     * Get devices for an area.
     *
     * @param Area $area
     * @param User $user
     * @return Collection
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
     *
     * @param Area $area
     * @return array
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
