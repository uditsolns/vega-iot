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

//    public int $tries = 3;
//    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly ScheduledReport $scheduledReport,
        public readonly array $reports,
        public readonly int $successCount = 0,
        public readonly int $failureCount = 0
    ) {
        $this->onQueue(config('notifications', 'notifications'));
    }

    public function via($notifiable): array
    {
        return [MsgClubEmailChannel::class];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $message = (new MsgClubEmailMessage)
            ->subject("Scheduled Report: {$this->scheduledReport->name}")
            ->view('emails.scheduled-reports.report', [
                'scheduledReport' => $this->scheduledReport,
                'reports' => $this->reports,
                'successCount' => $this->successCount,
                'failureCount' => $this->failureCount,
                'user' => $notifiable,
            ]);

        // Attach all PDF reports
        foreach ($this->reports as $report) {
            if (isset($report['pdf_path']) && file_exists($report['pdf_path'])) {
                $message->attach(
                    $report['pdf_path'],
                    $report['filename'] ?? basename($report['pdf_path'])
                );
            }
        }

        return $message;
    }
}
