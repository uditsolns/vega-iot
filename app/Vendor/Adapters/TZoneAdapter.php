<?php

namespace App\Vendor\Adapters;

use App\Vendor\Contracts\VendorAdapterInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Adapter for TZone TT19 loggers.
 *
 * Protocol: proprietary JSON over HTTP POST (msgtype 1/3/4).
 *
 * Slot mapping (matches DeviceModelSeeder for vendor=tzone, model=TT19):
 *   Slot 1 → temperature  (d.temp  °C)
 *   Slot 2 → temperature  (d.temp_ch2 °C – secondary probe)
 *   Slot 3 → humidity     (d.humi  %)
 *   Slot 4 → gps          (d.gps latitude/longitude → POINT)
 *   Slot 5 → lux          (d.light lux)
 *   Slot 6 → vibration    (d.shock g-force)
 *
 * Config commands use TZone's proprietary * # format.
 */
class TZoneAdapter implements VendorAdapterInterface
{
    public function parseReadings(Request $request): array
    {
        $body = $request->json()->all();

        // Only msgtype 3 carries sensor data
        if (($body['msgtype'] ?? 0) !== 3) {
            return [];
        }

        $d   = $body['data'] ?? [];
        $gps = $d['gps']   ?? [];
        $gsm = $d['gsm']   ?? [];

        $recordedAt = isset($body['rtc'])
            ? Carbon::parse($body['rtc'])
            : now();

        $battery = isset($d['bat'])
            ? (float) $d['bat']   // battery percentage – store in metadata
            : null;

        $signalStrength = isset($gsm['csq'])
            ? $this->csqToDbm((int) $gsm['csq'])
            : null;

        $readings = [];

        // Slot 1 – primary temperature
        if (isset($d['temp'])) {
            $readings[] = [
                'slot_number' => 1,
                'value'       => (float) $d['temp'],
                'metadata'    => [],
            ];
        }

        // Slot 2 – secondary temperature channel
        if (isset($d['temp_ch2'])) {
            $readings[] = [
                'slot_number' => 2,
                'value'       => (float) $d['temp_ch2'],
                'metadata'    => [],
            ];
        }

        // Slot 3 – humidity
        if (isset($d['humi'])) {
            $readings[] = [
                'slot_number' => 3,
                'value'       => (float) $d['humi'],
                'metadata'    => [],
            ];
        }

        // Slot 4 – GPS (stored as value_point; value_numeric stays null)
        $lat = $gps['latitude']  ?? null;
        $lng = $gps['longitude'] ?? null;
        if ($lat !== null && $lng !== null && $lat != 0) {
            $readings[] = [
                'slot_number' => 4,
                'value'       => null,
                'metadata'    => ['latitude' => $lat, 'longitude' => $lng, 'gps_point' => true],
            ];
        }

        // Slot 5 – light / lux
        if (isset($d['light'])) {
            $readings[] = [
                'slot_number' => 5,
                'value'       => (float) $d['light'],
                'metadata'    => [],
            ];
        }

        // Slot 6 – vibration / shock
        if (isset($d['shock'])) {
            $readings[] = [
                'slot_number' => 6,
                'value'       => (float) $d['shock'],
                'metadata'    => [],
            ];
        }

        if (empty($readings)) {
            return [];
        }

        return [[
            'recorded_at'      => $recordedAt,
            'firmware_version' => $body['fw'] ?? null,
            'battery_voltage'  => null,
            'signal_strength'  => $signalStrength,
            'readings'         => $readings,
            'extra_metadata'   => ['battery_pct' => $battery, 'imei' => $body['imei'] ?? null],
        ]];
    }

    public function buildConfigCommand(array $config): string
    {
        // TZone uses *000000,CMD,params# format
        $commands = [];

        if (isset($config['recording_interval'], $config['sending_interval'])) {
            $count  = max(1, (int) floor($config['sending_interval'] / $config['recording_interval']));
            $commands[] = "*000000,070,{$count}#";
        }

        if (empty($commands)) {
            throw new \RuntimeException('No supported configuration fields for TZone device.');
        }

        // Return semicolon-joined commands if multiple; device processes them sequentially
        return implode(';', $commands);
    }

    public function buildSuccessResponse(?string $configCommand = null, array $context = []): array
    {
        $sn = $context['sn'] ?? 1;

        $response = [
            'sta'       => 0,
            'data'      => [],
            'error'     => '',
            'errorcode' => '',
        ];

        if ($configCommand !== null) {
            $response['data']['downcmd'] = $configCommand;
        } else {
            $response['data']['ack'] = $sn;
        }

        return $response;
    }

    public function parseConfigAck(Request $request): ?string
    {
        $body = $request->json()->all();

        if (($body['msgtype'] ?? 0) === 4) {
            $sta = $body['data']['resdowncmd']['sta'] ?? null;
            return match ($sta) {
                'OK'    => 'confirmed',
                'ERROR' => 'failed',
                default => null,
            };
        }

        return null;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Convert GSM CSQ (0–31) to approximate dBm. */
    private function csqToDbm(int $csq): int
    {
        if ($csq === 99) {
            return -113; // unknown
        }
        return -113 + (2 * $csq);
    }
}
