<?php

namespace App\Channels;

use App\Providers\MsgClubProvider;
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
            // Log here only — provider never sees this case
            Log::warning('[MsgClubEmail] No email address on notifiable', [
                'notifiable_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toMsgClubEmail($notifiable);

        // Render HTML content
        $htmlContent = $message->render();

        $attachmentPaths = array_column($message->getAttachments(), 'path');

        // Send via provider with attachments
        $response = $this->provider->sendEmail(
            email: $email,
            name: trim("{$notifiable->first_name} {$notifiable->last_name}"),
            subject: $message->subject,
            htmlContent: $htmlContent,
            attachments: $attachmentPaths
        );

        // Provider already logs success, API rejections, network errors, and invalid responses.
        // Re-throw on failure so the queued job retries and eventually lands in failed_jobs.
        if (!$response['success']) {
            throw new \Exception("[MsgClubEmail] {$response['error']}");
        }
    }
}
