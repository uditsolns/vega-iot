<?php

namespace App\Services\Notification\Channels;

use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\Area;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannelInterface
{
    /**
     * Send SMS notification for an alert
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
            'channel' => 'sms',
            'sent_at' => now(),
            'is_delivered' => false,
        ]);

        try {
            // TODO: Implement SMS gateway integration (Twilio, AWS SNS, etc.)
            // Example:
            // $smsContent = $this->createSmsContent($alert);
            // $response = $this->smsGateway->send($user->phone, $smsContent);
            // $externalReference = $response->messageId;

            $smsContent = $this->createSmsContent($alert);

            // Store message content
            $notification->update([
                'message_content' => $smsContent,
            ]);

            // Stub: Mark as delivered for now
            // TODO: Integrate with actual SMS service provider
            $notification->markDelivered('stub-sms-reference-' . uniqid());

            Log::info('Alert SMS notification stub sent', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);

            return true;
        } catch (Exception $e) {
            $notification->markFailed($e->getMessage());

            Log::error('Failed to send alert SMS notification', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create SMS content for alert (max 160 characters recommended)
     *
     * @param Alert $alert
     * @return string
     */
    public function createSmsContent(Alert $alert): string
    {
        $device = $alert->device;
        $deviceName = $device->device_name ?? $device->device_code;

        return sprintf(
            '[%s ALERT] %s: %s at %s. Value: %s. Check system for details.',
            strtoupper($alert->severity->value),
            $alert->type->label(),
            $deviceName,
            $device->area?->name ?? 'Unknown',
            number_format($alert->trigger_value, 1)
        );
    }

    /**
     * Get channel name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'sms';
    }
}
