<?php

namespace App\Services\Notification\Channels;

use App\Models\Alert;
use App\Models\Area;
use App\Models\User;

interface NotificationChannelInterface
{
    /**
     * Send notification for an alert
     *
     * @param Alert $alert
     * @param User $user
     * @param Area $area
     * @return bool True if notification sent successfully
     */
    public function send(Alert $alert, User $user, Area $area): bool;

    /**
     * Get channel name
     *
     * @return string
     */
    public function getName(): string;
}
