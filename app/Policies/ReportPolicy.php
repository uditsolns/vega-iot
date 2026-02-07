<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reports.view');
    }

    public function view(User $user, Report $report): bool
    {
        if (!$user->hasPermission('reports.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $report->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reports.create');
    }
}
