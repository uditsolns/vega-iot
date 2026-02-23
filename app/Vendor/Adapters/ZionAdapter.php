<?php

namespace App\Vendor\Adapters;

use App\Vendor\Contracts\VendorAdapterInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Adapter for Zion IoT loggers.
 *
 * Protocol reference: "Logger to Server Integration – Zion IoT"
 *
 * Slot mapping (matches DeviceModelSeeder for vendor=zion, model=Z-TH-Probe):
 *   Slot 1 → temperature  (tI / 100  °C)
 *   Slot 2 → humidity     (hI / 100  %)
 *   Slot 3 → temp_probe   (tP1 / 100 °C; -30100 = probe disconnected → null)
 */
class ZionAdapter implements VendorAdapterInterface
{
    private const PROBE_DISCONNECTED = -30100;

    // ─── VendorAdapterInterface ──────────────────────────────────────────────

    public function parseReadings(Request $request): array
    {
        $body = $request->json()->all();
        $raw  = $body['entity']['data'] ?? null;

        if (empty($raw)) {
            return [];   // time-sync ping – no sensor data
        }

        // Offline packets arrive as array; online as single object
        $packets = is_array($raw) && isset($raw[0]) ? $raw : [$raw];

        $batches = [];

        foreach ($packets as $packet) {
            $batches = array_merge($batches, $this->expandPacket($packet));
        }

        return $batches;
    }

    public function buildConfigCommand(array $config): string
    {
        $parts = [];

        if (isset($config['recording_interval'])) {
            $parts[] = 'COLLECTION_INTERVAL:' . (int) $config['recording_interval'];
        }

        if (isset($config['sending_interval'])) {
            $parts[] = 'SENDING_INTERVAL:' . (int) $config['sending_interval'];
        }

        if (isset($config['timezone_offset_minutes'])) {
            $parts[] = 'TZ_OFFSET:' . (int) $config['timezone_offset_minutes'];
        }

        if (isset($config['wifi_ssid']) || isset($config['wifi_password']) || isset($config['wifi_mode'])) {
            $mode = $config['wifi_mode'] ?? 'PERSONAL';
            $ssid = $this->urlEncode($config['wifi_ssid'] ?? '');
            $pass = $this->urlEncode($config['wifi_password'] ?? '');
            $parts[] = "WPA2_MODE:{$mode}";
            $parts[] = "WPA2_SSID:{$ssid}";
            $parts[] = "WPA2_PASSWORD:{$pass}";
        }

        if (empty($parts)) {
            throw new \RuntimeException('No configurable fields provided for Zion device.');
        }

        return implode(';', $parts) . ';';
    }

    public function buildSuccessResponse(?string $configCommand = null, array $context = []): array
    {
        $timestamp = $context['timestamp'] ?? now()->timestamp;

        $response = [
            'success' => true,
            'data'    => ['timestamp' => $timestamp],
        ];

        if ($configCommand !== null) {
            // Determine if it's a query or an update command by checking format
            // Update commands contain colons (KEY:VALUE), queries are KEY-only
            if (str_contains($configCommand, ':')) {
                $response['data']['config'] = $configCommand;
            } else {
                $response['data']['QUERY'] = $configCommand;
            }
        }

        return $response;
    }

    public function parseConfigAck(Request $request): ?string
    {
        $body = $request->json()->all();
        $data = $body['entity']['data'] ?? [];

        if (!empty($data['config_update']) || !empty($data['query_response'])) {
            // Device responded – treat as confirmed
            return 'confirmed';
        }

        return null;
    }

    // ─── Internal helpers ────────────────────────────────────────────────────

    /**
     * Expand a single Zion packet into one batch per data-point timestamp.
     */
    private function expandPacket(array $packet): array
    {
        $count      = (int) ($packet['count'] ?? 1);
        $lastTs     = (int) ($packet['ldTimestamp'] ?? now()->timestamp);
        $interval   = (int) ($packet['CI'] ?? 10);          // minutes
        $battery    = isset($packet['batteryVoltage'])
            ? round($packet['batteryVoltage'] / 1000, 3)    // mV → V
            : null;
        $firmware   = $packet['fV'] ?? null;

        $tI  = $packet['tI']  ?? [];
        $hI  = $packet['hI']  ?? [];
        $tP1 = $packet['tP1'] ?? [];

        $batches = [];

        for ($i = 0; $i < $count; $i++) {
            // Oldest reading first: offset from last timestamp
            $offsetSeconds = ($count - 1 - $i) * $interval * 60;
            $recordedAt    = Carbon::createFromTimestamp($lastTs - $offsetSeconds);

            $readings = [];

            // Slot 1 – internal temperature
            if (isset($tI[$i])) {
                $readings[] = [
                    'slot_number' => 1,
                    'value'       => round($tI[$i] / 100, 2),
                    'metadata'    => [],
                ];
            }

            // Slot 2 – internal humidity
            if (isset($hI[$i])) {
                $readings[] = [
                    'slot_number' => 2,
                    'value'       => round($hI[$i] / 100, 2),
                    'metadata'    => [],
                ];
            }

            // Slot 3 – external probe
            if (isset($tP1[$i]) && $tP1[$i] !== self::PROBE_DISCONNECTED) {
                $readings[] = [
                    'slot_number' => 3,
                    'value'       => round($tP1[$i] / 100, 2),
                    'metadata'    => [],
                ];
            }

            if (empty($readings)) {
                continue;
            }

            $batches[] = [
                'recorded_at'      => $recordedAt,
                'firmware_version' => $firmware,
                'battery_voltage'  => $battery,
                'signal_strength'  => null,
                'readings'         => $readings,
            ];
        }

        // Event-triggered reading (button press / location event)
        if (!empty($packet['eventTimestamp'])) {
            $eventReadings = [];

            if (isset($packet['eventTempI'])) {
                $eventReadings[] = [
                    'slot_number' => 1,
                    'value'       => round($packet['eventTempI'] / 100, 2),
                    'metadata'    => ['event' => 'button_press'],
                ];
            }

            if (isset($packet['eventHumidI'])) {
                $eventReadings[] = [
                    'slot_number' => 2,
                    'value'       => round($packet['eventHumidI'] / 100, 2),
                    'metadata'    => ['event' => 'button_press'],
                ];
            }

            if (!empty($eventReadings)) {
                $batches[] = [
                    'recorded_at'      => Carbon::createFromTimestamp($packet['eventTimestamp']),
                    'firmware_version' => $firmware,
                    'battery_voltage'  => $battery,
                    'signal_strength'  => null,
                    'readings'         => $eventReadings,
                ];
            }
        }

        return $batches;
    }

    private function urlEncode(string $value): string
    {
        return rawurlencode($value);
    }
}
