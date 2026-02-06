<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Channels\MsgClubSmsChannel;
use App\Models\Alert;
use App\Models\Device;
use App\Notifications\Messages\MsgClubEmailMessage;
use App\Notifications\Messages\MsgClubSmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AlertBackInRangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $alertId,
        public readonly int $deviceId,
        public readonly string $deviceCode,
        public readonly float $currentValue,
        public readonly string $sensorType
    ) {
//        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        $device = Device::with('area')->find($this->deviceId);
        $area = $device?->area;

        if (!$area || !$area->alert_back_in_range_enabled) {
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
            ->template('alert_back_in_range')
            ->data([
                'code' => $this->deviceCode,
            ]);
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $alert = Alert::with('device.area.hub.location')->find($this->alertId);
        $device = $alert->device;

        return (new MsgClubEmailMessage)
            ->subject("Device Back in Range: {$this->deviceCode}")
            ->view('emails.alerts.back-in-range', [
                'alert' => $alert,
                'user' => $notifiable,
                'device' => $device,
                'area' => $device->area,
                'data' => [
                    'code' => $device->device_code,
                    'device_name' => $device->device_name ?? $device->device_code,
                    'location' => $device->area?->hub?->location?->name ?? 'N/A',
                    'area' => $device->area?->name ?? 'N/A',
                    'value' => $this->currentValue,
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
            'current_value' => $this->currentValue,
            'sensor_type' => $this->sensorType,
            'location' => $device->area?->hub?->location?->name ?? 'N/A',
            'event' => 'back_in_range',
        ];
    }
}
