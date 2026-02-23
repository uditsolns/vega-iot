<?php

namespace App\Http\Controllers\Gateway;

use App\Enums\ConfigRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceConfigurationRequest;
use App\Services\Reading\ReadingIngestionService;
use App\Vendor\Adapters\TZoneAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound HTTP POST requests from TZone TT19 loggers.
 *
 * URL: POST /gateway/tzone
 *
 * msgtype 1 → time-sync  → return server time
 * msgtype 3 → sensor data → ingest + optional config command
 * msgtype 4 → config ack  → confirm pending request + optional next command
 */
class TZoneGatewayController extends Controller
{
    public function __construct(
        private readonly TZoneAdapter            $adapter,
        private readonly ReadingIngestionService  $ingestion,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $body    = $request->json()->all();
        $msgtype = $body['msgtype'] ?? 0;

        // ── Time sync ────────────────────────────────────────────────────────
        if ($msgtype === 1) {
            return response()->json([
                'sta'       => 0,
                'data'      => ['servertime' => $this->formatServerTime()],
                'error'     => '',
                'errorcode' => '',
            ]);
        }

        $serial = $body['imei'] ?? $body['sn'] ?? null;
        if (!$serial) {
            return response()->json(['sta' => 1, 'error' => 'Missing device identifier'], 400);
        }

        $device = Device::where('device_uid', $serial)
            ->orWhere('device_code', $serial)
            ->with(['deviceModel', 'sensors.sensorType'])
            ->first();

        if (!$device) {
            Log::warning('[TZone] Unknown device', ['serial' => $serial]);
            return response()->json($this->adapter->buildSuccessResponse(null, ['sn' => $serial]));
        }

        // ── Config ack (msgtype 4) ────────────────────────────────────────────
        if ($msgtype === 4) {
            $ackResult = $this->adapter->parseConfigAck($request);
            if ($ackResult === 'confirmed') {
                $this->markConfigConfirmed($device);
            } elseif ($ackResult === 'failed') {
                $this->markConfigFailed($device, 'Device returned ERROR');
            }
        }

        // ── Sensor data (msgtype 3) ───────────────────────────────────────────
        if ($msgtype === 3) {
            try {
                $batches = $this->adapter->parseReadings($request);
                if (!empty($batches)) {
                    $this->ingestion->ingestBatches($device, $batches);
                    $device->update(['status' => 'online']);
                }
            } catch (\Throwable $e) {
                Log::error('[TZone] Ingestion failed', [
                    'serial' => $serial,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // ── Pending config command ────────────────────────────────────────────
        $configCommand = $this->getPendingCommand($device);

        return response()->json(
            $this->adapter->buildSuccessResponse($configCommand, ['sn' => $body['sn'] ?? 1])
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function markConfigConfirmed(Device $device): void
    {
        DeviceConfigurationRequest::where('device_id', $device->id)
            ->where('status', ConfigRequestStatus::Sent)
            ->latest()
            ->first()
            ?->markConfirmed();
    }

    private function markConfigFailed(Device $device, string $reason): void
    {
        DeviceConfigurationRequest::where('device_id', $device->id)
            ->where('status', ConfigRequestStatus::Sent)
            ->latest()
            ->first()
            ?->markFailed($reason);
    }

    private function getPendingCommand(Device $device): ?string
    {
        $pending = DeviceConfigurationRequest::where('device_id', $device->id)
            ->where('status', ConfigRequestStatus::Pending)
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->first();

        if (!$pending) {
            return null;
        }

        try {
            $command = $this->adapter->buildConfigCommand($pending->requested_config);
            $pending->markSent();
            return $command;
        } catch (\RuntimeException $e) {
            Log::warning('[TZone] Could not build config command', ['error' => $e->getMessage()]);
            $pending->markFailed($e->getMessage());
            return null;
        }
    }

    private function formatServerTime(): string
    {
        return now()->utc()->format('Y/m/d H:i:s');
    }
}
