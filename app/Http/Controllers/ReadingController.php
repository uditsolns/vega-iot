<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reading\GetAggregationsRequest;
use App\Http\Requests\Reading\ListReadingsRequest;
use App\Http\Resources\ReadingResource;
use App\Models\DeviceReading;
use App\Services\Reading\ReadingQueryService;
use Illuminate\Http\JsonResponse;

class ReadingController extends Controller
{
    public function __construct(
        private readonly ReadingQueryService $readingQueryService
    ) {}

    /**
     * Display a listing of readings with filters.
     */
    public function index(ListReadingsRequest $request): JsonResponse
    {
        $this->authorize("viewAny", DeviceReading::class);

        $readings = $this->readingQueryService->list(
            $request->validated(),
            $request->user()
        );

        return $this->collection(ReadingResource::collection($readings));
    }

    /**
     * Get aggregated readings data.
     */
    public function aggregations(GetAggregationsRequest $request): JsonResponse
    {
        $this->authorize("viewAny", DeviceReading::class);

        $aggregations = $this->readingQueryService->aggregations(
            $request->validated(),
            $request->user()
        );

        return $this->success($aggregations);
    }
}
