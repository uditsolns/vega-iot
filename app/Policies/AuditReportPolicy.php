<?php

namespace App\Policies;

use App\Models\AuditReport;
use App\Models\User;

class AuditReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit_reports.view');
    }

    public function view(User $user, AuditReport $report): bool
    {
        if (!$user->hasPermission('audit_reports.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $report->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('audit_reports.create');
    }
}
