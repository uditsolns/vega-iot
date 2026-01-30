<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class CompanyService
{
    public function __construct(private AuditService $auditService) {}
    /**
     * Get paginated list of companies.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Company::forUser($user))
            ->withCount(['users', 'devices'])
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::partial("client_name"),
                AllowedFilter::partial("email"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("is_hierarchy_enabled"),
                AllowedFilter::exact("is_csv_export_enabled"),
                AllowedFilter::exact("is_device_config_enabled"),
            ])
            ->allowedSorts(["name", "created_at"])
            ->allowedIncludes(["users", "roles"])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new company.
     *
     * @param array $data
     * @return Company
     */
    public function create(array $data): Company
    {
        $company = Company::create($data);

        // Audit log
        $this->auditService->log("company.created", Company::class, $company);

        return $company;
    }

    /**
     * Update a company.
     *
     * @param Company $company
     * @param array $data
     * @return Company
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        // Audit log
        $this->auditService->log("company.updated", Company::class, $company);

        return $company->fresh();
    }

    /**
     * Delete a company (soft delete).
     *
     * @param Company $company
     * @return void
     */
    public function delete(Company $company): void
    {
        $company->delete();

        // Audit log
        $this->auditService->log("company.deleted", Company::class, $company);
    }

    /**
     * Activate a company.
     *
     * @param Company $company
     * @return Company
     */
    public function activate(Company $company): Company
    {
        $company->update(["is_active" => true]);

        // Audit log
        $this->auditService->log("company.activated", Company::class, $company);

        return $company->fresh();
    }

    /**
     * Deactivate a company.
     *
     * @param Company $company
     * @return Company
     */
    public function deactivate(Company $company): Company
    {
        $company->update(["is_active" => false]);

        // Audit log
        $this->auditService->log("company.deactivated", Company::class, $company);

        return $company->fresh();
    }

    /**
     * Restore a soft-deleted company.
     *
     * @param Company $company
     * @return Company
     */
    public function restore(Company $company): Company
    {
        $company->restore();

        // Audit log
        $this->auditService->log("company.restored", Company::class, $company);

        return $company->fresh();
    }
}
