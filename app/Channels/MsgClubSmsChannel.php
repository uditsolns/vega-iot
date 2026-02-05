<?php

namespace App\Channels;

use App\Notifications\Messages\MsgClubSmsMessage;
use App\Services\Notification\Providers\MsgClubProvider;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MsgClubSmsChannel
{
    public function __construct(
        private MsgClubProvider $provider
    ) {}

    /**
     * Send the notification via MsgClub SMS
     */
    public function send($notifiable, Notification $notification): void
    {
        // Get phone number from notifiable
        if (!$phone = $notifiable->routeNotificationFor('msgclub_sms', $notification)) {
            Log::warning('No phone number for SMS notification', [
                'notifiable_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toMsgClubSms($notifiable);

        // Send via provider
        $response = $this->provider->sendSms(
            mobile: $phone,
            message: $message->content,
            templateId: $message->templateId
        );

        // Throw exception on failure (will trigger retry)
        if (!$response['success']) {
            throw new \Exception($response['error'] ?? 'SMS sending failed');
        }

        Log::info('SMS notification sent', [
            'notifiable_id' => $notifiable->id,
            'phone' => $phone,
            'reference' => $response['reference'] ?? null,
        ]);
    }
}
