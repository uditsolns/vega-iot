<?php

namespace App\Vendor;

use App\Enums\Vendor;

class IdeabyteAdapter extends AbstractVendorAdapter
{
    public function getVendorName(): string
    {
        return Vendor::Ideabyte->value;
    }

    /**
     * Ideabyte uses pull-based config — returns JSON that the device GETs.
     */
    public function buildConfigCommand(array $config): string
    {
        $payload = [];

        if (isset($config['recording_interval'])) {
            // Ideabyte expects seconds in the frequency field
            $payload['frequency'] = $config['recording_interval'] * 60;
        }

        if (isset($config['sending_interval'])) {
            $payload['sending_interval'] = $config['sending_interval'];
        }

        if (isset($config['timezone_offset_minutes'])) {
            $payload['tz_offset'] = $config['timezone_offset_minutes'];
        }

        return json_encode($payload);
    }
}
