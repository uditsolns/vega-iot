<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class CompanyService
{
    /**
     * Get paginated list of companies.
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
     */
    public function create(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * Update a company.
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }

    /**
     * Delete a company (soft delete).
     */
    public function delete(Company $company): void
    {
        $company->delete();
    }

    /**
     * Activate a company.
     */
    public function activate(Company $company): Company
    {
        $company->update(["is_active" => true]);

        // Audit log
        activity("company")
            ->event("activated")
            ->performedOn($company)
            ->withProperties(["company_id" => $company->id])
            ->log("Activated company \"{$company->name}\"");

        return $company->fresh();
    }

    /**
     * Deactivate a company.
     */
    public function deactivate(Company $company): Company
    {
        $company->update(["is_active" => false]);

        // Audit log
        activity("company")
            ->event("deactivated")
            ->performedOn($company)
            ->withProperties(["company_id" => $company->id])
            ->log("Deactivated company \"{$company->name}\"");

        return $company->fresh();
    }

    /**
     * Restore a soft-deleted company.
     */
    public function restore(Company $company): Company
    {
        $company->restore();

        // Audit log
        activity('company')
            ->event("restored")
            ->performedOn($company)
            ->withProperties(['company_id' => $company->id])
            ->log("Restored company \"{$company->name}\"");

        return $company->fresh();
    }
}
