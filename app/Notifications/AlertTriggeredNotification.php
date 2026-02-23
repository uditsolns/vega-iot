<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Channels\MsgClubSmsChannel;
use App\Channels\MsgClubVoiceChannel;
use App\Enums\AlertSeverity;
use App\Models\Alert;
use App\Models\Device;
use App\Notifications\Messages\MsgClubEmailMessage;
use App\Notifications\Messages\MsgClubSmsMessage;
use App\Notifications\Messages\MsgClubVoiceMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AlertTriggeredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int    $alertId,
        public readonly int    $deviceId,
        public readonly string $severity,
        public readonly string $sensorType,
        public readonly float  $triggerValue,
        public readonly string $reason,
        public readonly string $startedAt,
        public readonly string $event = 'triggered',
    ) {}

    // ─── Channels ─────────────────────────────────────────────────────────────

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

        if (
            $this->severity === AlertSeverity::Critical->value
            && $area->alert_voice_enabled
            && config('notifications.channels.voice.enabled', false)
        ) {
            $channels[] = MsgClubVoiceChannel::class;
        }

        return $channels;
    }

    /**
     * Get the SMS representation
     */
    public function toMsgClubSms($notifiable): MsgClubSmsMessage
    {
        $device = Device::find($this->deviceId);

        return (new MsgClubSmsMessage)
            ->template('alert_triggered')
            ->data([
                'severity'    => ucfirst($this->severity),
                'code'        => $device->device_code,
                'device_code' => $device->device_code,
                'value'       => number_format($this->triggerValue, 1),
                'location'    => $this->resolveLocationPath($device),
                'area'        => $device->area?->name ?? 'N/A',
            ]);
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $device = Device::with(['area.hub.location'])->find($this->deviceId);
        $alert  = Alert::with(['deviceSensor.currentConfiguration'])->find($this->alertId);

        return (new MsgClubEmailMessage)
            ->subject(ucfirst($this->severity) . " Alert: Device {$device->device_code}")
            ->view('emails.alerts.triggered', [
                'alert'  => $alert,
                'user'   => $notifiable,
                'device' => $device,
                'area'   => $device->area,
                'data'   => $this->buildTemplateData($device, $alert),
            ]);
    }

    public function toMsgClubVoice($notifiable): MsgClubVoiceMessage
    {
        $device = Device::find($this->deviceId);

        return (new MsgClubVoiceMessage)
            ->template('alert_triggered')
            ->data([
                'severity' => ucfirst($this->severity),
                'code'     => $device->device_code,
                'value'    => number_format($this->triggerValue, 1),
                'location' => $this->resolveLocationPath($device),
            ]);
    }

    public function toArray($notifiable): array
    {
        $device = Device::with('area.hub.location')->find($this->deviceId);

        return [
            'alert_id'     => $this->alertId,
            'device_id'    => $this->deviceId,
            'device_code'  => $device->device_code,
            'device_name'  => $device->device_name ?? $device->device_code,
            'severity'     => $this->severity,
            'sensor_type'  => $this->sensorType,
            'trigger_value' => $this->triggerValue,
            'reason'       => $this->reason,
            'location'     => $this->resolveLocationPath($device),
            'started_at'   => $this->startedAt,
            'event'        => $this->event,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function buildTemplateData(Device $device, ?Alert $alert): array
    {
        $area = $device->area;

        // Threshold value comes from SensorConfiguration, not DeviceConfiguration
        $sensorConfig  = $alert?->deviceSensor?->currentConfiguration;
        $thresholdKey  = $alert?->threshold_breached;                       // e.g. 'max_critical'
        $thresholdVal  = $sensorConfig && $thresholdKey
            ? $sensorConfig->{$thresholdKey}
            : null;

        return [
            'severity'       => $this->severity,
            'code'           => $device->device_code,
            'device_code'    => $device->device_code,
            'device_name'    => $device->device_name ?? $device->device_code,
            'location'       => $area?->hub?->location?->name  ?? 'N/A',
            'hub'            => $area?->hub?->name             ?? 'N/A',
            'area'           => $area?->name                   ?? 'N/A',
            'value'          => $this->triggerValue,
            'threshold'      => $thresholdVal                  ?? 'N/A',
            'threshold_type' => $thresholdKey                  ?? 'N/A',
            'sensor_type'    => $this->sensorType,
            'datetime'       => $this->startedAt,
            'alert_message'  => $this->reason,
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
            $area->hub->location->name ?? null,
            $area->hub->name           ?? null,
            $area->name,
        ]));
    }
}
