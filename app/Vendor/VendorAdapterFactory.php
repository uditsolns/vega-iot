<?php

namespace App\Vendor;

use App\Enums\Vendor;
use App\Models\Device;
use App\Vendor\Adapters\AliterAdapter;
use App\Vendor\Adapters\IdeabyteAdapter;
use App\Vendor\Adapters\SunsuiAdapter;
use App\Vendor\Adapters\TZoneAdapter;
use App\Vendor\Adapters\ZionAdapter;
use App\Vendor\Contracts\VendorAdapterInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

readonly class VendorAdapterFactory
{
    /**
     * @throws BindingResolutionException
     */
    public function make(Vendor $vendor): VendorAdapterInterface
    {
        return match ($vendor) {
            Vendor::Zion     => app()->make(ZionAdapter::class),
            Vendor::TZone    => app()->make(TZoneAdapter::class),
            Vendor::Ideabyte => app()->make(IdeabyteAdapter::class),
            Vendor::Aliter   => app()->make(AliterAdapter::class),
            Vendor::Sunsui   => app()->make(SunsuiAdapter::class),
        };
    }

    /**
     * @throws BindingResolutionException
     */
    public function makeForDevice(Device $device): VendorAdapterInterface
    {
        $device->loadMissing('deviceModel');

        if (!$device->deviceModel) {
            throw new \RuntimeException("Device [{$device->device_code}] has no associated device model.");
        }

        return $this->make($device->deviceModel->vendor);
    }
}
