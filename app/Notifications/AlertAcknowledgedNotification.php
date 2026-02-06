<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Channels\MsgClubSmsChannel;
use App\Models\Device;
use App\Notifications\Messages\MsgClubEmailMessage;
use App\Notifications\Messages\MsgClubSmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AlertAcknowledgedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $alertId,
        public readonly int $deviceId,
        public readonly string $deviceCode,
        public readonly int $acknowledgedBy,
        public readonly string $acknowledgedByName,
        public readonly string $acknowledgedAt
    ) {
//        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        $device = Device::with('area')->find($this->deviceId);
        $area = $device?->area;

        if (!$area) {
            return $channels;
        }

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
        return (new MsgClubSmsMessage)
            ->template('alert_acknowledged')
            ->data([
                'code' => $this->deviceCode,
                'user' => $this->acknowledgedByName,
            ]);
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $alert = \App\Models\Alert::with(['device.area.hub.location', 'acknowledgedBy'])
            ->find($this->alertId);
        $device = $alert->device;

        return (new MsgClubEmailMessage)
            ->subject("Alert Acknowledged: {$this->deviceCode}")
            ->view('emails.alerts.acknowledged', [
                'alert' => $alert,
                'user' => $notifiable,
                'device' => $device,
                'area' => $device->area,
                'data' => [
                    'code' => $device->device_code,
                    'device_name' => $device->device_name ?? $device->device_code,
                    'location' => $device->area?->hub?->location?->name ?? 'N/A',
                    'area' => $device->area?->name ?? 'N/A',
                ],
            ]);
    }

    public function toArray($notifiable): array
    {
        $device = Device::with('area.hub.location')->find($this->deviceId);

        return [
            'alert_id' => $this->alertId,
            'device_id' => $this->deviceId,
            'device_code' => $this->deviceCode,
            'device_name' => $device->device_name ?? $this->deviceCode,
            'acknowledged_by' => $this->acknowledgedBy,
            'acknowledged_by_name' => $this->acknowledgedByName,
            'acknowledged_at' => $this->acknowledgedAt,
            'location' => $device->area?->hub?->location?->name ?? 'N/A',
            'event' => 'acknowledged',
        ];
    }
}
