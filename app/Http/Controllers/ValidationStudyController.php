<?php

namespace App\Http\Controllers;

use App\Models\ValidationStudy;
use App\Services\Company\ValidationStudyService;
use App\Http\Requests\ValidationStudy\CreateValidationStudyRequest;
use App\Http\Requests\ValidationStudy\UpdateValidationStudyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationStudyController extends Controller
{
    public function __construct(
        private readonly ValidationStudyService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ValidationStudy::class);

        $studies = $this->service->list(
            $request->all(),
            $request->user()
        );

        return $this->collection($studies);
    }

    public function store(CreateValidationStudyRequest $request): JsonResponse
    {
        $this->authorize('create', ValidationStudy::class);

        $study = $this->service->create($request->validated());

        return $this->created($study, 'Validation study created');
    }

    public function show(ValidationStudy $validationStudy): JsonResponse
    {
        $this->authorize('view', $validationStudy);

        return $this->success($validationStudy);
    }

    public function update(
        UpdateValidationStudyRequest $request,
        ValidationStudy $validationStudy
    ): JsonResponse {
        $this->authorize('update', $validationStudy);

        $study = $this->service->update(
            $validationStudy,
            $request->validated()
        );

        return $this->success($study, 'Validation study updated');
    }

    public function destroy(ValidationStudy $validationStudy): JsonResponse
    {
        $this->authorize('delete', $validationStudy);

        $this->service->delete($validationStudy);

        return $this->success(null, 'Validation study deleted');
    }
}
