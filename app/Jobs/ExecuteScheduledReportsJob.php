<?php

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Services\ScheduledReport\ScheduledReportExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteScheduledReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ScheduledReportExecutionService $executionService): void
    {
        $scheduledReports = ScheduledReport::dueForExecution()->get();

        Log::info('Executing scheduled reports', [
            'count' => $scheduledReports->count(),
        ]);

        foreach ($scheduledReports as $scheduledReport) {
            try {
                $executionService->execute($scheduledReport);
            } catch (\Exception $e) {
                Log::error('Failed to execute scheduled report', [
                    'scheduled_report_id' => $scheduledReport->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
