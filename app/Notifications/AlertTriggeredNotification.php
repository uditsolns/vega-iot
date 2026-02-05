<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Channels\MsgClubSmsChannel;
use App\Channels\MsgClubVoiceChannel;
use App\Enums\AlertSeverity;
use App\Models\Alert;
use App\Notifications\Messages\MsgClubEmailMessage;
use App\Notifications\Messages\MsgClubSmsMessage;
use App\Notifications\Messages\MsgClubVoiceMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AlertTriggeredNotification extends Notification implements ShouldQueue
{
    use Queueable;

//    public int $tries = 3;
//    public array $backoff = [10, 30, 60];
//    public int $timeout = 60;

    public function __construct(
        public readonly Alert $alert,
        public readonly string $event = 'triggered'
    ) {
        // Set queue
        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    /**
     * Get the notification's delivery channels
     */
    public function via($notifiable): array
    {
        $area = $this->alert->device->area;

        if (!$area) {
            return [];
        }

        $channels = [];

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
        if ($this->alert->severity === AlertSeverity::Critical &&
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
        return (new MsgClubSmsMessage)
            ->template('alert_triggered')
            ->data([
                'severity' => ucfirst($this->alert->severity->value),
                'code' => $this->alert->device->device_code,
                'device_code' => $this->alert->device->device_code,
                'value' => number_format($this->alert->trigger_value, 1),
                'location' => $this->getLocationPath(),
                'area' => $this->alert->device->area?->name ?? 'N/A',
            ]);
    }

    /**
     * Get the email representation
     */
    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $severity = ucfirst($this->alert->severity->value);
        $deviceCode = $this->alert->device->device_code;

        return (new MsgClubEmailMessage)
            ->subject("{$severity} Alert: Device {$deviceCode}")
            ->view('emails.alerts.triggered', [
                'alert' => $this->alert,
                'user' => $notifiable,
                'device' => $this->alert->device,
                'area' => $this->alert->device->area,
                'data' => $this->getTemplateData(),
            ]);
    }

    /**
     * Get the voice representation
     */
    public function toMsgClubVoice($notifiable): MsgClubVoiceMessage
    {
        return (new MsgClubVoiceMessage)
            ->template('alert_triggered')
            ->data([
                'severity' => ucfirst($this->alert->severity->value),
                'code' => $this->alert->device->device_code,
                'value' => number_format($this->alert->trigger_value, 1),
                'location' => $this->getLocationPath(),
            ]);
    }

    /**
     * Get location path for the alert
     */
    protected function getLocationPath(): string
    {
        $device = $this->alert->device;
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
    protected function getTemplateData(): array
    {
        $device = $this->alert->device;
        $area = $device->area;
        $config = $device->currentConfiguration;

        return [
            'severity' => $this->alert->severity->value,
            'code' => $device->device_code,
            'device_code' => $device->device_code,
            'device_name' => $device->device_name ?? $device->device_code,
            'location' => $area?->hub?->location?->name ?? 'N/A',
            'hub' => $area?->hub?->name ?? 'N/A',
            'area' => $area?->name ?? 'N/A',
            'value' => $this->alert->trigger_value,
            'threshold' => $config?->{$this->alert->threshold_breached} ?? 'N/A',
            'threshold_type' => $this->alert->threshold_breached,
            'sensor_type' => $this->alert->type->value,
            'datetime' => $this->alert->started_at->format('Y-m-d H:i:s'),
            'alert_message' => $this->alert->reason,
        ];
    }
}
