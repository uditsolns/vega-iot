<?php

namespace App\Services\Company;

use App\Models\ValidationStudy;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

readonly class ValidationStudyService
{
    public function __construct(private AuditService $auditService) {}

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
        $study = ValidationStudy::create($data);

        $this->auditService->log(
            'validation_study.created',
            ValidationStudy::class,
            $study
        );

        return $study;
    }

    public function update(
        ValidationStudy $study,
        array $data
    ): ValidationStudy {
        $study->update($data);

        $this->auditService->log(
            'validation_study.updated',
            ValidationStudy::class,
            $study
        );

        return $study->fresh();
    }

    public function delete(ValidationStudy $study): void
    {
        $study->delete();

        $this->auditService->log(
            'validation_study.deleted',
            ValidationStudy::class,
            $study
        );
    }
}
