<?php

namespace App\Services\Alert;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\AlertAcknowledgedNotification;
use App\Notifications\AlertResolvedNotification;
use App\Traits\ResolvesAlertRecipients;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class AlertService
{
    use ResolvesAlertRecipients;

    /**
     * List alerts with filtering, sorting, and includes.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Alert::forUser($user))
            ->allowedFilters([
                AllowedFilter::exact("status"),
                AllowedFilter::exact("severity"),
                AllowedFilter::exact("type"),
                AllowedFilter::exact("device_id"),
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
            ])
            ->defaultSort("-started_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Get alert details with full relationships.
     */
    public function show(Alert $alert): Alert
    {
        return $alert->load([
            'device.area.hub.location',
            'deviceSensor.sensorType',
            'acknowledgedBy',
            'resolvedBy',
        ]);
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(Alert $alert, User $user, ?string $comment = null): Alert
    {
        if (!$alert->acknowledge($user, $comment)) {
            throw new \InvalidArgumentException('Only active alerts can be acknowledged.');
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

        $this->sendAcknowledgedNotification($alert->fresh(['device.area.hub.location', 'deviceSensor.sensorType']), $user);

        return $alert->fresh();
    }

    /**
     * Resolve an alert
     */
    public function resolve(Alert $alert, User $user, ?string $comment = null): Alert
    {
        if (!$alert->resolve($user, $comment)) {
            throw new \InvalidArgumentException('Only active or acknowledged alerts can be resolved.');
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

        $this->sendResolvedNotification($alert->fresh(['device.area.hub.location', 'deviceSensor.sensorType']), $user);

        return $alert->fresh();
    }

    // ─── Notification dispatchers ─────────────────────────────────────────────

    private function sendAcknowledgedNotification(Alert $alert, User $actingUser): void
    {
        $users = $this->getUsersToNotify($alert->device->area);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new AlertAcknowledgedNotification(
            alertId:             $alert->id,
            deviceId:            $alert->device_id,
            deviceCode:          $alert->device->device_code,
            sensorLabel:         $alert->sensor_label,
            acknowledgedBy:      $actingUser->id,
            acknowledgedByName:  trim("{$actingUser->first_name} {$actingUser->last_name}"),
            acknowledgedAt:      $alert->acknowledged_at->toDateTimeString(),
        ));
    }

    private function sendResolvedNotification(Alert $alert, User $actingUser): void
    {
        $users = $this->getUsersToNotify($alert->device->area);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new AlertResolvedNotification(
            alertId:          $alert->id,
            deviceId:         $alert->device_id,
            deviceCode:       $alert->device->device_code,
            sensorLabel:      $alert->sensor_label,
            resolvedBy:       $actingUser->id,
            resolvedByName:   trim("{$actingUser->first_name} {$actingUser->last_name}"),
            resolvedAt:       $alert->resolved_at->toDateTimeString(),
        ));
    }
}
