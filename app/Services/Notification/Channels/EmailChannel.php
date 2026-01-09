<?php

namespace App\Services\Notification\Channels;

use App\Services\Notification\Contracts\NotificationChannelInterface;
use App\Services\Notification\DTOs\NotificationPayload;
use App\Services\Notification\DTOs\NotificationResult;
use App\Services\Notification\Providers\MsgClubProvider;
use Exception;
use Illuminate\Support\Facades\Log;

readonly class EmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private MsgClubProvider $provider
    ) {}

    public function send(NotificationPayload $payload): NotificationResult
    {
        try {
            // Generate HTML content from Blade template
            $htmlContent = $this->renderEmailTemplate($payload);

            // Generate subject line
            $subject = $this->generateSubject($payload);

            // Send via MsgClub Email API
            $response = $this->provider->sendEmail(
                email: $payload->user->email,
                name: "{$payload->user->first_name} {$payload->user->last_name}",
                subject: $subject,
                htmlContent: $htmlContent
            );

            return NotificationResult::fromProviderResponse($response);
        } catch (Exception $e) {
            Log::error('Email notification failed', [
                'alert_id' => $payload->alertId,
                'user_id' => $payload->userId,
                'error' => $e->getMessage(),
            ]);

            return NotificationResult::failure($e->getMessage());
        }
    }

    public function supports(string $channel): bool
    {
        return $channel === 'email';
    }

    public function getChannelName(): string
    {
        return 'email';
    }

    /**
     * Render the email template for the given event
     */
    private function renderEmailTemplate(NotificationPayload $payload): string
    {
        $templateMap = [
            'triggered' => 'emails.alerts.triggered',
            'acknowledged' => 'emails.alerts.acknowledged',
            'resolved' => 'emails.alerts.resolved',
            'back_in_range' => 'emails.alerts.back-in-range',
        ];

        $template = $templateMap[$payload->event] ?? 'emails.alerts.triggered';

        return view($template, [
            'alert' => $payload->alert,
            'user' => $payload->user,
            'device' => $payload->device,
            'area' => $payload->area,
            'data' => $payload->data,
        ])->render();
    }

    /**
     * Generate email subject line
     */
    private function generateSubject(NotificationPayload $payload): string
    {
        $severity = ucfirst($payload->alert->severity->value);
        $deviceCode = $payload->device->device_code;

        return match($payload->event) {
            'triggered' => "$severity Alert: Device $deviceCode",
            'acknowledged' => "Alert Acknowledged: Device $deviceCode",
            'resolved' => "Alert Resolved: Device $deviceCode",
            'back_in_range' => "Device Back in Range: $deviceCode",
            default => "Alert Notification: Device $deviceCode",
        };
    }
}
