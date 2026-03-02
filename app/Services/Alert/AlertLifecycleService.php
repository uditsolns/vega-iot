<?php

namespace App\Services\Alert;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\DeviceSensor;
use App\Notifications\AlertBackInRangeNotification;
use App\Notifications\AlertTriggeredNotification;
use App\Traits\ResolvesAlertRecipients;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

readonly class AlertLifecycleService
{
    use ResolvesAlertRecipients;

    /**
     * Evaluate a single sensor reading against its configured thresholds.
     */
    public function evaluateSensor(
        DeviceSensor  $sensor,
        ?float        $value,
        string|Carbon $recordedAt
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

            // ── DB lock prevents duplicate alert creation from concurrent ingestion ──
            // Offline bulk packets can dispatch multiple ReadingReceived events nearly
            // simultaneously. Without lockForUpdate(), two workers can both read
            // existingAlert = null and create duplicate active alerts.
            $alert    = null;
            $isNew    = false;
            $shouldNotify = false;

            DB::transaction(function () use (
                $sensor, $value, $violation, $recordedAt,
                &$alert, &$isNew, &$shouldNotify
            ) {
                $existingAlert = Alert::where('device_id', $sensor->device_id)
                    ->where('device_sensor_id', $sensor->id)
                    ->whereIn('status', [AlertStatus::Active->value, AlertStatus::Acknowledged->value])
                    ->lockForUpdate()
                    ->first();

                if ($violation) {
                    if (!$existingAlert) {
                        $alert  = Alert::create([
                            'device_id'          => $sensor->device_id,
                            'device_sensor_id'   => $sensor->id,
                            'severity'           => $violation['severity'],
                            'status'             => AlertStatus::Active,
                            'trigger_value'      => $value,
                            'threshold_breached' => $violation['threshold_key'],
                            'reason'             => $violation['reason'],
                            'started_at'         => $recordedAt,
                            'notification_count' => 0,
                        ]);
                        $isNew        = true;
                        $shouldNotify = true;
                    } else {
                        // Escalate severity if needed (warning → critical)
                        $existingAlert->update([
                            'trigger_value'      => $value,
                            'threshold_breached' => $violation['threshold_key'],
                            'reason'             => $violation['reason'],
                            'severity'           => $violation['severity'],
                        ]);
                        $alert = $existingAlert;

                        $area = $sensor->device->area ?? null;
                        $shouldNotify = $this->shouldSendNotification($existingAlert, $area);
                    }
                } else {
                    if ($existingAlert) {
                        $existingAlert->autoResolve();
                        $alert = $existingAlert->fresh();
                    }
                }
            });

            // Dispatch notifications OUTSIDE the transaction to avoid
            // queued jobs firing before the transaction commits.
            if ($alert) {
                if ($violation && $shouldNotify) {
                    $this->sendAlertTriggeredNotification($alert->fresh(), $violation);

                    Log::info($isNew ? 'Alert created' : 'Alert updated & re-notified', [
                        'alert_id'  => $alert->id,
                        'device_id' => $sensor->device_id,
                        'sensor_id' => $sensor->id,
                        'severity'  => $violation['severity']->value,
                        'value'     => $value,
                    ]);
                } elseif (!$violation) {
                    $area = $sensor->device->area ?? null;
                    if ($area?->alert_back_in_range_enabled) {
                        $this->sendBackInRangeNotification($alert);
                    }

                    Log::info('Alert auto-resolved – sensor back in range', [
                        'alert_id'  => $alert->id,
                        'device_id' => $sensor->device_id,
                        'sensor_id' => $sensor->id,
                    ]);
                }
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
     * Compare value against thresholds, returning the most severe violation.
     *
     * Returns:
     * [
     *   'severity'        => AlertSeverity,
     *   'threshold_key'   => string,   // 'max_critical' | 'min_critical' | 'max_warning' | 'min_warning'
     *   'threshold_value' => float,
     *   'reason'          => string,
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

    private function sendAlertTriggeredNotification(Alert $alert, array $violation): void
    {
        try {
            $alert->loadMissing(['device.area.hub.location', 'deviceSensor.sensorType']);

            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                Log::warning('No users to notify for alert', ['alert_id' => $alert->id]);
                return;
            }

            Notification::send($users, new AlertTriggeredNotification(
                alertId:        $alert->id,
                deviceId:       $alert->device_id,
                severity:       $alert->severity->value,
                sensorType:     $alert->sensor_type_name,
                sensorLabel:    $alert->sensor_label,
                triggerValue:   (float) $alert->trigger_value,
                thresholdValue: $violation['threshold_value'],
                thresholdKey:   $violation['threshold_key'],
                reason:         $alert->reason,
                startedAt:      $alert->started_at->toDateTimeString(),
            ));

            $alert->incrementNotificationCount();

            Log::info('Alert triggered notifications dispatched', [
                'alert_id'    => $alert->id,
                'users_count' => $users->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send alert triggered notification', [
                'alert_id' => $alert->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function sendBackInRangeNotification(Alert $alert): void
    {
        try {
            $alert->loadMissing(['device.area.hub.location', 'deviceSensor.sensorType']);

            $users = $this->getUsersToNotify($alert->device->area);

            if ($users->isEmpty()) {
                return;
            }

            Notification::send($users, new AlertBackInRangeNotification(
                alertId:      $alert->id,
                deviceId:     $alert->device_id,
                deviceCode:   $alert->device->device_code,
                currentValue: (float) $alert->trigger_value,
                sensorType:   $alert->sensor_type_name,
                sensorLabel:  $alert->sensor_label,
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
}
