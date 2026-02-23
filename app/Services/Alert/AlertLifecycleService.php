<?php

namespace App\Services\Alert;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\DeviceSensor;
use App\Models\User;
use App\Notifications\AlertBackInRangeNotification;
use App\Notifications\AlertTriggeredNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

readonly class AlertLifecycleService
{
    /**
     * Evaluate a single sensor reading against its configured thresholds.
     */
    public function evaluateSensor(
        DeviceSensor    $sensor,
        ?float          $value,
        string|Carbon   $recordedAt
    ): void {
        if ($value === null) {
            return;
        }

        $recordedAt = $recordedAt instanceof Carbon
            ? $recordedAt
            : Carbon::parse($recordedAt);

        try {
            $config = $sensor->currentConfiguration;

            if (!$config) {
                Log::debug('No threshold config for sensor – skipping alert evaluation', [
                    'sensor_id' => $sensor->id,
                    'device_id' => $sensor->device_id,
                ]);
                return;
            }

            $violation = $this->evaluateThreshold($value, $config);

            $existingAlert = Alert::where('device_id', $sensor->device_id)
                ->where('device_sensor_id', $sensor->id)
                ->whereIn('status', [AlertStatus::Active->value, AlertStatus::Acknowledged->value])
                ->first();

            if ($violation) {
                $this->handleViolation($sensor, $value, $violation, $existingAlert, $recordedAt);
            } else {
                $this->handleBackInRange($sensor, $existingAlert);
            }
        } catch (Exception $e) {
            Log::error('Alert evaluation failed', [
                'sensor_id' => $sensor->id,
                'device_id' => $sensor->device_id,
                'value'     => $value,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // ─── Threshold evaluation ─────────────────────────────────────────────────

    /**
     * Compare value against thresholds.
     *
     * Returns:
     * [
     *   'severity'           => AlertSeverity,
     *   'threshold_key'      => string,   // e.g. 'max_critical'
     *   'threshold_value'    => float,
     *   'reason'             => string,
     * ]
     * or null when within acceptable range.
     */
    private function evaluateThreshold(float $value, $config): ?array
    {
        if ($config->max_critical !== null && $value > (float) $config->max_critical) {
            return [
                'severity'        => AlertSeverity::Critical,
                'threshold_key'   => 'max_critical',
                'threshold_value' => (float) $config->max_critical,
                'reason'          => "Value {$value} exceeded critical maximum {$config->max_critical}",
            ];
        }

        if ($config->min_critical !== null && $value < (float) $config->min_critical) {
            return [
                'severity'        => AlertSeverity::Critical,
                'threshold_key'   => 'min_critical',
                'threshold_value' => (float) $config->min_critical,
                'reason'          => "Value {$value} below critical minimum {$config->min_critical}",
            ];
        }

        if ($config->max_warning !== null && $value > (float) $config->max_warning) {
            return [
                'severity'        => AlertSeverity::Warning,
                'threshold_key'   => 'max_warning',
                'threshold_value' => (float) $config->max_warning,
                'reason'          => "Value {$value} exceeded warning maximum {$config->max_warning}",
            ];
        }

        if ($config->min_warning !== null && $value < (float) $config->min_warning) {
            return [
                'severity'        => AlertSeverity::Warning,
                'threshold_key'   => 'min_warning',
                'threshold_value' => (float) $config->min_warning,
                'reason'          => "Value {$value} below warning minimum {$config->min_warning}",
            ];
        }

        return null;
    }

    // ─── Violation handling ───────────────────────────────────────────────────

    /**
     * @param float       $actualValue  The real sensor reading that triggered the alert
     * @param array       $violation    Output of evaluateThreshold()
     * @param Alert|null  $existingAlert
     */
    private function handleViolation(
        DeviceSensor $sensor,
        float        $actualValue,
        array        $violation,
        ?Alert       $existingAlert,
        Carbon       $recordedAt
    ): void {
        if (!$existingAlert) {
            $alert = Alert::create([
                'device_id'         => $sensor->device_id,
                'device_sensor_id'  => $sensor->id,
                'severity'          => $violation['severity'],
                'status'            => AlertStatus::Active,
                'trigger_value'     => $actualValue,            // ← actual reading, NOT threshold
                'threshold_breached' => $violation['threshold_key'],   // ← key name e.g. 'max_critical'
                'reason'            => $violation['reason'],
                'started_at'        => $recordedAt,
                'notification_count' => 0,
            ]);

            $this->sendAlertTriggeredNotification($alert);

            Log::info('Alert created', [
                'alert_id'  => $alert->id,
                'device_id' => $sensor->device_id,
                'sensor_id' => $sensor->id,
                'severity'  => $violation['severity']->value,
                'value'     => $actualValue,
            ]);
        } else {
            // Update existing alert with latest reading
            $existingAlert->update([
                'trigger_value'      => $actualValue,
                'threshold_breached' => $violation['threshold_key'],
                'reason'             => $violation['reason'],
                'severity'           => $violation['severity'],  // may escalate warning→critical
            ]);

            $area = $sensor->device->area ?? null;
            if ($this->shouldSendNotification($existingAlert, $area)) {
                $this->sendAlertTriggeredNotification($existingAlert->fresh());
            }
        }
    }

    /**
     * Auto-resolve an alert when the sensor returns to normal range.
     */
    private function handleBackInRange(DeviceSensor $sensor, ?Alert $existingAlert): void
    {
        if (!$existingAlert) {
            return;
        }

        $existingAlert->autoResolve();

        $area = $sensor->device->area ?? null;
        if ($area?->alert_back_in_range_enabled) {
            $this->sendBackInRangeNotification($existingAlert->fresh());
        }

        Log::info('Alert auto-resolved – sensor back in range', [
            'alert_id'  => $existingAlert->id,
            'device_id' => $sensor->device_id,
            'sensor_id' => $sensor->id,
        ]);
    }

    // ─── Notification gating ─────────────────────────────────────────────────

    private function shouldSendNotification(Alert $alert, $area): bool
    {
        if ($alert->status === AlertStatus::Active) {
            return true;
        }

        if ($alert->status === AlertStatus::Acknowledged) {
            if (!$alert->last_notification_at) {
                return true;
            }
            $intervalHours = $area?->acknowledged_alert_notification_interval ?? 24;
            return $alert->last_notification_at->diffInHours(now()) >= $intervalHours;
        }

        return false;
    }

    // ─── Notification dispatchers ─────────────────────────────────────────────

    private function sendAlertTriggeredNotification(Alert $alert): void
    {
        try {
            $alert->loadMissing('device', 'deviceSensor.sensorType');

            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                Log::warning('No users to notify for alert', ['alert_id' => $alert->id]);
                return;
            }

            Notification::send($users, new AlertTriggeredNotification(
                alertId:      $alert->id,
                deviceId:     $alert->device_id,
                severity:     $alert->severity->value,
                sensorType:   $alert->deviceSensor->sensorType->name,
                triggerValue: (float) $alert->trigger_value,
                reason:       $alert->reason,
                startedAt:    $alert->started_at->toDateTimeString(),
            ));

            $alert->incrementNotificationCount();

            Log::info('Alert notifications dispatched', [
                'alert_id'    => $alert->id,
                'users_count' => $users->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send alert notification', [
                'alert_id' => $alert->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function sendBackInRangeNotification(Alert $alert): void
    {
        try {
            $alert->loadMissing('device', 'deviceSensor.sensorType');

            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                return;
            }

            Notification::send($users, new AlertBackInRangeNotification(
                alertId:      $alert->id,
                deviceId:     $alert->device_id,
                deviceCode:   $alert->device->device_code,
                currentValue: (float) $alert->trigger_value,
                sensorType:   $alert->deviceSensor->sensorType->name,
            ));

            Log::info('Back-in-range notifications dispatched', [
                'alert_id'    => $alert->id,
                'users_count' => $users->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send back-in-range notification', [
                'alert_id' => $alert->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ─── User resolution ─────────────────────────────────────────────────────

    private function getUsersToNotify($area): Collection
    {
        if (!$area) {
            return collect();
        }

        $companyId = $area->hub->location->company_id ?? null;
        if (!$companyId) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($area) {
                // Users with no area restrictions see all alerts
                $q->whereDoesntHave('areaAccess')
                    // Users restricted to specific areas see only their areas
                    ->orWhereHas('areaAccess', fn ($q2) => $q2->where('area_id', $area->id));
            })
            ->get();
    }
}
