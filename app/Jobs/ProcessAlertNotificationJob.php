<?php

namespace App\Jobs;

use App\Enums\AlertNotificationStatus;
use App\Models\Alert;
use App\Models\AlertNotification;
use App\Services\Notification\Channels\EmailChannel;
use App\Services\Notification\Channels\SmsChannel;
use App\Services\Notification\Channels\VoiceChannel;
use App\Services\Notification\Contracts\NotificationChannelInterface;
use App\Services\Notification\DTOs\NotificationPayload;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAlertNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $alertId,
        public readonly int $userId,
        public readonly string $channel,
        public readonly string $event,
    ) {}

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            // Find the notification record
            $notification = AlertNotification::where("alert_id", $this->alertId)
                ->where("user_id", $this->userId)
                ->where("channel", $this->channel)
                ->where("event", $this->event)
                ->where("status", AlertNotificationStatus::Pending)
                ->orderBy("id", "desc")
                ->first();

            if (!$notification) {
                Log::warning(
                    "Notification record not found or already processed",
                    [
                        "alert_id" => $this->alertId,
                        "user_id" => $this->userId,
                        "channel" => $this->channel,
                        "event" => $this->event,
                    ],
                );
                return;
            }

            // Build notification payload
            $payload = NotificationPayload::fromIds(
                $this->alertId,
                $this->userId,
                $this->channel,
                $this->event,
            );

            // Get the appropriate channel implementation
            $channelImpl = $this->resolveChannel($this->channel);

            if (!$channelImpl) {
                $this->markAsFailed(
                    $notification,
                    "Channel not supported: " . $this->channel,
                );
                return;
            }

            // Send the notification
            $result = $channelImpl->send($payload);

            // Update notification record based on result
            if ($result->success) {
                $this->markAsSent($notification, $result->reference);

                // Update alert notification tracking
                $alert = Alert::find($this->alertId);
                if ($alert) {
                    $alert->incrementNotificationCount();
                }
            } else {
                $this->markAsFailed($notification, $result->error);
            }
        } catch (Exception $e) {
            Log::error("Notification job failed", [
                "alert_id" => $this->alertId,
                "user_id" => $this->userId,
                "channel" => $this->channel,
                "error" => $e->getMessage(),
            ]);

            // If notification record exists, mark as failed
            if (isset($notification)) {
                $this->markAsFailed($notification, $e->getMessage());
            }

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Resolve the channel implementation
     */
    private function resolveChannel(
        string $channel,
    ): ?NotificationChannelInterface {
        return match ($channel) {
            "email" => app(EmailChannel::class),
            "sms" => app(SmsChannel::class),
            "voice" => app(VoiceChannel::class),
            default => null,
        };
    }

    /**
     * Mark notification as successfully sent
     */
    private function markAsSent(
        AlertNotification $notification,
        ?string $reference,
    ): void {
        $notification->update([
            "status" => AlertNotificationStatus::Sent,
            "sent_at" => now(),
            "external_reference" => $reference,
            "error_message" => null,
        ]);

        Log::info("Notification sent successfully", [
            "notification_id" => $notification->id,
            "alert_id" => $this->alertId,
            "user_id" => $this->userId,
            "channel" => $this->channel,
            "reference" => $reference,
        ]);
    }

    /**
     * Mark notification as failed
     */
    private function markAsFailed(
        AlertNotification $notification,
        ?string $error,
    ): void {
        $notification->update([
            "status" => AlertNotificationStatus::Failed,
            "failed_at" => now(),
            "error_message" => $error,
            "retry_count" => $notification->retry_count + 1,
        ]);

        Log::error("Notification failed", [
            "notification_id" => $notification->id,
            "alert_id" => $this->alertId,
            "user_id" => $this->userId,
            "channel" => $this->channel,
            "error" => $error,
            "retry_count" => $notification->retry_count + 1,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Notification job permanently failed", [
            "alert_id" => $this->alertId,
            "user_id" => $this->userId,
            "channel" => $this->channel,
            "error" => $exception->getMessage(),
        ]);
    }
}
