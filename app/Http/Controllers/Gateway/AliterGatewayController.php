<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\Reading\ReadingIngestionService;
use App\Vendor\Adapters\AliterAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound HTTP POST requests from Aliter loggers.
 *
 * URL: POST /gateway/aliter
 *
 * Aliter devices publish to an MQTT broker; an MQTT-to-HTTP bridge
 * (or a dedicated subscriber service) forwards each message here.
 *
 * Device identification:
 *   1. Query param 'device_uid'
 *   2. JSON body 'device' field (MAC address)
 */
class AliterGatewayController extends Controller
{
    public function __construct(
        private readonly AliterAdapter           $adapter,
        private readonly ReadingIngestionService  $ingestion,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $body      = $request->json()->all();
        $macOrUid  = $request->query('device_uid') ?? $body['device'] ?? null;

        if (!$macOrUid) {
            return response()->json(['status' => 'error', 'reason' => 'Missing device identifier'], 400);
        }

        $device = Device::where('device_uid', $macOrUid)
            ->with(['deviceModel', 'sensors.sensorType'])
            ->first();

        if (!$device) {
            Log::warning('[Aliter] Unknown device', ['mac' => $macOrUid]);
            return response()->json(['status' => 'ok']);
        }

        try {
            $batches = $this->adapter->parseReadings($request);
            if (!empty($batches)) {
                $this->ingestion->ingestBatches($device, $batches);
                $device->update(['status' => 'online']);
            }
        } catch (\Throwable $e) {
            Log::error('[Aliter] Ingestion failed', [
                'device_uid' => $macOrUid,
                'error'      => $e->getMessage(),
            ]);
        }

        return response()->json($this->adapter->buildSuccessResponse());
    }
}
