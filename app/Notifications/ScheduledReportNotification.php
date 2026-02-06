<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\ScheduledReport;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ScheduledReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Store only necessary data, not full report objects
     */
    public function __construct(
        public readonly int $scheduledReportId,
        public readonly string $reportName,
        public readonly string $frequency,
        public readonly string $format,
        public readonly string $dataFormation,
        public readonly array $reportFilePaths, // Just the file paths, not the content
        public readonly int $successCount = 0,
        public readonly int $failureCount = 0
    ) {
//        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        return [MsgClubEmailChannel::class];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        // Load the scheduled report fresh from database
        $scheduledReport = ScheduledReport::find($this->scheduledReportId);

        $message = (new MsgClubEmailMessage)
            ->subject("Scheduled Report: {$this->reportName}")
            ->view('emails.reports.scheduled-report', [
                'scheduledReport' => $scheduledReport,
                'successCount' => $this->successCount,
                'failureCount' => $this->failureCount,
                'user' => $notifiable,
            ]);

        // Attach all PDF reports
        foreach ($this->reportFilePaths as $report) {
            if (isset($report['path']) && file_exists($report['path'])) {
                $message->attach($report['path'], $report['filename'] ?? null);
            }
        }

        return $message;
    }

    /**
     * This notification should NOT be stored in database
     * Return null to skip database storage
     */
    public function toDatabase($notifiable): ?array
    {
        return null;
    }
}
