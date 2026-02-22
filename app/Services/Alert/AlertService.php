<?php

namespace App\Services\Alert;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Area;
use App\Models\Location;
use App\Models\User;
use App\Notifications\AlertAcknowledgedNotification;
use App\Notifications\AlertResolvedNotification;
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

        $user->notify(new AlertAcknowledgedNotification(
            alertId: $alert->id,
            deviceId: $alert->device_id,
            deviceCode: $alert->device->device_code,
            acknowledgedBy: $user->id,
            acknowledgedByName: "{$user->first_name} {$user->last_name}",
            acknowledgedAt: $alert->acknowledged_at->toDateTimeString()
        ));

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

        $user->notify(new AlertResolvedNotification(
            alertId: $alert->id,
            deviceId: $alert->device_id,
            deviceCode: $alert->device->device_code,
            resolvedBy: $user->id,
            resolvedByName: "{$user->first_name} {$user->last_name}",
            resolvedAt: $alert->resolved_at->toDateTimeString()
        ));

        return $alert->fresh();
    }

    /**
     * Get alert details with full relationships
     */
    public function show(Alert $alert): Alert
    {
        return $alert->load(["device.area", "acknowledgedBy", "resolvedBy"]);
    }
}
