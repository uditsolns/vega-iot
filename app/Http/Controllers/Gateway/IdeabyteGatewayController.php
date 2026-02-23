<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\Reading\ReadingIngestionService;
use App\Vendor\Adapters\IdeabyteAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound HTTP POST requests from Ideabyte / Vega loggers.
 *
 * URL: POST /gateway/ideabyte
 *
 * The device ID is extracted from the JSON payload ('Id' field).
 * Live upload  → single JSON object in body
 * History upload → JSON array
 */
class IdeabyteGatewayController extends Controller
{
    public function __construct(
        private readonly IdeabyteAdapter         $adapter,
        private readonly ReadingIngestionService  $ingestion,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $body = $request->json()->all();

        // Device ID is on the first packet (single object or first array element)
        $firstPacket = is_array($body) && isset($body[0]) ? $body[0] : $body;
        $deviceCode  = $firstPacket['Id'] ?? $firstPacket['id'] ?? null;

        if (!$deviceCode) {
            return response()->json(['message' => 'error', 'reason' => 'Missing device Id'], 400);
        }

        $device = Device::where('device_uid', $deviceCode)
            ->orWhere('device_code', $deviceCode)
            ->with(['deviceModel', 'sensors.sensorType'])
            ->first();

        if (!$device) {
            Log::warning('[Ideabyte] Unknown device', ['device_code' => $deviceCode]);
            // Return success to prevent infinite retries; data is dropped
            return response()->json(['message' => 'success', 'Id' => $deviceCode, 'frequency' => 1, 'config' => 0]);
        }

        try {
            $batches = $this->adapter->parseReadings($request);
            if (!empty($batches)) {
                $this->ingestion->ingestBatches($device, $batches);
                $device->update(['status' => 'online']);
            }
        } catch (\Throwable $e) {
            Log::error('[Ideabyte] Ingestion failed', [
                'device_code' => $deviceCode,
                'error'       => $e->getMessage(),
            ]);
        }

        return response()->json(
            $this->adapter->buildSuccessResponse(null, [
                'device_code' => $device->device_code,
                'frequency'   => $device->currentConfiguration?->recording_interval ?? 1,
            ])
        );
    }
}
