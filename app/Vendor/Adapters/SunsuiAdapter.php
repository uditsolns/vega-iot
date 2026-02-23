<?php

namespace App\Vendor\Adapters;

use App\Vendor\Contracts\VendorAdapterInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Adapter for Sunsui 4-channel and 8-channel configurable loggers.
 *
 * Sunsui transmits sensor values as ch1…ch8 (or channel_1…channel_N).
 * Slot numbers map 1:1 to channel numbers.
 *
 * Supported models:
 *   4CH – Temp, Humidity, Air Quality, Sound (max 4 slots)
 *   8CH – Temp, Humidity (max 8 slots)
 */
class SunsuiAdapter implements VendorAdapterInterface
{
    public function parseReadings(Request $request): array
    {
        $body = $request->json()->all();

        if (empty($body)) {
            return [];
        }

        $ts  = $body['timestamp'] ?? $body['ts'] ?? now()->timestamp;
        $recordedAt = Carbon::createFromTimestamp((int) $ts);

        $battery    = $body['battery']      ?? $body['batt']  ?? null;
        $firmware   = $body['firmware']     ?? $body['fw']    ?? null;
        $signal     = $body['signal']       ?? $body['rssi']  ?? null;
        $channels   = $body['channels']     ?? [];

        $readings = [];

        // Try explicit channels array: [{'slot': 1, 'value': 23.5}, ...]
        if (!empty($channels) && isset($channels[0]['slot'])) {
            foreach ($channels as $ch) {
                $readings[] = [
                    'slot_number' => (int) $ch['slot'],
                    'value'       => $ch['value'] !== null ? (float) $ch['value'] : null,
                    'metadata'    => [],
                ];
            }
        } else {
            // Fallback: look for ch1…ch8 or channel_1…channel_8 keys
            for ($i = 1; $i <= 8; $i++) {
                $value = $body["ch{$i}"] ?? $body["channel_{$i}"] ?? null;
                if ($value !== null) {
                    $readings[] = [
                        'slot_number' => $i,
                        'value'       => (float) $value,
                        'metadata'    => [],
                    ];
                }
            }
        }

        if (empty($readings)) {
            return [];
        }

        return [[
            'recorded_at'      => $recordedAt,
            'firmware_version' => $firmware,
            'battery_voltage'  => $battery !== null ? (float) $battery : null,
            'signal_strength'  => $signal !== null ? (int) $signal : null,
            'readings'         => $readings,
        ]];
    }

    public function buildConfigCommand(array $config): string
    {
        // Sunsui configuration is vendor-specific; extend as protocol docs arrive
        throw new \RuntimeException('Sunsui OTA configuration not yet implemented.');
    }

    public function buildSuccessResponse(?string $configCommand = null, array $context = []): array
    {
        return ['status' => 'ok', 'ts' => now()->timestamp];
    }

    public function parseConfigAck(Request $request): ?string
    {
        return null;
    }
}
