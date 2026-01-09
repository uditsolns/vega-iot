<?php

namespace App\Services\Notification\Channels;

use App\Services\Notification\Contracts\NotificationChannelInterface;
use App\Services\Notification\DTOs\NotificationPayload;
use App\Services\Notification\DTOs\NotificationResult;
use App\Services\Notification\Providers\MsgClubProvider;
use Illuminate\Support\Facades\Log;

readonly class SmsChannel implements NotificationChannelInterface
{
    public function __construct(private MsgClubProvider $provider) {}

    public function send(NotificationPayload $payload): NotificationResult
    {
        try {
            // Check if user has a phone number
            if (empty($payload->user->phone)) {
                return NotificationResult::failure("User has no phone number");
            }

            // Get SMS template and replace placeholders
            $message = $this->generateSmsMessage($payload);

            // Get template ID for the event
            $templateId = $this->getTemplateId($payload);

            // Send via MsgClub SMS API
            $response = $this->provider->sendSms(
                mobile: $payload->user->phone,
                message: $message,
                templateId: $templateId,
            );

            return NotificationResult::fromProviderResponse($response);
        } catch (\Exception $e) {
            Log::error("SMS notification failed", [
                "alert_id" => $payload->alertId,
                "user_id" => $payload->userId,
                "error" => $e->getMessage(),
            ]);

            return NotificationResult::failure($e->getMessage());
        }
    }

    public function supports(string $channel): bool
    {
        return $channel === "sms";
    }

    public function getChannelName(): string
    {
        return "sms";
    }

    /**
     * Get template key based on event
     */
    private function getTemplateKey(string $event): string
    {
        return match ($event) {
            "acknowledged" => "alert_acknowledged",
            "resolved" => "alert_resolved",
            "back_in_range" => "alert_back_in_range",
            default => "alert_triggered",
        };
    }

    /**
     * Generate SMS message from template
     */
    private function generateSmsMessage(NotificationPayload $payload): string
    {
        $templates = config("notifications.templates.sms");
        $templateKey = $this->getTemplateKey($payload->event);

        $template =
            $templates[$templateKey]["content"] ??
            $templates["alert_triggered"]["content"];

        return $payload->replaceTemplate($template);
    }

    /**
     * Get SMS template ID for the event
     */
    private function getTemplateId(NotificationPayload $payload): ?string
    {
        $templates = config("notifications.templates.sms");
        $templateKey = $this->getTemplateKey($payload->event);

        return $templates[$templateKey]["id"] ??
            ($templates["alert_triggered"]["id"] ?? null);
    }
}
