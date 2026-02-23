<?php

namespace App\Vendor\Contracts;

use Illuminate\Http\Request;

interface VendorAdapterInterface
{
    /**
     * Parse vendor HTTP request into normalized reading batches.
     *
     * Returns an array of batches. Each batch represents one point in time
     * and maps to one call of ReadingIngestionService::ingest().
     *
     * Each batch shape:
     * [
     *   'recorded_at'      => \Carbon\Carbon,
     *   'firmware_version' => string|null,
     *   'battery_voltage'  => float|null,    // volts
     *   'signal_strength'  => int|null,      // dBm or RSSI
     *   'readings'         => [
     *       ['slot_number' => int, 'value' => float|null, 'metadata' => array],
     *       ...
     *   ],
     * ]
     *
     * Returns an empty array when the request carries no sensor data
     * (e.g. time-sync pings, config-ack messages).
     */
    public function parseReadings(Request $request): array;

    /**
     * Build a vendor-specific command string from a config array.
     *
     * Throws \RuntimeException when the vendor/model does not support
     * the requested configuration key(s).
     */
    public function buildConfigCommand(array $config): string;

    /**
     * Build the HTTP response payload to return to the device.
     *
     * @param  string|null  $configCommand  Vendor command to push, or null
     * @param  array        $context        Extra context (timestamp, etc.)
     */
    public function buildSuccessResponse(?string $configCommand = null, array $context = []): array;

    /**
     * Determine whether this request is a config acknowledgement from the device.
     * Returns 'confirmed', 'failed', or null (not an ack).
     */
    public function parseConfigAck(Request $request): ?string;
}
