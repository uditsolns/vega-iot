<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\Reading\ReadingIngestionService;
use App\Vendor\Adapters\SunsuiAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound HTTP POST requests from Sunsui 4CH / 8CH loggers.
 *
 * URL: POST /gateway/sunsui
 *
 * Device identification:
 *   1. Query param 'device_uid'
 *   2. JSON body 'device_uid' or 'sn'
 */
class SunsuiGatewayController extends Controller
{
    public function __construct(
        private readonly SunsuiAdapter            $adapter,
        private readonly ReadingIngestionService   $ingestion,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $body     = $request->json()->all();
        $deviceId = $request->query('device_uid')
            ?? $body['device_uid']
            ?? $body['sn']
            ?? null;

        if (!$deviceId) {
            return response()->json(['status' => 'error', 'reason' => 'Missing device identifier'], 400);
        }

        $device = Device::where('device_uid', $deviceId)
            ->orWhere('device_code', $deviceId)
            ->with(['deviceModel', 'sensors.sensorType'])
            ->first();

        if (!$device) {
            Log::warning('[Sunsui] Unknown device', ['device_id' => $deviceId]);
            return response()->json(['status' => 'ok', 'ts' => now()->timestamp]);
        }

        try {
            $batches = $this->adapter->parseReadings($request);
            if (!empty($batches)) {
                $this->ingestion->ingestBatches($device, $batches);
                $device->update(['status' => 'online']);
            }
        } catch (\Throwable $e) {
            Log::error('[Sunsui] Ingestion failed', [
                'device_id' => $deviceId,
                'error'     => $e->getMessage(),
            ]);
        }

        return response()->json($this->adapter->buildSuccessResponse());
    }
}
