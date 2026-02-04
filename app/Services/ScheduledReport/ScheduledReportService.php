<?php

namespace App\Services\ScheduledReport;

use App\Models\ScheduledReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ScheduledReportService
{
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(ScheduledReport::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('frequency'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('device_type'),
            ])
            ->allowedSorts(['name', 'created_at', 'next_run_at'])
            ->allowedIncludes(['createdBy', 'devices', 'executions'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data, User $user): ScheduledReport
    {
        $deviceIds = $data['device_ids'];
        unset($data['device_ids']);

        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;
        $data['next_run_at'] = $this->calculateNextRun(
            $data['frequency'],
            $data['time'],
            $data['timezone']
        );

        $scheduledReport = ScheduledReport::create($data);

        $scheduledReport->devices()->attach($deviceIds);

        activity('scheduled-report')
            ->event('created')
            ->performedOn($scheduledReport)
            ->withProperties([
                'scheduled_report_id' => $scheduledReport->id,
                'device_ids' => $deviceIds,
                'device_count' => count($deviceIds),
            ])
            ->log("Created scheduled report \"{$scheduledReport->name}\"");

        return $scheduledReport->load('devices', 'createdBy');
    }

    public function update(ScheduledReport $scheduledReport, array $data): ScheduledReport
    {
        $deviceIds = $data['device_ids'] ?? null;
        unset($data['device_ids']);

        if (isset($data['frequency']) || isset($data['time']) || isset($data['timezone'])) {
            $data['next_run_at'] = $this->calculateNextRun(
                $data['frequency'] ?? $scheduledReport->frequency->value,
                $data['time'] ?? $scheduledReport->time,
                $data['timezone'] ?? $scheduledReport->timezone
            );
        }

        $scheduledReport->update($data);

        if ($deviceIds !== null) {
            $scheduledReport->devices()->sync($deviceIds);
        }

        activity('scheduled-report')
            ->event('updated')
            ->performedOn($scheduledReport)
            ->log("Updated scheduled report \"{$scheduledReport->name}\"");

        return $scheduledReport->load('devices', 'createdBy');
    }

    public function delete(ScheduledReport $scheduledReport): bool
    {
        activity('scheduled-report')
            ->event('deleted')
            ->performedOn($scheduledReport)
            ->log("Deleted scheduled report \"{$scheduledReport->name}\"");

        return $scheduledReport->delete();
    }

    public function toggle(ScheduledReport $scheduledReport): ScheduledReport
    {
        $scheduledReport->update([
            'is_active' => !$scheduledReport->is_active,
        ]);
        $scheduledReport = $scheduledReport->fresh();

        $status = $scheduledReport->is_active ? 'activated' : 'deactivated';

        activity('scheduled-report')
            ->event($status)
            ->performedOn($scheduledReport)
            ->log(ucfirst($status) . " scheduled report \"{$scheduledReport->name}\"");

        return $scheduledReport;
    }

    private function calculateNextRun(string $frequency, string $time, string $timezone): Carbon
    {
        $now = Carbon::now($timezone);
        $scheduledTime = Carbon::parse($time, $timezone)->setDate(
            $now->year,
            $now->month,
            $now->day
        );

        if ($scheduledTime->lessThanOrEqualTo($now)) {
            $scheduledTime->addDay();
        }

        return match ($frequency) {
            'weekly' => $scheduledTime->next(Carbon::MONDAY),
            'fortnightly' => $scheduledTime->addWeeks(2),
            'monthly' => $scheduledTime->addMonth(),
            default => $scheduledTime,
        };
    }
}
