<?php

namespace App\Http\Controllers;

use App\Enums\AlertResolutionOptionType;
use App\Http\Requests\AlertResolutionOption\StoreAlertResolutionOptionRequest;
use App\Http\Requests\AlertResolutionOption\UpdateAlertResolutionOptionRequest;
use App\Http\Resources\AlertResolutionOptionResource;
use App\Models\AlertResolutionOption;
use App\Services\Alert\AlertResolutionOptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AlertResolutionOptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AlertResolutionOptionService $service
    ) {}
    public function index(Request $request): JsonResponse
    {
        return $this->success($this->service->listGrouped());
    }

    public function store(StoreAlertResolutionOptionRequest $request): JsonResponse
    {
        $this->authorize('create', AlertResolutionOption::class);

        $option = $this->service->create($request->validated());

        return $this->success(new AlertResolutionOptionResource($option), 'Option created', 201);
    }

    public function update(UpdateAlertResolutionOptionRequest $request, AlertResolutionOption $alertResolutionOption): JsonResponse
    {
        $this->authorize('update', $alertResolutionOption);

        $option = $this->service->update($alertResolutionOption, $request->validated());

        return $this->success(new AlertResolutionOptionResource($option), 'Option updated');
    }

    public function destroy(AlertResolutionOption $alertResolutionOption): JsonResponse
    {
        $this->authorize('delete', $alertResolutionOption);

        $this->service->delete($alertResolutionOption);

        return $this->success(null, 'Option deleted');
    }
}
