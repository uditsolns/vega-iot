<?php

namespace App\Vendor;

use App\Enums\Vendor;

class TZoneAdapter extends AbstractVendorAdapter
{
    private const PASSWORD = '000000';

    public function getVendorName(): string
    {
        return Vendor::TZone->value;
    }

    public function buildConfigCommand(array $config): string
    {
        // TZone uses sequential commands; return the first applicable command.
        // The gateway state machine (Module 2) sends these one at a time.
        if (isset($config['sending_interval'])) {
            // Command 070: reporting interval count
            return sprintf('*%s,070,%d#', self::PASSWORD, $config['sending_interval']);
        }

        if (isset($config['recording_interval'])) {
            return sprintf('*%s,070,%d#', self::PASSWORD, $config['recording_interval']);
        }

        return '';
    }
}
