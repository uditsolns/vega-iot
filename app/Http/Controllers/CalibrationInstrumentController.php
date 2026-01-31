<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalibrationInstrument\CreateCalibrationInstrumentRequest;
use App\Http\Requests\CalibrationInstrument\UpdateCalibrationInstrumentRequest;
use App\Models\CalibrationInstrument;
use App\Services\Company\CalibrationInstrumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalibrationInstrumentController extends Controller
{
    public function __construct(
        private readonly CalibrationInstrumentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CalibrationInstrument::class);

        $items = $this->service->list($request->all(), $request->user());

        return $this->collection($items);
    }

    public function store(CreateCalibrationInstrumentRequest $request): JsonResponse
    {
        $this->authorize('create', CalibrationInstrument::class);

        $item = $this->service->create($request->validated());

        return $this->created($item, 'Calibration instrument created');
    }

    public function show(CalibrationInstrument $calibrationInstrument): JsonResponse
    {
        $this->authorize('view', $calibrationInstrument);

        return $this->success($calibrationInstrument);
    }

    public function update(
        UpdateCalibrationInstrumentRequest $request,
        CalibrationInstrument $calibrationInstrument
    ): JsonResponse {
        $this->authorize('update', $calibrationInstrument);

        $item = $this->service->update(
            $calibrationInstrument,
            $request->validated()
        );

        return $this->success($item, 'Calibration instrument updated');
    }

    public function destroy(CalibrationInstrument $calibrationInstrument): JsonResponse
    {
        $this->authorize('delete', $calibrationInstrument);

        $this->service->delete($calibrationInstrument);

        return $this->success(null, 'Calibration instrument deleted');
    }
}
