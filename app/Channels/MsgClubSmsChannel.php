<?php

namespace App\Channels;

use App\Providers\MsgClubProvider;
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
            Log::warning('[MsgClubSms] No phone number on notifiable', [
                'notifiable_id' => $notifiable->id,
                'notification'  => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toMsgClubSms($notifiable);

        // Send via provider
        $response = $this->provider->sendSms(
            mobile:     $phone,
            message:    $message->content,
            templateId: $message->templateId,
        );

        // Throw exception on failure (will trigger retry)
        if (!$response['success']) {
            throw new \Exception("[MsgClubSms] {$response['error']}");
        }
    }
}
