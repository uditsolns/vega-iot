<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reading\IngestBatchRequest;
use App\Http\Requests\Reading\IngestReadingRequest;
use App\Services\Reading\ReadingIngestionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class IngestController extends Controller
{
    public function __construct(
        private readonly ReadingIngestionService $ingestionService,
    ) {}

    /**
     * Store a single reading from a device
     */
    public function store(IngestReadingRequest $request): JsonResponse
    {
        try {
            // Get device from request (set by auth.device middleware)
            $device = $request->input("device");

            // Store the reading (returns DeviceReading model)
            $reading = $this->ingestionService->store(
                $device,
                $request->validated(),
            );

            // Return minimal JSON response for device bandwidth efficiency
            return response()->json(
                [
                    "success" => true,
                    "received_at" => $reading->received_at->toIso8601String(),
                ],
                201,
            );
        } catch (Exception $e) {
            // Log error for debugging
            Log::error("Reading ingestion failed", [
                "device_id" => $request->input("device")?->id,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to store reading",
                ],
                500,
            );
        }
    }

    /**
     * Store a batch of readings from a device
     */
    public function batch(IngestBatchRequest $request): JsonResponse
    {
        try {
            // Get device from request (set by auth.device middleware)
            $device = $request->input("device");

            // Store batch of readings
            $result = $this->ingestionService->storeBatch(
                $device,
                $request->validated()["readings"],
            );

            // Return batch result with errors if any failed
            $response = [
                "success" => $result["failed"] === 0,
                "count" => $result["success"],
                "failed" => $result["failed"],
                "received_at" => now()->toIso8601String(),
            ];

            // Include errors if any readings failed validation
            if ($result["failed"] > 0 && !empty($result["errors"])) {
                $response["errors"] = $result["errors"];
            }

            return response()->json($response, 201);
        } catch (Exception $e) {
            // Log error for debugging
            Log::error("Batch ingestion failed", [
                "device_id" => $request->input("device")?->id,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to store batch readings",
                ],
                500,
            );
        }
    }
}
