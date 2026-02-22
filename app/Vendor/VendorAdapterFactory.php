<?php

namespace App\Vendor;

use App\Enums\Vendor;
use App\Models\Device;

class VendorAdapterFactory
{
    private static array $adapters = [];

    public static function make(Vendor $vendor): VendorAdapterInterface
    {
        $class = match ($vendor) {
            Vendor::Zion => ZionAdapter::class,
            Vendor::TZone => TZoneAdapter::class,
            Vendor::Ideabyte => IdeabyteAdapter::class,
            Vendor::Aliter => AliterAdapter::class,
            Vendor::Sunsui => SunsuiAdapter::class,
        };

        return self::$adapters[$vendor->value] ??= new $class();
    }

    public static function makeForDevice(Device $device): VendorAdapterInterface
    {
        $device->loadMissing('deviceModel');
        return self::make($device->deviceModel->vendor);
    }
}
