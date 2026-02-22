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
     * Evaluate a single sensor reading against its thresholds
     */
    public function evaluateSensor(
        DeviceSensor  $sensor,
        ?float        $value,
        string|Carbon $recordedAt
    ): void
    {
        if ($value === null) {
            return;
        }

        $recordedAt = $recordedAt instanceof Carbon ? $recordedAt : Carbon::parse($recordedAt);

        try {
            $config = $sensor->currentConfiguration;

            if (!$config) {
                Log::debug('No configuration for sensor, skipping alert evaluation', [
                    'sensor_id' => $sensor->id,
                    'device_id' => $sensor->device_id,
                ]);
                return;
            }

            // Evaluate threshold
            $violation = $this->evaluateThreshold($value, $config);

            // Check for existing alert
            $existingAlert = Alert::where('device_id', $sensor->device_id)
                ->where('device_sensor_id', $sensor->id)
                ->whereIn('status', [AlertStatus::Active->value, AlertStatus::Acknowledged->value])
                ->first();

            if ($violation) {
                $this->handleViolation($sensor, $violation, $existingAlert, $recordedAt);
            } else {
                $this->handleBackInRange($sensor, $existingAlert);
            }
        } catch (Exception $e) {
            Log::error('Alert evaluation failed', [
                'sensor_id' => $sensor->id,
                'device_id' => $sensor->device_id,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Evaluate value against threshold configuration
     */
    private function evaluateThreshold(float $value, $config): ?array
    {
        // Check critical thresholds first
        if ($config->max_critical !== null && $value > $config->max_critical) {
            return [
                'severity' => AlertSeverity::Critical,
                'threshold' => $config->max_critical,
                'reason' => "Value {$value} exceeded critical maximum {$config->max_critical}",
            ];
        }

        if ($config->min_critical !== null && $value < $config->min_critical) {
            return [
                'severity' => AlertSeverity::Critical,
                'threshold' => $config->min_critical,
                'reason' => "Value {$value} below critical minimum {$config->min_critical}",
            ];
        }

        // Check warning thresholds
        if ($config->max_warning !== null && $value > $config->max_warning) {
            return [
                'severity' => AlertSeverity::Warning,
                'threshold' => $config->max_warning,
                'reason' => "Value {$value} exceeded warning maximum {$config->max_warning}",
            ];
        }

        if ($config->min_warning !== null && $value < $config->min_warning) {
            return [
                'severity' => AlertSeverity::Warning,
                'threshold' => $config->min_warning,
                'reason' => "Value {$value} below warning minimum {$config->min_warning}",
            ];
        }

        return null;
    }

    /**
     * Handle threshold violation
     */
    private function handleViolation(
        DeviceSensor $sensor,
        array        $violation,
        ?Alert       $existingAlert,
        Carbon       $recordedAt
    ): void
    {
        if (!$existingAlert) {
            // Create new alert
            $alert = Alert::create([
                'device_id' => $sensor->device_id,
                'device_sensor_id' => $sensor->id,
                'severity' => $violation['severity'],
                'status' => AlertStatus::Active,
                'trigger_value' => $sensor->sensorType->isNumeric() ? $violation['threshold'] : null,
                'threshold_breached' => $violation['threshold'],
                'reason' => $violation['reason'],
                'started_at' => $recordedAt,
                'notification_count' => 0,
            ]);

            $this->sendAlertTriggeredNotification($alert);

            Log::info('Alert created', [
                'alert_id' => $alert->id,
                'device_id' => $sensor->device_id,
                'sensor_id' => $sensor->id,
                'severity' => $violation['severity']->value,
            ]);
        } else {
            // Update existing alert
            $existingAlert->update([
                'trigger_value' => $violation['threshold'],
                'threshold_breached' => $violation['threshold'],
                'reason' => $violation['reason'],
            ]);

            // Send notification based on status and interval
            if ($this->shouldSendNotification($existingAlert, $sensor->device->area)) {
                $this->sendAlertTriggeredNotification($existingAlert);
            }
        }
    }

    /**
     * Handle sensor returning to normal range
     */
    private function handleBackInRange(DeviceSensor $sensor, ?Alert $existingAlert): void
    {
        if ($existingAlert) {
            $existingAlert->autoResolve();

            // Send notification if enabled
            if ($sensor->device->area && $sensor->device->area->alert_back_in_range_enabled) {
                $this->sendBackInRangeNotification($existingAlert);
            }

            Log::info('Alert auto-resolved - sensor back in range', [
                'alert_id' => $existingAlert->id,
                'device_id' => $sensor->device_id,
                'sensor_id' => $sensor->id,
            ]);
        }
    }

    /**
     * Determine if notification should be sent
     */
    private function shouldSendNotification(Alert $alert, $area): bool
    {
        if ($alert->status === AlertStatus::Active) {
            return true;
        }

        if ($alert->status === AlertStatus::Acknowledged) {
            if (!$alert->last_notification_at) {
                return true;
            }

            $intervalHours = $area->acknowledged_alert_notification_interval ?? 24;
            $hoursSinceLastNotification = $alert->last_notification_at->diffInHours(now());

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
            $alert->loadMissing('device', 'deviceSensor.sensorType');

            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                Log::warning('No users to notify for alert', [
                    'alert_id' => $alert->id,
                    'device_id' => $alert->device_id,
                ]);
                return;
            }

            $notification = new AlertTriggeredNotification(
                alertId: $alert->id,
                deviceId: $alert->device_id,
                severity: $alert->severity->value,
                sensorType: $alert->deviceSensor->sensorType->name,
                triggerValue: (float)$alert->trigger_value,
                reason: $alert->reason,
                startedAt: $alert->started_at->toDateTimeString()
            );

            Notification::send($users, $notification);
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
            $alert->loadMissing('device', 'deviceSensor.sensorType');

            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                return;
            }

            $notification = new AlertBackInRangeNotification(
                alertId: $alert->id,
                deviceId: $alert->device_id,
                deviceCode: $alert->device->device_code,
                currentValue: (float)$alert->trigger_value,
                sensorType: $alert->deviceSensor->sensorType->name
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
     * Get users who should be notified
     */
    private function getUsersToNotify($area): Collection
    {
        if (!$area) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($area) {
                $query->whereHas('company', function ($q) use ($area) {
                    $q->where('id', $area->hub->location->company_id);
                })
                    ->whereDoesntHave('areaAccess')
                    ->orWhereHas('areaAccess', function ($q) use ($area) {
                        $q->where('area_id', $area->id);
                    });
            })
            ->get();
    }
}
