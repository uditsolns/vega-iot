<?php

namespace App\Services\ScheduledReport;

use App\Models\ScheduledReport;
use App\Models\ScheduledReportExecution;
use App\Models\User;
use App\Notifications\ScheduledReportNotification;
use App\Services\Report\Adapters\ScheduledReportAdapter;
use App\Services\Report\ReportGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
            $reportFilePaths = [];
            $failedDevices = [];

            $fromDatetime = $this->getFromDatetime($scheduledReport);
            $toDatetime = now($scheduledReport->timezone);

            foreach ($devices as $device) {
                try {
                    // Create adapter for this device
                    $reportableAdapter = new ScheduledReportAdapter(
                        scheduledReport: $scheduledReport,
                        device: $device,
                        fromDatetime: $fromDatetime,
                        toDatetime: $toDatetime
                    );

                    // Generate report using the adapter (no Report model created)
                    $pdfContent = $this->reportGenerator->generateFromReportable($reportableAdapter);

                    // Save PDF to temp storage
                    $filename = "{$device->device_code}_{$fromDatetime->format('Ymd')}_{$toDatetime->format('Ymd')}.pdf";
                    $pdfPath = storage_path("app/temp/scheduled-reports/{$scheduledReport->id}/{$filename}");

                    // Ensure directory exists
                    if (!is_dir(dirname($pdfPath))) {
                        mkdir(dirname($pdfPath), 0755, true);
                    }

                    file_put_contents($pdfPath, $pdfContent);

                    // Store only file path and metadata, NOT the PDF content
                    $reportFilePaths[] = [
                        'device_code' => $device->device_code,
                        'device_name' => $device->device_name ?? $device->device_code,
                        'filename' => $filename,
                        'path' => $pdfPath,
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

            if (!empty($reportFilePaths)) {
                $this->sendReports($scheduledReport, $reportFilePaths);

                // Cleanup temp files after sending
                $this->cleanupTempFiles($scheduledReport->id);
            }

            $status = empty($failedDevices) ? 'success' : (empty($reportFilePaths) ? 'failed' : 'partial');

            $execution->update([
                'status' => $status,
                'execution_details' => [
                    'generated' => array_map(fn($r) => [
                        'device_code' => $r['device_code'],
                        'device_name' => $r['device_name'],
                    ], $reportFilePaths),
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

    /**
     * Send reports using Laravel notifications
     */
    private function sendReports(ScheduledReport $scheduledReport, array $reportFilePaths): void
    {
        try {
            // Get users by email
            $recipients = User::whereIn('email', $scheduledReport->recipient_emails)->get();

            if ($recipients->isEmpty()) {
                Log::warning('No valid recipients found for scheduled report', [
                    'scheduled_report_id' => $scheduledReport->id,
                    'emails' => $scheduledReport->recipient_emails,
                ]);
                return;
            }

            // Create notification with minimal data
            $notification = new ScheduledReportNotification(
                scheduledReportId: $scheduledReport->id,
                reportName: $scheduledReport->name,
                frequency: $scheduledReport->frequency->label(),
                format: $scheduledReport->format->label(),
                dataFormation: $scheduledReport->data_formation->label(),
                reportFilePaths: $reportFilePaths,
                successCount: count($reportFilePaths),
                failureCount: 0
            );

            // Send using Laravel notifications
            Notification::send($recipients, $notification);

            Log::info('Scheduled report notifications sent', [
                'scheduled_report_id' => $scheduledReport->id,
                'recipients_count' => $recipients->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send scheduled report notifications', [
                'scheduled_report_id' => $scheduledReport->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup temporary PDF files
     */
    private function cleanupTempFiles(int $scheduledReportId): void
    {
        try {
            $tempDir = storage_path("app/temp/scheduled-reports/{$scheduledReportId}");
            if (is_dir($tempDir)) {
                array_map('unlink', glob("$tempDir/*"));
                rmdir($tempDir);
            }
        } catch (Exception $e) {
            Log::warning('Failed to cleanup temp files', [
                'scheduled_report_id' => $scheduledReportId,
                'error' => $e->getMessage(),
            ]);
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
