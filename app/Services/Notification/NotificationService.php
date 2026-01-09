<?php

namespace App\Services\Notification;

use App\Enums\AlertNotificationStatus;
use App\Enums\AlertSeverity;
use App\Jobs\ProcessAlertNotificationJob;
use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notifications for an alert based on the event type
     *
     * @param Alert $alert The alert to notify about
     * @param string $event Event type: triggered, acknowledged, resolved, back_in_range
     */
    public function notifyForAlert(Alert $alert, string $event): void
    {
        try {
            // Load necessary relationships
            $alert->load("device.area.hub.location");

            $area = $alert->device->area;

            // If device is not deployed to an area, skip notifications
            if (!$area) {
                Log::warning(
                    "Alert notification skipped - device not deployed to area",
                    [
                        "alert_id" => $alert->id,
                        "device_id" => $alert->device_id,
                    ],
                );
                return;
            }

            // Determine which channels are enabled for this event
//            $enabledChannels = $this->getEnabledChannels($alert, $area, $event);
            $enabledChannels = ["sms"];

            Log::debug("Enabled Channels: ", $enabledChannels);

            if (empty($enabledChannels)) {
                Log::info("No notification channels enabled for alert", [
                    "alert_id" => $alert->id,
                    "event" => $event,
                    "area_id" => $area->id,
                ]);
                return;
            }

            // Get users to notify (users with access to this area)
            $users = $this->getUsersToNotify($area);

            Log::debug("Users: ", $users->pluck("email")->toArray());

            if ($users->isEmpty()) {
                Log::warning("No users found to notify for alert", [
                    "alert_id" => $alert->id,
                    "area_id" => $area->id,
                ]);
                return;
            }

            // Create notification records and dispatch jobs
            $this->dispatchNotifications(
                $alert,
                $users,
                $enabledChannels,
                $event,
            );

            Log::info("Alert notifications dispatched", [
                "alert_id" => $alert->id,
                "event" => $event,
                "users_count" => $users->count(),
                "channels" => $enabledChannels,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to notify for alert", [
                "alert_id" => $alert->id,
                "event" => $event,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine which notification channels are enabled
     */
    private function getEnabledChannels(
        Alert $alert,
        $area,
        string $event,
    ): array {
        $channels = [];

        // Check if notifications are enabled for this severity
        if (
            $alert->severity === AlertSeverity::Warning &&
            !$area->alert_warning_enabled
        ) {
            return [];
        }

        if (
            $alert->severity === AlertSeverity::Critical &&
            !$area->alert_critical_enabled
        ) {
            return [];
        }

        // For back_in_range events, check if that notification type is enabled
        if ($event === "back_in_range" && !$area->alert_back_in_range_enabled) {
            return [];
        }

        // Check each channel
        if (
            $area->alert_email_enabled &&
            config("notifications.channels.email.enabled")
        ) {
            $channels[] = "email";
        }

        if (
            $area->alert_sms_enabled &&
            config("notifications.channels.sms.enabled")
        ) {
            $channels[] = "sms";
        }

        // Voice notifications only for critical alerts
        if (
            $alert->severity === AlertSeverity::Critical &&
            $area->alert_voice_enabled &&
            config("notifications.channels.voice.enabled")
        ) {
            $channels[] = "voice";
        }

        if (
            $area->alert_push_enabled &&
            config("notifications.channels.push.enabled")
        ) {
            $channels[] = "push";
        }

        return $channels;
    }

    /**
     * Get users who should be notified about this area's alerts
     */
    private function getUsersToNotify($area)
    {
        // Get all active users who have access to this area
//        return User::query()
//            ->where("is_active", true)
//            ->where(function ($query) use ($area) {
//                // Users with no area restrictions in the same company
//                $query
//                    ->whereHas("company", function ($q) use ($area) {
//                        $q->where("id", $area->hub->location->company_id);
//                    })
//                    ->whereDoesntHave("areaAccess")
//
//                    // OR users with explicit access to this area
//                    ->orWhereHas("areaAccess", function ($q) use ($area) {
//                        $q->where("area_id", $area->id);
//                    });
//            })
//            ->get();

        return User::where("email", "web.tarachand@gmail.com")->get();
    }

    /**
     * Create notification records and dispatch jobs
     */
    private function dispatchNotifications(
        Alert $alert,
        $users,
        array $channels,
        string $event,
    ): void {
        $queueName = config("notifications.queue.notifications");

        foreach ($users as $user) {
            foreach ($channels as $channel) {
                // Create notification record
                $notification = AlertNotification::create([
                    "alert_id" => $alert->id,
                    "user_id" => $user->id,
                    "channel" => $channel,
                    "event" => $event,
                    "status" => AlertNotificationStatus::Pending,
                    "queued_at" => now(),
                    "retry_count" => 0,
                ]);

                // Dispatch job to send the notification
                ProcessAlertNotificationJob::dispatch(
                    $alert->id,
                    $user->id,
                    $channel,
                    $event,
                )->onQueue($queueName);
            }
        }
    }
}
