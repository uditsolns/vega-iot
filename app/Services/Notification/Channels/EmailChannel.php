<?php

namespace App\Services\Notification\Channels;

use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\Area;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements NotificationChannelInterface
{
    /**
     * Send email notification for an alert
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
            'channel' => 'email',
            'sent_at' => now(),
            'is_delivered' => false,
        ]);

        try {
            // Create email content
            $emailContent = $this->createEmailContent($alert, $area);

            // Send email using Laravel Mail facade
            Mail::raw($emailContent['body'], function ($message) use ($user, $emailContent) {
                $message->to($user->email)
                    ->subject($emailContent['subject']);
            });

            // Mark as delivered
            $notification->markDelivered();

            Log::info('Alert email notification sent', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (Exception $e) {
            // Mark as failed
            $notification->markFailed($e->getMessage());

            Log::error('Failed to send alert email notification', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create email content for alert
     *
     * @param Alert $alert
     * @param Area $area
     * @return array [subject, body]
     */
    public function createEmailContent(Alert $alert, Area $area): array
    {
        $device = $alert->device;
        $locationPath = $device->getLocationPath();

        // Build subject
        $subject = sprintf(
            '[%s ALERT] %s - %s',
            strtoupper($alert->severity->value),
            $alert->type->label(),
            $device->device_name ?? $device->device_code
        );

        // Build body
        $body = "ALERT NOTIFICATION\n";
        $body .= "==================\n\n";
        $body .= "Severity: " . strtoupper($alert->severity->value) . "\n";
        $body .= "Type: " . $alert->type->label() . "\n";
        $body .= "Status: " . $alert->status->label() . "\n\n";

        $body .= "DEVICE INFORMATION\n";
        $body .= "------------------\n";
        $body .= "Device: " . ($device->device_name ?? $device->device_code) . "\n";
        $body .= "Device Code: " . $device->device_code . "\n";
        $body .= "Location: " . $locationPath . "\n";
        $body .= "Area: " . $area->name . "\n\n";

        $body .= "ALERT DETAILS\n";
        $body .= "-------------\n";
        $body .= "Reason: " . $alert->reason . "\n";
        $body .= "Trigger Value: " . number_format($alert->trigger_value, 2) . "\n";
        $body .= "Threshold Breached: " . $alert->threshold_breached . "\n";
        $body .= "Started At: " . $alert->started_at->format('Y-m-d H:i:s') . "\n";

        if ($alert->acknowledged_at) {
            $body .= "\nACKNOWLEDGMENT\n";
            $body .= "--------------\n";
            $body .= "Acknowledged By: " . ($alert->acknowledgedBy?->first_name ?? 'Unknown') . "\n";
            $body .= "Acknowledged At: " . $alert->acknowledged_at->format('Y-m-d H:i:s') . "\n";
            if ($alert->acknowledge_comment) {
                $body .= "Comment: " . $alert->acknowledge_comment . "\n";
            }
        }

        $body .= "\n--\n";
        $body .= "VEGA IoT Sensor Management System\n";
        $body .= "This is an automated alert notification. Please do not reply to this email.\n";

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Get channel name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'email';
    }
}
