<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Channels\MsgClubSmsChannel;
use App\Models\Alert;
use App\Notifications\Messages\MsgClubEmailMessage;
use App\Notifications\Messages\MsgClubSmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AlertAcknowledgedNotification extends Notification implements ShouldQueue
{
    use Queueable;

//    public int $tries = 3;
//    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly Alert $alert
    ) {
        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        $area = $this->alert->device->area;

        if (!$area) {
            return [];
        }

        $channels = [];

        if ($area->alert_email_enabled &&
            config('notifications.channels.email.enabled', true)) {
            $channels[] = MsgClubEmailChannel::class;
        }

        if ($area->alert_sms_enabled &&
            config('notifications.channels.sms.enabled', true)) {
            $channels[] = MsgClubSmsChannel::class;
        }

        return $channels;
    }

    public function toMsgClubSms($notifiable): MsgClubSmsMessage
    {
        $acknowledgedBy = $this->alert->acknowledgedBy;

        return (new MsgClubSmsMessage)
            ->template('alert_acknowledged')
            ->data([
                'code' => $this->alert->device->device_code,
                'user' => $acknowledgedBy ? $acknowledgedBy->first_name : 'Unknown',
            ]);
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        return (new MsgClubEmailMessage)
            ->subject("Alert Acknowledged: {$this->alert->device->device_code}")
            ->view('emails.alerts.acknowledged', [
                'alert' => $this->alert,
                'user' => $notifiable,
                'device' => $this->alert->device,
                'area' => $this->alert->device->area,
                'data' => $this->getTemplateData(),
            ]);
    }

    protected function getTemplateData(): array
    {
        $device = $this->alert->device;
        $area = $device->area;

        return [
            'code' => $device->device_code,
            'device_name' => $device->device_name ?? $device->device_code,
            'location' => $area?->hub?->location?->name ?? 'N/A',
            'area' => $area?->name ?? 'N/A',
        ];
    }
}
