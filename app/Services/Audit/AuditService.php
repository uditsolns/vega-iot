<?php

namespace App\Services\Audit;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditService
{
    /**
     * List activity logs with filters
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        $query = Activity::query();

        // Apply company scoping for non-super admins
        if (!$user->isSuperAdmin()) {
            $query->where(function($q) use ($user) {
                // Activities by users from same company
                $q->whereHasMorph('causer', [User::class], function($sq) use ($user) {
                    $sq->where('company_id', $user->company_id);
                });
            });
        }

        return QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::partial('log_name'),
                AllowedFilter::partial('description'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::callback('causer_id', function ($query, $value) {
                    $query->where('causer_id', $value)->where('causer_type', User::class);
                }),
                AllowedFilter::callback('date_from', function ($query, $value) {
                    $query->where('created_at', '>=', $value);
                }),
                AllowedFilter::callback('date_to', function ($query, $value) {
                    $query->where('created_at', '<=', $value);
                }),
            ])
            ->allowedSorts(['created_at', 'log_name', 'description'])
            ->allowedIncludes(['causer', 'subject'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 50);
    }
}
