<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\Report;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $reportId,
        public readonly string $pdfPath,
    ) {
        // $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        return [MsgClubEmailChannel::class];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $report = Report::find($this->reportId);

        return (new MsgClubEmailMessage)
            ->subject("Report Generated: {$report->name}")
            ->view('emails.reports.generated', [
                'report' => $report,
                'user' => $notifiable,
            ])
            ->attach($this->pdfPath);
    }
}
