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

class AlertAcknowledgedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public readonly string $event;

    public function __construct(
        public readonly int    $alertId,
        public readonly int    $deviceId,
        public readonly string $deviceCode,
        public readonly string $sensorLabel,
        public readonly int    $acknowledgedBy,
        public readonly string $acknowledgedByName,
        public readonly string $acknowledgedAt,
    ) {
        $this->event = 'acknowledged';
         $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        $device = Device::with('area')->find($this->deviceId);
        $area   = $device?->area;

        if (!$area) {
            return $channels;
        }

        if ($area->alert_email_enabled && config('notifications.channels.email.enabled', true)) {
            $channels[] = MsgClubEmailChannel::class;
        }

        if ($area->alert_sms_enabled && config('notifications.channels.sms.enabled', true)) {
            $channels[] = MsgClubSmsChannel::class;
        }

        return $channels;
    }

    public function toMsgClubSms($notifiable): MsgClubSmsMessage
    {
        $device = Device::with('area.hub.location')->find($this->deviceId);

        return (new MsgClubSmsMessage)
            ->template('alert_acknowledged')
            ->data([
                'code'     => $this->deviceCode,
                'sensor'   => $this->sensorLabel,
                'user'     => $this->acknowledgedByName,
                'location' => $this->resolveLocationPath($device),
            ]);
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $alert  = Alert::with(['device.area.hub.location', 'acknowledgedBy', 'deviceSensor.sensorType'])->find($this->alertId);
        $device = $alert->device;

        return (new MsgClubEmailMessage)
            ->subject("Alert Acknowledged: {$this->deviceCode}")
            ->view('emails.alerts.acknowledged', [
                'alert'  => $alert,
                'user'   => $notifiable,
                'device' => $device,
                'area'   => $device->area,
                'data'   => [
                    'code'           => $device->device_code,
                    'device_name'    => $device->device_name ?? $device->device_code,
                    'location'       => $device->area?->hub?->location?->name ?? 'N/A',
                    'hub'            => $device->area?->hub?->name             ?? 'N/A',
                    'area'           => $device->area?->name                   ?? 'N/A',
                    'sensor_label'   => $this->sensorLabel,
                    'acknowledged_by' => $this->acknowledgedByName,
                ],
            ]);
    }

    public function toArray($notifiable): array
    {
        $device = Device::with('area.hub.location')->find($this->deviceId);

        return [
            'alert_id'            => $this->alertId,
            'device_id'           => $this->deviceId,
            'device_code'         => $this->deviceCode,
            'device_name'         => $device->device_name ?? $this->deviceCode,
            'sensor_label'        => $this->sensorLabel,
            'acknowledged_by'     => $this->acknowledgedBy,
            'acknowledged_by_name' => $this->acknowledgedByName,
            'acknowledged_at'     => $this->acknowledgedAt,
            'location'            => $device->area?->hub?->location?->name ?? 'N/A',
            'event'               => $this->event,
        ];
    }

    private function resolveLocationPath(Device $device): string
    {
        $area = $device->relationLoaded('area') ? $device->area : $device->load('area')->area;
        if (!$area) {
            return 'Unassigned';
        }
        $area->loadMissing('hub.location');

        return implode(' > ', array_filter([
            $area->hub?->location?->name ?? null,
            $area->hub?->name            ?? null,
            $area->name,
        ]));
    }
}
