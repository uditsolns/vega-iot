<?php

namespace App\Services\Notification\DTOs;

use App\Models\Alert;
use App\Models\Device;
use App\Models\User;
use App\Models\Area;

readonly class NotificationPayload
{
    public function __construct(
        public int $alertId,
        public int $userId,
        public string $channel,
        public string $event,
        public Alert $alert,
        public User $user,
        public Device $device,
        public ?Area $area,
        public array $data = [],
    ) {}

    /**
     * Create a NotificationPayload from IDs
     */
    public static function fromIds(
        int $alertId,
        int $userId,
        string $channel,
        string $event,
    ): self {
        $alert = Alert::with([
            "device.area.hub.location",
            "device.currentConfiguration",
        ])->findOrFail($alertId);
        $user = User::findOrFail($userId);
        $device = $alert->device;
        $area = $device->area;

        return new self(
            alertId: $alertId,
            userId: $userId,
            channel: $channel,
            event: $event,
            alert: $alert,
            user: $user,
            device: $device,
            area: $area,
            data: self::buildData($alert, $user, $device, $area),
        );
    }

    /**
     * Build data array for template replacements
     */
    private static function buildData(
        Alert $alert,
        User $user,
        Device $device,
        ?Area $area,
    ): array {
        // Extract individual location hierarchy names
        $location = $area?->hub?->location?->name ?? "N/A";
        $hub = $area?->hub?->name ?? "N/A";
        $areaName = $area?->name ?? "N/A";

        // Get threshold value from device configuration
        $thresholdValue = self::getThresholdValue($alert, $device);

        return [
            "name" => $user->first_name,
            "full_name" => "{$user->first_name} {$user->last_name}",
            "severity" => $alert->severity->value,
            "code" => $device->device_code,
            "device_code" => $device->device_code,
            "device_name" => $device->device_name ?? $device->device_code,
            "location" => $location,
            "hub" => $hub,
            "area" => $areaName,
            "value" => $alert->trigger_value ?? "N/A",
            "threshold" => $thresholdValue,
            "threshold_type" => $alert->threshold_breached ?? "N/A",
            "sensor_type" => $alert->type->value,
            "datetime" => $alert->started_at->format("Y-m-d H:i:s"),
            "alert_message" => $alert->reason,
        ];
    }

    /**
     * Get the threshold value that was breached
     */
    private static function getThresholdValue(
        Alert $alert,
        Device $device,
    ): string {
        $config = $device->currentConfiguration;

        if (!$config || !$alert->threshold_breached) {
            return "N/A";
        }

        // Map threshold_breached field to actual config field
        $thresholdField = $alert->threshold_breached;

        // Get the threshold value from configuration
        if (
            property_exists($config, $thresholdField) &&
            isset($config->$thresholdField)
        ) {
            return (string) $config->$thresholdField;
        }

        return "N/A";
    }

    /**
     * Replace template placeholders with actual values
     */
    public function replaceTemplate(string $template): string
    {
        $replacements = [];
        foreach ($this->data as $key => $value) {
            $replacements["{{$key}}"] = $value;
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template,
        );
    }
}
