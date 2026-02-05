<?php

namespace App\Channels;

use App\Notifications\Messages\MsgClubEmailMessage;
use App\Services\Notification\Providers\MsgClubProvider;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MsgClubEmailChannel
{
    public function __construct(
        private MsgClubProvider $provider
    ) {}

    /**
     * Send the notification via MsgClub Email
     */
    public function send($notifiable, Notification $notification): void
    {
        // Get email from notifiable
        if (!$email = $notifiable->routeNotificationFor('msgclub_email', $notification)) {
            Log::warning('No email for email notification', [
                'notifiable_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toMsgClubEmail($notifiable);

        // Render HTML content
        $htmlContent = $message->render();

        // Send via provider
        $response = $this->provider->sendEmail(
            email: $email,
            name: trim("{$notifiable->first_name} {$notifiable->last_name}"),
            subject: $message->subject,
            htmlContent: $htmlContent
        );

        // Throw exception on failure
        if (!$response['success']) {
            throw new \Exception($response['error'] ?? 'Email sending failed');
        }

        Log::info('Email notification sent', [
            'notifiable_id' => $notifiable->id,
            'email' => $email,
            'subject' => $message->subject,
            'reference' => $response['reference'] ?? null,
        ]);
    }
}
