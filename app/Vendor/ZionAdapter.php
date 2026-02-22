<?php

namespace App\Vendor;

use App\Enums\Vendor;

class ZionAdapter extends AbstractVendorAdapter
{
    public function getVendorName(): string
    {
        return Vendor::Zion->value;
    }

    public function buildConfigCommand(array $config): string
    {
        $parts = [];

        if (isset($config['recording_interval'])) {
            $parts[] = 'COLLECTION_INTERVAL:' . $config['recording_interval'];
        }

        if (isset($config['sending_interval'])) {
            $parts[] = 'SENDING_INTERVAL:' . $config['sending_interval'];
        }

        if (isset($config['timezone_offset_minutes'])) {
            $parts[] = 'TZ_OFFSET:' . $config['timezone_offset_minutes'];
        }

        if (isset($config['wifi_ssid'])) {
            $mode = strtoupper($config['wifi_mode'] ?? 'WPA2');
            $parts[] = "WPA2_MODE:PERSONAL;WPA2_SSID:{$config['wifi_ssid']};WPA2_PASSWORD:{$config['wifi_password']}";
        }

        return implode(';', $parts) . ';';
    }
}
