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

    /**
     * Store only IDs and primitive data, not Eloquent models
     */
    public function __construct(
        public readonly int $alertId,
        public readonly int $deviceId,
        public readonly string $severity,
        public readonly string $sensorType,
        public readonly float $triggerValue,
        public readonly string $reason,
        public readonly string $startedAt,
        public readonly string $event = 'triggered'
    ) {
//        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    /**
     * Get the notification's delivery channels
     */
    public function via($notifiable): array
    {
        $channels = ['database']; // Always store in database

        // Get area configuration - load fresh from database
        $device = Device::with('area')->find($this->deviceId);
        $area = $device?->area;

        if (!$area) {
            return $channels;
        }

        // Check if email is enabled
        if ($area->alert_email_enabled &&
            config('notifications.channels.email.enabled', true)) {
            $channels[] = MsgClubEmailChannel::class;
        }

        // Check if SMS is enabled
        if ($area->alert_sms_enabled &&
            config('notifications.channels.sms.enabled', true)) {
            $channels[] = MsgClubSmsChannel::class;
        }

        // Voice only for critical alerts
        if ($this->severity === AlertSeverity::Critical->value &&
            $area->alert_voice_enabled &&
            config('notifications.channels.voice.enabled', false)) {
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
                'severity' => ucfirst($this->severity),
                'code' => $device->device_code,
                'device_code' => $device->device_code,
                'value' => number_format($this->triggerValue, 1),
                'location' => $this->getLocationPath($device),
                'area' => $device->area?->name ?? 'N/A',
            ]);
    }

    /**
     * Get the email representation
     */
    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $device = Device::with([
            'area.hub.location',
            'currentConfiguration'
        ])->find($this->deviceId);

        $alert = Alert::find($this->alertId);

        return (new MsgClubEmailMessage)
            ->subject(ucfirst($this->severity) . " Alert: Device {$device->device_code}")
            ->view('emails.alerts.triggered', [
                'alert' => $alert,
                'user' => $notifiable,
                'device' => $device,
                'area' => $device->area,
                'data' => $this->getTemplateData($device, $alert),
            ]);
    }

    /**
     * Get the voice representation
     */
    public function toMsgClubVoice($notifiable): MsgClubVoiceMessage
    {
        $device = Device::find($this->deviceId);

        return (new MsgClubVoiceMessage)
            ->template('alert_triggered')
            ->data([
                'severity' => ucfirst($this->severity),
                'code' => $device->device_code,
                'value' => number_format($this->triggerValue, 1),
                'location' => $this->getLocationPath($device),
            ]);
    }

    /**
     * Get the array representation for database storage
     */
    public function toArray($notifiable): array
    {
        $device = Device::with('area.hub.location')->find($this->deviceId);

        return [
            'alert_id' => $this->alertId,
            'device_id' => $this->deviceId,
            'device_code' => $device->device_code,
            'device_name' => $device->device_name ?? $device->device_code,
            'severity' => $this->severity,
            'sensor_type' => $this->sensorType,
            'trigger_value' => $this->triggerValue,
            'reason' => $this->reason,
            'location' => $this->getLocationPath($device),
            'started_at' => $this->startedAt,
            'event' => $this->event,
        ];
    }

    /**
     * Get location path for the alert
     */
    protected function getLocationPath($device): string
    {
        $area = $device->area;

        if (!$area) {
            return 'Unassigned';
        }

        $location = $area->hub->location->name ?? 'N/A';
        $hub = $area->hub->name ?? 'N/A';
        $areaName = $area->name ?? 'N/A';

        return "{$location} > {$hub} > {$areaName}";
    }

    /**
     * Get template data for emails
     */
    protected function getTemplateData($device, $alert): array
    {
        $area = $device->area;
        $config = $device->currentConfiguration;

        return [
            'severity' => $this->severity,
            'code' => $device->device_code,
            'device_code' => $device->device_code,
            'device_name' => $device->device_name ?? $device->device_code,
            'location' => $area?->hub?->location?->name ?? 'N/A',
            'hub' => $area?->hub?->name ?? 'N/A',
            'area' => $area?->name ?? 'N/A',
            'value' => $this->triggerValue,
            'threshold' => $config?->{$alert->threshold_breached} ?? 'N/A',
            'threshold_type' => $alert->threshold_breached,
            'sensor_type' => $this->sensorType,
            'datetime' => $this->startedAt,
            'alert_message' => $this->reason,
        ];
    }
}
