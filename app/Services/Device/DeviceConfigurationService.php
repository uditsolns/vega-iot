<?php

namespace App\Services\Device;

use App\Models\Device;
use App\Models\DeviceConfiguration;
use App\Models\DeviceConfigurationRequest;
use App\Models\User;
use App\Vendor\VendorAdapterFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class DeviceConfigurationService
{
    public function getCurrent(Device $device): ?DeviceConfiguration
    {
        return $device->currentConfiguration;
    }

    public function getHistory(Device $device): Collection
    {
        return $device->configurations()->with('updatedBy')->get();
    }

    public function createDefault(Device $device): DeviceConfiguration
    {
        return DeviceConfiguration::create([
            'device_id' => $device->id,
            'recording_interval' => 5,
            'sending_interval' => 5,
            'effective_from' => now(),
        ]);
    }

    public function update(Device $device, array $data, User $user): DeviceConfiguration
    {
        return DB::transaction(function () use ($device, $data, $user) {
            DeviceConfiguration::where('device_id', $device->id)
                ->whereNull('effective_to')
                ->update(['effective_to' => now()]);

            $config = DeviceConfiguration::create([
                ...\Arr::only($data, [
                    'recording_interval',
                    'sending_interval',
                    'wifi_ssid',
                    'wifi_password',
                    'wifi_mode',
                    'timezone_offset_minutes',
                ]),
                'device_id' => $device->id,
                'effective_from' => now(),
                'updated_by' => $user->id,
            ]);

            $this->createConfigRequest($device, $data, $user);

            return $config->fresh();
        });
    }

    private function createConfigRequest(Device $device, array $data, User $user): ?DeviceConfigurationRequest
    {
        $device->loadMissing('deviceModel');
        $adapter = VendorAdapterFactory::makeForDevice($device);

        try {
            $vendorCommand = $adapter->buildConfigCommand($data);
        } catch (\RuntimeException) {
            return null;
        }

        if (empty($vendorCommand)) {
            return null;
        }

        return DeviceConfigurationRequest::create([
            'device_id' => $device->id,
            'requested_config' => $data,
            'vendor_command' => $vendorCommand,
            'status' => \App\Enums\ConfigRequestStatus::Pending,
            'requested_by' => $user->id,
        ]);
    }
}
