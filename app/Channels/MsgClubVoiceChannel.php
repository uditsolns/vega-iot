<?php

namespace App\Channels;

use App\Providers\MsgClubProvider;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MsgClubVoiceChannel
{
    public function __construct(
        private MsgClubProvider $provider
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        if (!$phone = $notifiable->routeNotificationFor('msgclub_voice', $notification)) {
            Log::warning('[MsgClubVoice] No phone number on notifiable', [
                'notifiable_id' => $notifiable->id,
                'notification'  => get_class($notification),
            ]);
            return;
        }

        $message = $notification->toMsgClubVoice($notifiable);

        $response = $this->provider->sendVoice(
            mobile:  $phone,
            message: $message->content,
        );

        if (!$response['success']) {
            throw new \Exception("[MsgClubVoice] {$response['error']}");
        }
    }
}
