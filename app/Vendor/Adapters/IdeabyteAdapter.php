<?php

namespace App\Vendor\Adapters;

use App\Vendor\Contracts\VendorAdapterInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Adapter for Ideabyte data loggers.
 *
 * Protocol reference: API Documentation v1.3_vega.pdf
 *
 * Live upload  → single JSON object
 * History upload → JSON array of objects
 *
 * Slot mapping (matches DeviceModelSeeder for vendor=ideabyte, model=DT-TH):
 *   Slot 1 → temperature  (temp or temp1)
 *   Slot 2 → temperature  (temp2 – dual-temp model only)
 *   Slot 3 → humidity     (hum)
 *   Slot 4 → humidity     (hum2 – if present)
 *
 * Response (v1.3):
 *   {"message":"success","Id":"...","frequency":1,"config":0|1}
 *   When config=1, device does GET /<devID> to fetch new thresholds.
 *   We do NOT send thresholds to devices; config is always 0.
 */
class IdeabyteAdapter implements VendorAdapterInterface
{
    public function parseReadings(Request $request): array
    {
        $body = $request->json()->all();

        if (empty($body)) {
            return [];
        }

        // History = array, Live = single object
        $packets = isset($body[0]) ? $body : [$body];

        $batches = [];

        foreach ($packets as $packet) {
            $batch = $this->parsePacket($packet);
            if ($batch !== null) {
                $batches[] = $batch;
            }
        }

        return $batches;
    }

    public function buildConfigCommand(array $config): string
    {
        // Ideabyte devices do not support push-based config commands.
        // They poll GET /<devID> and receive threshold JSON.
        // We flag `config=1` in the response to trigger a poll.
        throw new \RuntimeException('Ideabyte devices use pull-based config; no push command needed.');
    }

    public function buildSuccessResponse(?string $configCommand = null, array $context = []): array
    {
        $id        = $context['device_code'] ?? '';
        $frequency = $context['frequency'] ?? 1;

        return [
            'message'   => 'success',
            'Id'        => $id,
            'frequency' => $frequency,
            'config'    => 0,   // We never push thresholds to devices per architecture
        ];
    }

    public function parseConfigAck(Request $request): ?string
    {
        // Ideabyte doesn't send ack messages; config is pull-based
        return null;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function parsePacket(array $packet): ?array
    {
        $tStamp = $packet['tStamp'] ?? null;
        if (!$tStamp) {
            return null;
        }

        $recordedAt = Carbon::createFromTimestamp((int) $tStamp);
        $battery    = isset($packet['batt']) ? (float) $packet['batt'] : null;   // percentage
        $readings   = [];

        // Slot 1 – primary temperature (temp or temp1)
        $temp1 = $packet['temp'] ?? $packet['temp1'] ?? null;
        if ($temp1 !== null) {
            $readings[] = [
                'slot_number' => 1,
                'value'       => (float) $temp1,
                'metadata'    => [],
            ];
        }

        // Slot 2 – secondary temperature
        if (isset($packet['temp2'])) {
            $readings[] = [
                'slot_number' => 2,
                'value'       => (float) $packet['temp2'],
                'metadata'    => [],
            ];
        }

        // Slot 3 – primary humidity
        if (isset($packet['hum'])) {
            $readings[] = [
                'slot_number' => 3,
                'value'       => (float) $packet['hum'],
                'metadata'    => [],
            ];
        }

        // Slot 4 – secondary humidity (future extension)
        if (isset($packet['hum2'])) {
            $readings[] = [
                'slot_number' => 4,
                'value'       => (float) $packet['hum2'],
                'metadata'    => [],
            ];
        }

        if (empty($readings)) {
            return null;
        }

        return [
            'recorded_at'      => $recordedAt,
            'firmware_version' => null,
            'battery_voltage'  => null,
            'signal_strength'  => null,
            'readings'         => $readings,
            'extra_metadata'   => ['battery_pct' => $battery],
        ];
    }
}
