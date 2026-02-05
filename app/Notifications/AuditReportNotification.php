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

//    public int $tries = 3;
//    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly AuditReport $auditReport,
        public readonly string $pdfPath
    ) {
        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        return [MsgClubEmailChannel::class];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        return (new MsgClubEmailMessage)
            ->subject("Audit Report Generated: {$this->auditReport->name}")
            ->view('emails.reports.audit', [
                'auditReport' => $this->auditReport,
                'user' => $notifiable,
            ])
            ->attach($this->pdfPath);
    }
}
