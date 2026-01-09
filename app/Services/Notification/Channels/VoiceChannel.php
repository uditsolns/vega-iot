<?php

namespace App\Services\Notification\Channels;

use App\Services\Notification\Contracts\NotificationChannelInterface;
use App\Services\Notification\DTOs\NotificationPayload;
use App\Services\Notification\DTOs\NotificationResult;
use App\Services\Notification\Providers\MsgClubProvider;
use Illuminate\Support\Facades\Log;

readonly class VoiceChannel implements NotificationChannelInterface
{
    public function __construct(
        private MsgClubProvider $provider
    ) {}

    public function send(NotificationPayload $payload): NotificationResult
    {
        try {
            // Check if user has a phone number
            if (empty($payload->user->phone)) {
                return NotificationResult::failure('User has no phone number');
            }

            // Get Voice template and replace placeholders
            $message = $this->generateVoiceMessage($payload);

            // Send via MsgClub Voice API
            $response = $this->provider->sendVoice(
                mobile: $payload->user->phone,
                message: $message
            );

            return NotificationResult::fromProviderResponse($response);
        } catch (\Exception $e) {
            Log::error('Voice notification failed', [
                'alert_id' => $payload->alertId,
                'user_id' => $payload->userId,
                'error' => $e->getMessage(),
            ]);

            return NotificationResult::failure($e->getMessage());
        }
    }

    public function supports(string $channel): bool
    {
        return $channel === 'voice';
    }

    public function getChannelName(): string
    {
        return 'voice';
    }

    /**
     * Generate voice message from template
     */
    private function generateVoiceMessage(NotificationPayload $payload): string
    {
        $templates = config('notifications.templates.voice');

        $template = $templates['alert_triggered'] ??
            'Critical Alert: Device {code} in {location}. Current value: {value}. Immediate action required.';

        return $payload->replaceTemplate($template);
    }
}
