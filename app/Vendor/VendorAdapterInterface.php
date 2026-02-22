<?php

namespace App\Vendor;

interface VendorAdapterInterface
{
    public function getVendorName(): string;

    /**
     * Build vendor-specific configuration command string.
     * Keys: recording_interval (min), sending_interval (min),
     *       wifi_ssid, wifi_password, wifi_mode, timezone_offset_minutes
     */
    public function buildConfigCommand(array $config): string;

    /**
     * Extract device UID from incoming payload. (Module 2)
     */
    public function extractDeviceIdentifier(mixed $payload): ?string;

    /**
     * Normalize vendor payload into standard format. (Module 2)
     * Returns: ['device_uid' => string, 'readings' => [...]]
     */
    public function parseDataPayload(array $payload): array;

    /**
     * Parse config confirmation from device. (Module 2)
     * Returns: ['confirmed' => bool, 'applied_config' => array]
     */
    public function parseConfigConfirmation(array $payload): array;
}
