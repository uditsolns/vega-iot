<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AuditReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $auditReportId,
        public readonly string $reportName,
        public readonly string $reportType,
        public readonly string $pdfPath,
        public readonly int $generatedById,
        public readonly string $generatedByName
    ) {
//        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        // Audit reports are sent via email only, not stored in database
        // They are more like scheduled reports - one-time delivery
        return [MsgClubEmailChannel::class];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $auditReport = \App\Models\AuditReport::with('generatedBy')
            ->find($this->auditReportId);

        return (new MsgClubEmailMessage)
            ->subject("Audit Report Generated: {$this->reportName}")
            ->view('emails.reports.audit', [
                'auditReport' => $auditReport,
                'user' => $notifiable,
            ])
            ->attach($this->pdfPath);
    }

    /**
     * Don't store in database
     */
    public function toDatabase($notifiable): ?array
    {
        return null;
    }
}
