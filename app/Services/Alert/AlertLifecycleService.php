<?php

namespace App\Services\Alert;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Models\User;
use App\Notifications\AlertAcknowledgedNotification;
use App\Notifications\AlertBackInRangeNotification;
use App\Notifications\AlertResolvedNotification;
use App\Notifications\AlertTriggeredNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

readonly class AlertLifecycleService
{
    public function __construct(
        private ThresholdEvaluationService $thresholdService,
    ) {}

    /**
     * Evaluate a reading and process alerts
     */
    public function evaluateAndProcess(
        Device $device,
        DeviceReading $reading,
    ): void {
        try {
            if (!$device->currentConfiguration) {
                Log::warning(
                    "Device has no configuration, skipping alert evaluation",
                    [
                        "device_id" => $device->id,
                    ],
                );
                return;
            }

            // Evaluate thresholds
            $violations = $this->thresholdService->evaluate(
                $reading,
                $device->currentConfiguration,
            );

            Log::debug("Violations: ", $violations);

            // Check for existing active or acknowledged alerts
            $existingAlert = Alert::where('device_id', $device->id)
                ->whereIn('status', [
                    AlertStatus::Active->value,
                    AlertStatus::Acknowledged->value,
                ])
                ->first();

            // Process based on violations and existing alerts
            if (!empty($violations)) {
                $this->handleViolations($device, $violations, $existingAlert);
            } else {
                $this->handleBackInRange($device, $existingAlert);
            }
        } catch (Exception $e) {
            Log::error("Alert evaluation failed", [
                "device_id" => $device->id,
                "reading_id" => $reading->recorded_at,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle threshold violations
     */
    private function handleViolations(
        Device $device,
        array $violations,
        ?Alert $existingAlert,
    ): void {
        // Take the first (highest severity) violation
        $violation = $violations[0];

        if (!$existingAlert) {
            // No existing alert - create new alert and notify
            $alert = $this->createAlert($device, $violation);
            $this->sendAlertTriggeredNotification($alert);
        } elseif ($existingAlert->status === AlertStatus::Active) {
            // Active alert still violated - update and notify
            $this->updateAlert($existingAlert, $violation);
            $this->sendAlertTriggeredNotification($existingAlert);
        } elseif ($existingAlert->status === AlertStatus::Acknowledged) {
            // Acknowledged alert still violated - update and notify based on interval
            $this->updateAlert($existingAlert, $violation);

            if ($this->shouldSendNotification($existingAlert, $device->area)) {
                $this->sendAlertTriggeredNotification($existingAlert);
            }
        }
    }

    /**
     * Handle sensor returning to normal range
     */
    private function handleBackInRange(
        Device $device,
        ?Alert $existingAlert,
    ): void {
        if ($existingAlert) {
            // Auto-resolve the alert
            $existingAlert->autoResolve();

            // Send notification if enabled
            if ($device->area && $device->area->alert_back_in_range_enabled) {
                $this->sendBackInRangeNotification($existingAlert);
            }

            Log::info("Alert auto-resolved - sensor back in range", [
                "alert_id" => $existingAlert->id,
                "device_id" => $device->id,
            ]);
        }
    }

    /**
     * Create a new alert
     */
    private function createAlert(Device $device, array $violation): Alert
    {
        $alert = Alert::create([
            "device_id" => $device->id,
            "type" => $violation["type"],
            "severity" => $violation["severity"],
            "status" => AlertStatus::Active->value,
            "trigger_value" => $violation["value"],
            "threshold_breached" => $violation["threshold_breached"],
            "reason" => $violation["reason"],
            "started_at" => now(),
            "notification_count" => 0,
        ]);

        Log::info("Alert created", [
            "alert_id" => $alert->id,
            "device_id" => $device->id,
            "type" => $violation["type"],
            "severity" => $violation["severity"],
        ]);

        return $alert;
    }

    /**
     * Update an existing alert with new violation data
     */
    private function updateAlert(Alert $alert, array $violation): void
    {
        $alert->update([
            "trigger_value" => $violation["value"],
            "threshold_breached" => $violation["threshold_breached"],
            "reason" => $violation["reason"],
        ]);
    }

    /**
     * Determine if notification should be sent for acknowledged alert
     */
    private function shouldSendNotification(Alert $alert, $area): bool
    {
        // Always send for active alerts
        if ($alert->status === AlertStatus::Active) {
            return true;
        }

        // For acknowledged alerts, check interval
        if ($alert->status === AlertStatus::Acknowledged) {
            if (!$alert->last_notification_at) {
                return true;
            }

            $intervalHours =
                $area->acknowledged_alert_notification_interval ?? 24;
            $hoursSinceLastNotification = $alert->last_notification_at->diffInHours(
                now(),
            );

            return $hoursSinceLastNotification >= $intervalHours;
        }

        return false;
    }

    /**
     * Send alert triggered notification
     */
    private function sendAlertTriggeredNotification(Alert $alert): void
    {
        try {
            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                Log::warning('No users to notify for alert', [
                    'alert_id' => $alert->id,
                    'device_id' => $alert->device_id,
                ]);
                return;
            }

            // Create notification with primitive data only
            $notification = new AlertTriggeredNotification(
                alertId: $alert->id,
                deviceId: $alert->device_id,
                severity: $alert->severity->value,
                sensorType: $alert->type->value,
                triggerValue: (float) $alert->trigger_value,
                reason: $alert->reason,
                startedAt: $alert->started_at->toDateTimeString()
            );

            // Send to all users
            Notification::send($users, $notification);

            // Update alert notification tracking
            $alert->incrementNotificationCount();

            Log::info('Alert notifications sent', [
                'alert_id' => $alert->id,
                'users_count' => $users->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send alert notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send back in range notification
     */
    private function sendBackInRangeNotification(Alert $alert): void
    {
        try {
            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                return;
            }

            $notification = new AlertBackInRangeNotification(
                alertId: $alert->id,
                deviceId: $alert->device_id,
                deviceCode: $alert->device->device_code,
                currentValue: (float) $alert->trigger_value,
                sensorType: $alert->type->value
            );

            Notification::send($users, $notification);

            Log::info('Back in range notifications sent', [
                'alert_id' => $alert->id,
                'users_count' => $users->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send back in range notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get users who should be notified about this area's alerts
     */
    private function getUsersToNotify($area)
    {
        if (!$area) {
            return collect();
        }

        // Get all active users who have access to this area
        return User::query()
            ->where("is_active", true)
            ->where(function ($query) use ($area) {
                // Users with no area restrictions in the same company
                $query
                    ->whereHas("company", function ($q) use ($area) {
                        $q->where("id", $area->hub->location->company_id);
                    })
                    ->whereDoesntHave("areaAccess")

                    // OR users with explicit access to this area
                    ->orWhereHas("areaAccess", function ($q) use ($area) {
                        $q->where("area_id", $area->id);
                    });
            })
            ->get();
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(
        Alert $alert,
        User $user,
        ?string $comment = null,
    ): bool {
        if ($alert->status !== AlertStatus::Active) {
            return false;
        }

        DB::transaction(function () use ($alert, $user, $comment) {
            $alert->acknowledge($user, $comment);

            // Send notification
            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isNotEmpty()) {
                $notification = new AlertAcknowledgedNotification(
                    alertId: $alert->id,
                    deviceId: $alert->device_id,
                    deviceCode: $alert->device->device_code,
                    acknowledgedBy: $user->id,
                    acknowledgedByName: "{$user->first_name} {$user->last_name}",
                    acknowledgedAt: $alert->acknowledged_at->toDateTimeString()
                );

                Notification::send($users, $notification);
            }
        });

        Log::info("Alert acknowledged", [
            "alert_id" => $alert->id,
            "user_id" => $user->id,
        ]);

        return true;
    }

    /**
     * Manually resolve an alert
     */
    public function resolve(
        Alert $alert,
        User $user,
        ?string $comment = null,
    ): bool {
        if (
            !in_array($alert->status, [
                AlertStatus::Active,
                AlertStatus::Acknowledged,
            ])
        ) {
            return false;
        }

        DB::transaction(function () use ($alert, $user, $comment) {
            $alert->resolve($user, $comment, false);

            // Send notification
            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isNotEmpty()) {
                $notification = new AlertResolvedNotification(
                    alertId: $alert->id,
                    deviceId: $alert->device_id,
                    deviceCode: $alert->device->device_code,
                    resolvedBy: $user->id,
                    resolvedByName: "{$user->first_name} {$user->last_name}",
                    resolvedAt: $alert->resolved_at->toDateTimeString()
                );

                Notification::send($users, $notification);
            }
        });

        Log::info("Alert manually resolved", [
            "alert_id" => $alert->id,
            "user_id" => $user->id,
        ]);

        return true;
    }
}
