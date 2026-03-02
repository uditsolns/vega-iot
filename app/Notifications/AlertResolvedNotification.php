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

class AlertResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public readonly string $event;

    public function __construct(
        public readonly int    $alertId,
        public readonly int    $deviceId,
        public readonly string $deviceCode,
        public readonly string $sensorLabel,
        public readonly int    $resolvedBy,
        public readonly string $resolvedByName,
        public readonly string $resolvedAt,
    ) {
        $this->event = 'resolved';
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

        // FIX: was incorrectly checking alert_back_in_range_enabled for a resolved notification.
        // Resolved notifications use the same channel flags as triggered alerts.
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
            ->template('alert_resolved')
            ->data([
                'code'     => $this->deviceCode,
                'sensor'   => $this->sensorLabel,
                'user'     => $this->resolvedByName,
                'location' => $this->resolveLocationPath($device),
            ]);
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $alert  = Alert::with(['device.area.hub.location', 'resolvedBy', 'deviceSensor.sensorType'])->find($this->alertId);
        $device = $alert->device;

        return (new MsgClubEmailMessage)
            ->subject("Alert Resolved: {$this->deviceCode}")
            ->view('emails.alerts.resolved', [
                'alert'  => $alert,
                'user'   => $notifiable,
                'device' => $device,
                'area'   => $device->area,
                'data'   => [
                    'code'         => $device->device_code,
                    'device_name'  => $device->device_name ?? $device->device_code,
                    'location'     => $device->area?->hub?->location?->name ?? 'N/A',
                    'hub'          => $device->area?->hub?->name             ?? 'N/A',
                    'area'         => $device->area?->name                   ?? 'N/A',
                    'sensor_label' => $this->sensorLabel,
                    'resolved_by'  => $this->resolvedByName,
                ],
            ]);
    }

    public function toArray($notifiable): array
    {
        $device = Device::with('area.hub.location')->find($this->deviceId);

        return [
            'alert_id'       => $this->alertId,
            'device_id'      => $this->deviceId,
            'device_code'    => $this->deviceCode,
            'device_name'    => $device->device_name ?? $this->deviceCode,
            'sensor_label'   => $this->sensorLabel,
            'resolved_by'    => $this->resolvedBy,
            'resolved_by_name' => $this->resolvedByName,
            'resolved_at'    => $this->resolvedAt,
            'location'       => $device->area?->hub?->location?->name ?? 'N/A',
            'event'          => $this->event,
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
