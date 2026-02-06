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
        public readonly array $reportFilePaths,
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
            ->subject("Scheduled Report: {$scheduledReport->name}")
            ->view('emails.reports.scheduled-report', [
                'scheduledReport' => $scheduledReport,
                'user' => $notifiable,
                'reports' => $this->reportFilePaths,
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
