<?php

namespace App\Services\Alert;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Area;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class AlertService
{

    /**
     * List alerts with filtering, sorting, and includes
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Alert::forUser($user))
            ->allowedFilters([
                AllowedFilter::exact("status"),
                AllowedFilter::exact("severity"),
                AllowedFilter::exact("type"),
                AllowedFilter::exact("device_id"),
                AllowedFilter::exact("is_back_in_range"),
                AllowedFilter::scope("active"),
                AllowedFilter::scope("acknowledged"),
                AllowedFilter::scope("open"),
                AllowedFilter::scope("closed"),
            ])
            ->allowedSorts([
                "started_at",
                "severity",
                "status",
                "acknowledged_at",
                "resolved_at",
                "duration_seconds",
            ])
            ->allowedIncludes([
                "device",
                "device.area",
                "device.area.hub.location",
                "acknowledgedBy",
                "resolvedBy",
                "notifications",
            ])
            ->defaultSort("-started_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Get alert statistics for a user
     */
    public function getStatistics(User $user, ?int $days = 7): array
    {
        $query = Alert::forUser($user);

        // Get counts by status
        $totalActive = (clone $query)
            ->where("status", AlertStatus::Active)
            ->count();
        $totalAcknowledged = (clone $query)
            ->where("status", AlertStatus::Acknowledged)
            ->count();

        // Get counts by severity (open alerts only)
        $totalCritical = (clone $query)
            ->whereIn("status", [
                AlertStatus::Active->value,
                AlertStatus::Acknowledged->value,
            ])
            ->where("severity", "critical")
            ->count();

        $totalWarning = (clone $query)
            ->whereIn("status", [
                AlertStatus::Active->value,
                AlertStatus::Acknowledged->value,
            ])
            ->where("severity", "warning")
            ->count();

        // Resolved today
        $resolvedToday = (clone $query)
            ->whereIn("status", [
                AlertStatus::Resolved->value,
                AlertStatus::AutoResolved->value,
            ])
            ->whereDate("resolved_at", Carbon::today())
            ->count();

        // Average resolution time (in hours) for last N days
        $avgResolutionTime = (clone $query)
            ->whereIn("status", [
                AlertStatus::Resolved->value,
                AlertStatus::AutoResolved->value,
            ])
            ->whereNotNull("duration_seconds")
            ->where("resolved_at", ">=", Carbon::now()->subDays($days))
            ->avg("duration_seconds");

        $avgResolutionTimeHours = $avgResolutionTime
            ? round($avgResolutionTime / 3600, 2)
            : 0;

        // Top devices by alert count (last N days)
        $topDevices = (clone $query)
            ->select("device_id", DB::raw("COUNT(*) as alert_count"))
            ->where("started_at", ">=", Carbon::now()->subDays($days))
            ->groupBy("device_id")
            ->orderByDesc("alert_count")
            ->limit(5)
            ->with("device:id,device_code,device_name")
            ->get()
            ->map(function ($alert) {
                return [
                    "device_id" => $alert->device_id,
                    "device_name" =>
                        $alert->device?->device_name ??
                        ($alert->device?->device_code ?? "Unknown"),
                    "alert_count" => $alert->alert_count,
                ];
            })
            ->toArray();

        return [
            "total_active" => $totalActive,
            "total_acknowledged" => $totalAcknowledged,
            "total_critical" => $totalCritical,
            "total_warning" => $totalWarning,
            "resolved_today" => $resolvedToday,
            "avg_resolution_time_hours" => $avgResolutionTimeHours,
            "top_devices" => $topDevices,
        ];
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(
        Alert $alert,
        User $user,
        ?string $comment = null,
    ): Alert {
        if (!$alert->acknowledge($user, $comment)) {
            throw new \InvalidArgumentException(
                "Only active alerts can be acknowledged",
            );
        }

        // Audit log
        activity("alert")
            ->performedOn($alert)
            ->event('acknowledged')
            ->withProperties([
                'device_id' => $alert->device->id,
                'alert_id' => $alert->id,
            ])
            ->log("Acknowledged {$alert->severity->value} alert for device \"{$alert->device->device_code}\"");

        // TODO: Send "alert acknowledged" notification

        return $alert->fresh();
    }

    /**
     * Resolve an alert
     */
    public function resolve(
        Alert $alert,
        User $user,
        ?string $comment = null,
    ): Alert {
        if (!$alert->resolve($user, $comment, false)) {
            throw new \InvalidArgumentException(
                "Only active or acknowledged alerts can be resolved",
            );
        }

        // Audit log
        activity("alert")
            ->performedOn($alert)
            ->event('resolved')
            ->withProperties([
                'device_id' => $alert->device->id,
                'alert_id' => $alert->id,
            ])
            ->log("Resolved {$alert->severity->value} alert for device \"{$alert->device->device_code}\"");

        // TODO: Send "alert resolved" notification

        return $alert->fresh();
    }

    /**
     * Get alert details with full relationships
     */
    public function show(Alert $alert): Alert
    {
        return $alert->load(["device.area", "acknowledgedBy", "resolvedBy"]);
    }

    /**
     * Get notifications for an alert
     */
    public function getNotifications(Alert $alert): Collection
    {
        return $alert
            ->notifications()
            ->with("user")
            ->orderBy("created_at", "desc")
            ->get();
    }

    /**
     * List alerts for devices in a specific area
     */
    public function listForArea(
        Area $area,
        array $filters,
        User $user,
    ): LengthAwarePaginator {
        $deviceIds = $area->devices()->pluck("id")->toArray();

        if (empty($deviceIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $filters["per_page"] ?? 20,
                1,
            );
        }

        return QueryBuilder::for(Alert::forUser($user))
            ->whereIn("device_id", $deviceIds)
            ->allowedFilters([
                AllowedFilter::exact("status"),
                AllowedFilter::exact("severity"),
                AllowedFilter::exact("type"),
                AllowedFilter::exact("is_back_in_range"),
                AllowedFilter::scope("active"),
                AllowedFilter::scope("acknowledged"),
                AllowedFilter::scope("open"),
                AllowedFilter::scope("closed"),
            ])
            ->allowedSorts([
                "started_at",
                "severity",
                "status",
                "acknowledged_at",
                "resolved_at",
                "duration_seconds",
            ])
            ->allowedIncludes([
                "device",
                "device.area",
                "acknowledgedBy",
                "resolvedBy",
                "notifications",
            ])
            ->defaultSort("-started_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * List alerts for devices in a specific location
     */
    public function listForLocation(
        Location $location,
        array $filters,
        User $user,
    ): LengthAwarePaginator {
        // Get all device IDs in this location: Location -> Hubs -> Areas -> Devices
        $deviceIds = $location
            ->hubs()
            ->with("areas.devices")
            ->get()
            ->pluck("areas")
            ->flatten()
            ->pluck("devices")
            ->flatten()
            ->pluck("id")
            ->unique()
            ->toArray();

        if (empty($deviceIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $filters["per_page"] ?? 20,
                1,
            );
        }

        return QueryBuilder::for(Alert::forUser($user))
            ->whereIn("device_id", $deviceIds)
            ->allowedFilters([
                AllowedFilter::exact("status"),
                AllowedFilter::exact("severity"),
                AllowedFilter::exact("type"),
                AllowedFilter::exact("is_back_in_range"),
                AllowedFilter::scope("active"),
                AllowedFilter::scope("acknowledged"),
                AllowedFilter::scope("open"),
                AllowedFilter::scope("closed"),
            ])
            ->allowedSorts([
                "started_at",
                "severity",
                "status",
                "acknowledged_at",
                "resolved_at",
                "duration_seconds",
            ])
            ->allowedIncludes([
                "device",
                "device.area",
                "device.area.hub.location",
                "acknowledgedBy",
                "resolvedBy",
                "notifications",
            ])
            ->defaultSort("-started_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Get recent alerts for a device
     */
    public function getDeviceAlerts(
        int $deviceId,
        User $user,
        int $limit = 10,
    ): Collection {
        return Alert::forUser($user)
            ->where("device_id", $deviceId)
            ->with(["acknowledgedBy", "resolvedBy"])
            ->recentFirst()
            ->limit($limit)
            ->get();
    }
}
