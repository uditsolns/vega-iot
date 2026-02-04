<?php

namespace App\Services\ScheduledReport;

use App\Mail\ScheduledReportMail;
use App\Models\Report;
use App\Models\ScheduledReport;
use App\Models\ScheduledReportExecution;
use App\Services\Report\ReportGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

readonly class ScheduledReportExecutionService
{
    public function __construct(
        private ReportGeneratorService $reportGenerator
    ) {}

    public function execute(ScheduledReport $scheduledReport): ScheduledReportExecution
    {
        $execution = ScheduledReportExecution::create([
            'scheduled_report_id' => $scheduledReport->id,
            'executed_at' => now(),
            'status' => 'success',
            'reports_generated' => 0,
            'reports_failed' => 0,
        ]);

        try {
            $devices = $scheduledReport->devices;
            $generatedReports = [];
            $failedDevices = [];

            $fromDatetime = $this->getFromDatetime($scheduledReport);
            $toDatetime = now($scheduledReport->timezone);

            foreach ($devices as $device) {
                try {
                    $reportData = [
                        'company_id' => $scheduledReport->company_id,
                        'device_id' => $device->id,
                        'generated_by' => $scheduledReport->created_by,
                        'name' => "{$scheduledReport->name} - {$device->device_code}",
                        'file_type' => $scheduledReport->file_type,
                        'format' => $scheduledReport->format,
                        'data_formation' => $scheduledReport->data_formation,
                        'interval' => $scheduledReport->interval,
                        'from_datetime' => $fromDatetime,
                        'to_datetime' => $toDatetime,
                    ];

                    $report = Report::create($reportData);
                    $pdfContent = $this->reportGenerator->generate($report);

                    $generatedReports[] = [
                        'device_code' => $device->device_code,
                        'device_name' => $device->device_name ?? $device->device_code,
                        'pdf_content' => $pdfContent,
                        'filename' => "{$device->device_code}_{$fromDatetime->format('Ymd')}_{$toDatetime->format('Ymd')}.pdf",
                    ];

                    $execution->increment('reports_generated');
                } catch (Exception $e) {
                    Log::error('Scheduled report generation failed for device', [
                        'scheduled_report_id' => $scheduledReport->id,
                        'device_id' => $device->id,
                        'error' => $e->getMessage(),
                    ]);

                    $failedDevices[] = [
                        'device_code' => $device->device_code,
                        'error' => $e->getMessage(),
                    ];

                    $execution->increment('reports_failed');
                }
            }

            if (!empty($generatedReports)) {
                $this->sendReports($scheduledReport, $generatedReports);
            }

            $status = empty($failedDevices) ? 'success' : (empty($generatedReports) ? 'failed' : 'partial');

            $execution->update([
                'status' => $status,
                'execution_details' => [
                    'generated' => array_map(fn($r) => [
                        'device_code' => $r['device_code'],
                        'device_name' => $r['device_name'],
                    ], $generatedReports),
                    'failed' => $failedDevices,
                ],
            ]);

            $this->updateScheduledReportNextRun($scheduledReport);
        } catch (Exception $e) {
            Log::error('Scheduled report execution failed', [
                'scheduled_report_id' => $scheduledReport->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $execution;
    }

    private function sendReports(ScheduledReport $scheduledReport, array $reports): void
    {
        foreach ($scheduledReport->recipient_emails as $email) {
            try {
                Mail::to($email)->send(new ScheduledReportMail($scheduledReport, $reports));
            } catch (Exception $e) {
                Log::error('Failed to send scheduled report email', [
                    'scheduled_report_id' => $scheduledReport->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getFromDatetime(ScheduledReport $scheduledReport): Carbon
    {
        if ($scheduledReport->last_run_at) {
            return Carbon::parse($scheduledReport->last_run_at, $scheduledReport->timezone);
        }

        $now = Carbon::now($scheduledReport->timezone);

        return match ($scheduledReport->frequency->value) {
            'daily' => $now->subDay(),
            'weekly' => $now->subWeek(),
            'fortnightly' => $now->subWeeks(2),
            'monthly' => $now->subMonth(),
            default => $now->subDay(),
        };
    }

    private function updateScheduledReportNextRun(ScheduledReport $scheduledReport): void
    {
        $nextRun = $this->calculateNextRun(
            $scheduledReport->frequency->value,
            $scheduledReport->time,
            $scheduledReport->timezone
        );

        $scheduledReport->update([
            'last_run_at' => now(),
            'next_run_at' => $nextRun,
        ]);
    }

    private function calculateNextRun(string $frequency, string $time, string $timezone): Carbon
    {
        $now = Carbon::now($timezone);
        $scheduledTime = Carbon::parse($time, $timezone)->setDate(
            $now->year,
            $now->month,
            $now->day
        );

        $scheduledTime->addDay();

        return match ($frequency) {
            'daily' => $scheduledTime,
            'weekly' => $scheduledTime->next(Carbon::MONDAY),
            'fortnightly' => $scheduledTime->addWeeks(2),
            'monthly' => $scheduledTime->addMonth(),
            default => $scheduledTime,
        };
    }
}
