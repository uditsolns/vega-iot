<?php

namespace App\Vendor;

use App\Enums\Vendor;

class AliterAdapter extends AbstractVendorAdapter
{
    public function getVendorName(): string
    {
        return Vendor::Aliter->value;
    }

    /**
     * @throws \RuntimeException Aliter config protocol not yet documented.
     */
    public function buildConfigCommand(array $config): string
    {
        throw new \RuntimeException(
            'Aliter configuration protocol is not documented. Provide protocol details to implement.'
        );
    }
}
