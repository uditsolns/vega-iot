<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\AuditReport;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AuditReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $auditReportId,
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
        $auditReport = AuditReport::find($this->auditReportId);

        return (new MsgClubEmailMessage)
            ->subject("Audit Report Generated: {$auditReport->name}")
            ->view('emails.reports.audit', [
                'auditReport' => $auditReport,
                'user' => $notifiable,
            ])
            ->attach($this->pdfPath);
    }
}
