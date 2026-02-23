<?php

namespace App\Http\Controllers\Gateway;

use App\Enums\ConfigRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceConfigurationRequest;
use App\Services\Reading\ReadingIngestionService;
use App\Vendor\Adapters\ZionAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound HTTP POST requests from Zion IoT loggers.
 *
 * URL:  POST /gateway/zion
 * Query params: sendingMode, deviceUid (MAC address)
 *
 * Flow:
 *  1. Identify device by deviceUid (query param or body)
 *  2. Parse config-ack if present → confirm pending request
 *  3. Parse sensor readings → ingest
 *  4. Check for pending config request → embed in response
 */
class ZionGatewayController extends Controller
{
    public function __construct(
        private readonly ZionAdapter            $adapter,
        private readonly ReadingIngestionService $ingestion,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $deviceUid = $request->query('deviceUid')
            ?? $request->json('entity.data.deviceUid')
            ?? null;

        if (!$deviceUid) {
            return response()->json(['success' => false, 'error' => 'Missing deviceUid'], 400);
        }

        $device = Device::where('device_uid', $deviceUid)
            ->with(['deviceModel', 'sensors.sensorType'])
            ->first();

        if (!$device) {
            Log::warning('[Zion] Unknown device', ['device_uid' => $deviceUid]);
            // Still return success to prevent device from retrying indefinitely
            return response()->json($this->adapter->buildSuccessResponse());
        }

        // ── Step 1: Config-ack handling ──────────────────────────────────────
        $ackResult = $this->adapter->parseConfigAck($request);
        if ($ackResult === 'confirmed') {
            $this->markConfigConfirmed($device);
        }

        // ── Step 2: Sensor data ingestion ────────────────────────────────────
        try {
            $batches = $this->adapter->parseReadings($request);
            if (!empty($batches)) {
                $this->ingestion->ingestBatches($device, $batches);
                $device->update(['status' => 'online']);
            }
        } catch (\Throwable $e) {
            Log::error('[Zion] Ingestion failed', [
                'device_uid' => $deviceUid,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── Step 3: Pending config command ───────────────────────────────────
        $configCommand = $this->getPendingCommand($device);

        return response()->json(
            $this->adapter->buildSuccessResponse($configCommand, ['timestamp' => now()->timestamp])
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function markConfigConfirmed(Device $device): void
    {
        $request = DeviceConfigurationRequest::where('device_id', $device->id)
            ->where('status', ConfigRequestStatus::Sent)
            ->latest()
            ->first();

        $request?->markConfirmed();
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
            Log::warning('[Zion] Could not build config command', ['error' => $e->getMessage()]);
            $pending->markFailed($e->getMessage());
            return null;
        }
    }
}
