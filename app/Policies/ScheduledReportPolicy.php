<?php

namespace App\Policies;

use App\Models\ScheduledReport;
use App\Models\User;

class ScheduledReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('scheduled_reports.view');
    }

    public function view(User $user, ScheduledReport $scheduledReport): bool
    {
        if (!$user->hasPermission('scheduled_reports.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $scheduledReport->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('scheduled_reports.create');
    }

    public function update(User $user, ScheduledReport $scheduledReport): bool
    {
        if (!$user->hasPermission('scheduled_reports.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $scheduledReport->company_id === $user->company_id;
    }

    public function delete(User $user, ScheduledReport $scheduledReport): bool
    {
        if (!$user->hasPermission('scheduled_reports.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $scheduledReport->company_id === $user->company_id;
    }
}
