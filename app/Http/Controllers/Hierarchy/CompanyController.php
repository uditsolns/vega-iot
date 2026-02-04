<?php

namespace App\Http\Controllers\Hierarchy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CreateCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companyService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Company::class);

        $companies = $this->companyService->list(
            $request->all(),
            $request->user(),
        );

        return $this->collection(CompanyResource::collection($companies));
    }

    public function store(CreateCompanyRequest $request): JsonResponse
    {
        $this->authorize("create", Company::class);

        $company = $this->companyService->create($request->validated());

        return $this->created(
            new CompanyResource($company),
            "Company created successfully",
        );
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        $this->authorize("view", $company);

        return $this->success(new CompanyResource($company));
    }

    public function update(
        UpdateCompanyRequest $request,
        Company $company,
    ): JsonResponse {
        $this->authorize("update", $company);

        $company = $this->companyService->update(
            $company,
            $request->validated(),
        );

        return $this->success(
            new CompanyResource($company),
            "Company updated successfully",
        );
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        $this->authorize("delete", $company);

        $this->companyService->delete($company);

        return $this->success(null, "Company deleted successfully");
    }

    public function activate(Request $request, Company $company): JsonResponse
    {
        $this->authorize("update", $company);

        $company = $this->companyService->activate($company);

        return $this->success(
            new CompanyResource($company),
            "Company activated successfully",
        );
    }

    public function deactivate(Request $request, Company $company): JsonResponse
    {
        $this->authorize("update", $company);

        $company = $this->companyService->deactivate($company);

        return $this->success(
            new CompanyResource($company),
            "Company deactivated successfully",
        );
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $company = Company::onlyTrashed()->findOrFail($id);

        $this->authorize("update", $company);

        $company = $this->companyService->restore($company);

        return $this->success(
            new CompanyResource($company),
            "Company restored successfully",
        );
    }
}
