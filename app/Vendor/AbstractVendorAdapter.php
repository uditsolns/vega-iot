<?php

namespace App\Vendor;

abstract class AbstractVendorAdapter implements VendorAdapterInterface
{
    public function extractDeviceIdentifier(mixed $payload): ?string
    {
        throw new \RuntimeException(static::class . '::extractDeviceIdentifier() not implemented — Module 2');
    }

    public function parseDataPayload(array $payload): array
    {
        throw new \RuntimeException(static::class . '::parseDataPayload() not implemented — Module 2');
    }

    public function parseConfigConfirmation(array $payload): array
    {
        throw new \RuntimeException(static::class . '::parseConfigConfirmation() not implemented — Module 2');
    }
}
