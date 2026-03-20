<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudy\CreateValidationStudyRequest;
use App\Http\Requests\ValidationStudy\ImportValidationStudyRequest;
use App\Http\Requests\ValidationStudy\UpdateValidationStudyRequest;
use App\Http\Requests\ValidationStudy\UploadReportRequest;
use App\Http\Resources\ValidationStudyResource;
use App\Models\ValidationStudy;
use App\Services\Company\ValidationStudyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidationStudyController extends Controller
{
    public function __construct(
        private readonly ValidationStudyService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ValidationStudy::class);

        $studies = $this->service->list($request->all(), $request->user());

        return $this->collection(ValidationStudyResource::collection($studies));
    }

    public function store(CreateValidationStudyRequest $request): JsonResponse
    {
        $this->authorize('create', ValidationStudy::class);

        $study = $this->service->create($request->validated());

        return $this->created(new ValidationStudyResource($study), 'Validation study created');
    }

    public function show(ValidationStudy $validationStudy): JsonResponse
    {
        $this->authorize('view', $validationStudy);

        return $this->success(new ValidationStudyResource($validationStudy));
    }

    public function update(
        UpdateValidationStudyRequest $request,
        ValidationStudy $validationStudy
    ): JsonResponse {
        $this->authorize('update', $validationStudy);

        $study = $this->service->update($validationStudy, $request->validated());

        return $this->success(new ValidationStudyResource($study), 'Validation study updated');
    }

    public function destroy(ValidationStudy $validationStudy): JsonResponse
    {
        $this->authorize('delete', $validationStudy);

        $this->service->delete($validationStudy);

        return $this->success(null, 'Validation study deleted');
    }


    // Bulk Import
    /**
     * Bulk import validation studies from an Excel/CSV file.
     * Requires validation_studies.create permission. Company users import into their own company;
     * super admins must supply a company_id query parameter.
     */
    public function import(ImportValidationStudyRequest $request): JsonResponse
    {
        $this->authorize('create', ValidationStudy::class);

        $user      = $request->user();
        $companyId = $user->ofSystem()
            ? $request->integer('company_id')
            : $user->company_id;

        abort_if(!$companyId, 422, 'company_id is required for super admin imports.');

        $result = $this->service->import($request->file('file'), $companyId);

        return $this->success($result, "Import completed: {$result['imported']} imported, {$result['skipped']} skipped.");
    }


    // Report
    public function uploadReport(
        UploadReportRequest $request,
        ValidationStudy $validationStudy
    ): JsonResponse {
        $this->authorize('update', $validationStudy);

        $study = $this->service->uploadReport($validationStudy, $request->file('file'));

        return $this->success(new ValidationStudyResource($study), 'Report uploaded');
    }

    public function downloadReport(ValidationStudy $validationStudy): StreamedResponse
    {
        $this->authorize('view', $validationStudy);

        return $this->service->downloadReport($validationStudy);
    }

    public function deleteReport(ValidationStudy $validationStudy): JsonResponse
    {
        $this->authorize('update', $validationStudy);

        $study = $this->service->deleteReport($validationStudy);

        return $this->success(new ValidationStudyResource($study), 'Report deleted');
    }
}
