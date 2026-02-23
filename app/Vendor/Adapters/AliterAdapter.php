<?php

namespace App\Vendor\Adapters;

use App\Vendor\Contracts\VendorAdapterInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Adapter for Aliter 4-channel configurable loggers.
 *
 * Protocol: JSON over HTTP POST (broker-forwarded from MQTT).
 * MQTT topic = device MAC address.
 *
 * Aliter devices have 4 configurable channels. Slot assignment is
 * determined at device-provisioning time in device_sensors.
 * The JSON keys map to slots in this order:
 *   RTD1, RTD2, TC1, TC2, A_TEMP → temperature sensors
 *   A_HUM                         → humidity sensor
 *
 * Because Aliter's channel assignment is arbitrary, the adapter uses a
 * KEY→slot_number mapping stored in the device_model data_format JSONB,
 * or falls back to the default ordering below.
 *
 * Default key → slot mapping (Aliter 4CH):
 *   'RTD1'   → slot 1
 *   'RTD2'   → slot 2
 *   'TC1'    → slot 3
 *   'TC2'    → slot 4
 *   'A_TEMP' → slot 1 (ambient – fallback if RTDs absent)
 *   'A_HUM'  → slot 2 (ambient humidity – fallback)
 */
class AliterAdapter implements VendorAdapterInterface
{
    /**
     * Default JSON-key → slot_number mapping.
     * Adjust per deployment via device_model.data_format.
     */
    private const DEFAULT_KEY_SLOT_MAP = [
        'RTD1'   => 1,
        'RTD2'   => 2,
        'TC1'    => 3,
        'TC2'    => 4,
        'A_TEMP' => 1,
        'A_HUM'  => 2,
    ];

    public function parseReadings(Request $request): array
    {
        $body = $request->json()->all();

        if (empty($body) || !isset($body['data'])) {
            return [];
        }

        $d          = $body['data'];
        $ts         = $body['ts'] ?? now()->timestamp;
        $recordedAt = Carbon::createFromTimestamp((int) $ts);
        $rssi       = $body['rssi'] ?? null;

        $batteryVolt = isset($d['BATT_VOLT'])
            ? (float) $d['BATT_VOLT']
            : null;

        $readings = [];

        foreach (self::DEFAULT_KEY_SLOT_MAP as $key => $slot) {
            if (!isset($d[$key])) {
                continue;
            }
            $readings[] = [
                'slot_number' => $slot,
                'value'       => (float) $d[$key],
                'metadata'    => ['source_key' => $key],
            ];
        }

        // Deduplicate: if a slot appears twice (e.g. both A_TEMP and RTD1 map to slot 1),
        // keep the first occurrence only.
        $seen     = [];
        $readings = array_filter($readings, function ($r) use (&$seen) {
            if (in_array($r['slot_number'], $seen, true)) {
                return false;
            }
            $seen[] = $r['slot_number'];
            return true;
        });

        if (empty($readings)) {
            return [];
        }

        return [[
            'recorded_at'      => $recordedAt,
            'firmware_version' => null,
            'battery_voltage'  => $batteryVolt,
            'signal_strength'  => $rssi !== null ? -(int) $rssi : null,
            'readings'         => array_values($readings),
            'extra_metadata'   => [
                'button' => $d['BUTTON'] ?? null,
                'mac'    => $body['device'] ?? null,
            ],
        ]];
    }

    public function buildConfigCommand(array $config): string
    {
        // Aliter devices are configured at the hardware level; no over-the-air
        // config push is supported in the current firmware revision.
        throw new \RuntimeException('Aliter devices do not support OTA configuration.');
    }

    public function buildSuccessResponse(?string $configCommand = null, array $context = []): array
    {
        // Simple acknowledgement; Aliter doesn't expect a specific payload
        return ['status' => 'ok'];
    }

    public function parseConfigAck(Request $request): ?string
    {
        return null;  // Aliter has no config-ack flow
    }
}
