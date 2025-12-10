<?php

namespace App\Services\Notification\Channels;

use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\Area;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class PushChannel implements NotificationChannelInterface
{
    /**
     * Send push notification for an alert
     *
     * @param Alert $alert
     * @param User $user
     * @param Area $area
     * @return bool
     */
    public function send(Alert $alert, User $user, Area $area): bool
    {
        // Create notification record
        $notification = AlertNotification::create([
            'alert_id' => $alert->id,
            'user_id' => $user->id,
            'channel' => 'push',
            'sent_at' => now(),
            'is_delivered' => false,
        ]);

        try {
            // TODO: Implement push notification service (Firebase Cloud Messaging, OneSignal, etc.)
            // Example:
            // $pushContent = $this->createPushContent($alert);
            // $response = $this->pushService->send($user->device_token, $pushContent);
            // $externalReference = $response->messageId;

            $pushContent = $this->createPushContent($alert);

            // Store message content
            $notification->update([
                'message_content' => json_encode($pushContent),
            ]);

            // Stub: Mark as delivered for now
            // TODO: Integrate with actual push notification service provider
            $notification->markDelivered('stub-push-reference-' . uniqid());

            Log::info('Alert push notification stub sent', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ]);

            return true;
        } catch (Exception $e) {
            $notification->markFailed($e->getMessage());

            Log::error('Failed to send alert push notification', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create push notification content for alert
     *
     * @param Alert $alert
     * @return array [title, body, data]
     */
    public function createPushContent(Alert $alert): array
    {
        $device = $alert->device;
        $deviceName = $device->device_name ?? $device->device_code;

        return [
            'title' => sprintf(
                '%s Alert: %s',
                ucfirst($alert->severity->value),
                $alert->type->label()
            ),
            'body' => sprintf(
                '%s - %s: %s',
                $deviceName,
                $alert->reason,
                number_format($alert->trigger_value, 2)
            ),
            'data' => [
                'alert_id' => $alert->id,
                'device_id' => $device->id,
                'device_code' => $device->device_code,
                'severity' => $alert->severity->value,
                'type' => $alert->type->value,
                'area_id' => $device->area_id,
                'area_name' => $device->area?->name,
            ],
            'priority' => $alert->severity->value === 'critical' ? 'high' : 'default',
            'sound' => $alert->severity->value === 'critical' ? 'critical_alert' : 'default',
        ];
    }

    /**
     * Get channel name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'push';
    }
}
