<?php

namespace App\Channels;

use App\Notifications\Messages\MsgClubVoiceMessage;
use App\Services\Notification\Providers\MsgClubProvider;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MsgClubVoiceChannel
{
    public function __construct(
        private MsgClubProvider $provider
    ) {}

    /**
     * Send the notification via MsgClub Voice Call
     */
    public function send($notifiable, Notification $notification): void
    {
        // Get phone number from notifiable
        if (!$phone = $notifiable->routeNotificationFor('msgclub_voice', $notification)) {
            Log::warning('No phone number for voice notification', [
                'notifiable_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toMsgClubVoice($notifiable);

        // Send via provider
        $response = $this->provider->sendVoice(
            mobile: $phone,
            message: $message->content
        );

        // Throw exception on failure
        if (!$response['success']) {
            throw new \Exception($response['error'] ?? 'Voice call failed');
        }

        Log::info('Voice notification sent', [
            'notifiable_id' => $notifiable->id,
            'phone' => $phone,
            'reference' => $response['reference'] ?? null,
        ]);
    }
}
