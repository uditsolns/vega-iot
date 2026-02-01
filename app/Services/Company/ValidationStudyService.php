<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\ValidationStudy;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

readonly class ValidationStudyService
{
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(
            ValidationStudy::forUser($user)
        )
            ->allowedFilters([
                AllowedFilter::partial('area_type'),
                AllowedFilter::partial('location'),
                AllowedFilter::exact('qualification_type'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedSorts([
                'mapping_start_at',
                'mapping_due_at',
                'created_at',
            ])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): ValidationStudy
    {
        $company = Company::find($data['company_id']);
        $user = Auth::user();

        if (isset($user->company_id) && ($company->id !== $user->company_id)) {
            throw new UnauthorizedException("Company doesn't belong to you");
        }

        return ValidationStudy::create($data);

    }

    public function update(ValidationStudy $study, array $data): ValidationStudy {
        $study->update($data);

        return $study->fresh();
    }

    public function delete(ValidationStudy $study): void
    {
        $study->delete();
    }
}
