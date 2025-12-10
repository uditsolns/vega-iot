<?php

namespace App\Services\Notification\Channels;

use App\Enums\AlertSeverity;
use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\Area;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class VoiceChannel implements NotificationChannelInterface
{
    /**
     * Send voice call notification for an alert
     * Only for critical severity alerts
     *
     * @param Alert $alert
     * @param User $user
     * @param Area $area
     * @return bool
     */
    public function send(Alert $alert, User $user, Area $area): bool
    {
        // Voice notifications only for critical alerts
        if ($alert->severity !== AlertSeverity::Critical) {
            Log::info('Skipping voice notification for non-critical alert', [
                'alert_id' => $alert->id,
                'severity' => $alert->severity->value,
            ]);
            return false;
        }

        // Create notification record
        $notification = AlertNotification::create([
            'alert_id' => $alert->id,
            'user_id' => $user->id,
            'channel' => 'voice',
            'sent_at' => now(),
            'is_delivered' => false,
        ]);

        try {
            // TODO: Implement voice call gateway integration (Twilio Voice, etc.)
            // Example:
            // $voiceMessage = $this->createVoiceMessage($alert);
            // $response = $this->voiceGateway->call($user->phone, $voiceMessage);
            // $externalReference = $response->callSid;

            $voiceMessage = $this->createVoiceMessage($alert);

            // Store message content
            $notification->update([
                'message_content' => $voiceMessage,
            ]);

            // Stub: Mark as delivered for now
            // TODO: Integrate with actual voice call service provider
            $notification->markDelivered('stub-voice-reference-' . uniqid());

            Log::info('Alert voice notification stub sent', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);

            return true;
        } catch (Exception $e) {
            $notification->markFailed($e->getMessage());

            Log::error('Failed to send alert voice notification', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create voice message script for alert
     *
     * @param Alert $alert
     * @return string
     */
    public function createVoiceMessage(Alert $alert): string
    {
        $device = $alert->device;
        $deviceName = $device->device_name ?? $device->device_code;
        $areaName = $device->area?->name ?? 'unknown area';

        return sprintf(
            'Critical alert notification. %s alert for device %s in %s. ' .
            'Trigger value is %s. Please check the system immediately. ' .
            'This is a critical alert from VEGA IoT Sensor Management System.',
            $alert->type->label(),
            $deviceName,
            $areaName,
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
        return 'voice';
    }
}
