<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditService
{
    /**
     * Log an audit event
     */
    public function log(string $event, string $auditableType, $auditable, ?array $metadata = []): AuditLog
    {
        $user = Auth::user();
        $request = request();

        // Get old and new values
        $oldValues = [];
        $newValues = [];
        $changedFields = [];

        if ($auditable && method_exists($auditable, 'getOriginal') && method_exists($auditable, 'getAttributes')) {
            $original = $auditable->getOriginal();
            $current = $auditable->getAttributes();

            foreach ($current as $key => $value) {
                if (isset($original[$key]) && $original[$key] !== $value) {
                    $oldValues[$key] = $original[$key];
                    $newValues[$key] = $value;
                    $changedFields[] = $key;
                }
            }
        }

        // Merge with metadata
        if (!empty($metadata['old_values'])) {
            $oldValues = array_merge($oldValues, $metadata['old_values']);
        }
        if (!empty($metadata['new_values'])) {
            $newValues = array_merge($newValues, $metadata['new_values']);
        }

        // Build description
        $description = $this->buildDescription($event, $auditableType, $auditable, $changedFields);

        return AuditLog::create([
            'user_id' => $user?->id,
            'company_id' => $user?->company_id,
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditable?->id ?? ($metadata['auditable_id'] ?? null),
            'description' => $description,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Build human-readable description
     */
    private function buildDescription(string $event, string $auditableType, $auditable, array $changedFields): string
    {
        $resourceName = class_basename($auditableType);
        $resourceIdentifier = $auditable?->name ?? $auditable?->id ?? 'resource';

        $action = match (true) {
            str_contains($event, 'created') => 'created',
            str_contains($event, 'updated') => 'updated',
            str_contains($event, 'deleted') => 'deleted',
            str_contains($event, 'restored') => 'restored',
            str_contains($event, 'assigned') => 'assigned',
            str_contains($event, 'unassigned') => 'unassigned',
            str_contains($event, 'activated') => 'activated',
            str_contains($event, 'deactivated') => 'deactivated',
            str_contains($event, 'acknowledged') => 'acknowledged',
            str_contains($event, 'resolved') => 'resolved',
            str_contains($event, 'generated') => 'generated',
            default => $event,
        };

        $description = ucfirst($action) . " $resourceName: $resourceIdentifier";

        if (!empty($changedFields)) {
            $description .= " (Changed: " . implode(', ', $changedFields) . ")";
        }

        return $description;
    }

    /**
     * List audit logs with filters
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {

        return QueryBuilder::for(AuditLog::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial('event'),
                AllowedFilter::partial('auditable_type'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('auditable_id'),
                AllowedFilter::scope('date_from'),
                AllowedFilter::scope('date_to'),
            ])
            ->allowedSorts(['created_at', 'event', 'auditable_type'])
            ->allowedIncludes(['user'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get user's activity history
     */
    public function getUserActivity(int $userId, int $days = 30): Collection
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get resource history
     */
    public function getResourceHistory(string $auditableType, int $auditableId): Collection
    {
        return AuditLog::where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
