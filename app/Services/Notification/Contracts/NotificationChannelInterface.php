<?php

namespace App\Services\Notification\Contracts;

use App\Services\Notification\DTOs\NotificationPayload;
use App\Services\Notification\DTOs\NotificationResult;

interface NotificationChannelInterface
{
    /**
     * Send notification via this channel
     */
    public function send(NotificationPayload $payload): NotificationResult;

    /**
     * Check if this channel supports the given channel name
     */
    public function supports(string $channel): bool;

    /**
     * Get the channel name
     */
    public function getChannelName(): string;
}
