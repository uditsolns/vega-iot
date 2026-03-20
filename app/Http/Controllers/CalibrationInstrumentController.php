<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalibrationInstrument\CreateCalibrationInstrumentRequest;
use App\Http\Requests\CalibrationInstrument\ImportCalibrationInstrumentRequest;
use App\Http\Requests\CalibrationInstrument\UpdateCalibrationInstrumentRequest;
use App\Http\Requests\CalibrationInstrument\UploadReportRequest;
use App\Http\Resources\CalibrationInstrumentResource;
use App\Models\CalibrationInstrument;
use App\Services\Company\CalibrationInstrumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalibrationInstrumentController extends Controller
{
    public function __construct(
        private readonly CalibrationInstrumentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CalibrationInstrument::class);

        $items = $this->service->list($request->all(), $request->user());

        return $this->collection(CalibrationInstrumentResource::collection($items));
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

        return $this->success(new CalibrationInstrumentResource($calibrationInstrument));
    }

    public function update(
        UpdateCalibrationInstrumentRequest $request,
        CalibrationInstrument $calibrationInstrument
    ): JsonResponse {
        $this->authorize('update', $calibrationInstrument);

        $item = $this->service->update($calibrationInstrument, $request->validated());

        return $this->success(new CalibrationInstrumentResource($item), 'Calibration instrument updated');
    }

    public function destroy(CalibrationInstrument $calibrationInstrument): JsonResponse
    {
        $this->authorize('delete', $calibrationInstrument);

        $this->service->delete($calibrationInstrument);

        return $this->success(null, 'Calibration instrument deleted');
    }

    // Bulk Import
    /**
     * Bulk import calibration instruments from an Excel/CSV file.
     * Requires assets.create permission. Company users import into their own company;
     * super admins must supply a company_id query parameter.
     */
    public function import(ImportCalibrationInstrumentRequest $request): JsonResponse
    {
        $this->authorize('create', CalibrationInstrument::class);

        $user      = $request->user();
        $companyId = $user->ofSystem()
            ? $request->integer('company_id')
            : $user->company_id;

        abort_if(!$companyId, 422, 'company_id is required for super admin imports.');

        $result = $this->service->import($request->file('file'), $companyId);

        return $this->success($result, "Import completed: {$result['imported']} imported, {$result['skipped']} skipped.");
    }


    // Calibration Report
    public function uploadReport(
        UploadReportRequest $request,
        CalibrationInstrument $calibrationInstrument
    ): JsonResponse {
        $this->authorize('update', $calibrationInstrument);

        $item = $this->service->uploadReport($calibrationInstrument, $request->file('file'));

        return $this->success(new CalibrationInstrumentResource($item), 'Calibration report uploaded');
    }

    public function downloadReport(CalibrationInstrument $calibrationInstrument): StreamedResponse
    {
        $this->authorize('view', $calibrationInstrument);

        return $this->service->downloadReport($calibrationInstrument);
    }

    public function deleteReport(CalibrationInstrument $calibrationInstrument): JsonResponse
    {
        $this->authorize('update', $calibrationInstrument);

        $item = $this->service->deleteReport($calibrationInstrument);

        return $this->success(new CalibrationInstrumentResource($item), 'Calibration report deleted');
    }
}
