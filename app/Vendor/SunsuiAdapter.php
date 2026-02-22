<?php

namespace App\Vendor;

use App\Enums\Vendor;

class SunsuiAdapter extends AbstractVendorAdapter
{
    public function getVendorName(): string
    {
        return Vendor::Sunsui->value;
    }

    /**
     * Sunsui uses MQTT JSON RPC — returns JSON payload.
     */
    public function buildConfigCommand(array $config): string
    {
        $payload = ['type' => 'config'];

        if (isset($config['recording_interval'])) {
            $payload['record_interval'] = $config['recording_interval'];
        }

        if (isset($config['sending_interval'])) {
            $payload['send_interval'] = $config['sending_interval'];
        }

        return json_encode($payload);
    }
}
