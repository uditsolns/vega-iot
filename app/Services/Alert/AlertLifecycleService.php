<?php

namespace App\Services\Alert;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class AlertLifecycleService
{
    public function __construct(
        private ThresholdEvaluationService $thresholdService,
        private NotificationService $notificationService,
    ) {}

    /**
     * Evaluate a reading and process alerts
     *
     * @param Device $device
     * @param DeviceReading $reading
     */
    public function evaluateAndProcess(
        Device $device,
        DeviceReading $reading,
    ): void {
        try {
            // Load device configuration
            $device->load("currentConfiguration", "area");

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

            // TODO: update the logic
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
            $this->notificationService->notifyForAlert($alert, "triggered");
        } elseif ($existingAlert->status === AlertStatus::Active) {
            // Active alert still violated - update and notify
            $this->updateAlert($existingAlert, $violation);
            $this->notificationService->notifyForAlert(
                $existingAlert,
                "triggered",
            );
        } elseif ($existingAlert->status === AlertStatus::Acknowledged) {
            // Acknowledged alert still violated - update and notify based on interval
            $this->updateAlert($existingAlert, $violation);

            if ($this->shouldSendNotification($existingAlert, $device->area)) {
                $this->notificationService->notifyForAlert(
                    $existingAlert,
                    "triggered",
                );
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
                $this->notificationService->notifyForAlert(
                    $existingAlert,
                    "back_in_range",
                );
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
     * Acknowledge an alert
     *
     * @param Alert $alert
     * @param User $user
     * @param string|null $comment
     * @return bool
     * @throws Throwable
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
            $this->notificationService->notifyForAlert($alert, "acknowledged");
        });

        Log::info("Alert acknowledged", [
            "alert_id" => $alert->id,
            "user_id" => $user->id,
        ]);

        return true;
    }

    /**
     * Manually resolve an alert
     *
     * @param Alert $alert
     * @param User $user
     * @param string|null $comment
     * @return bool
     * @throws Throwable
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
            $this->notificationService->notifyForAlert($alert, "resolved");
        });

        Log::info("Alert manually resolved", [
            "alert_id" => $alert->id,
            "user_id" => $user->id,
        ]);

        return true;
    }
}
