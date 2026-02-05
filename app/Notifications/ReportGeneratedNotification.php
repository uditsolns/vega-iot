<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\User;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

//    public int $tries = 3;
//    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly string $reportName,
        public readonly string $pdfPath,
        public readonly User $generatedBy
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
            ->subject("Report Generated: {$this->reportName}")
            ->view('emails.reports.generated', [
                'reportName' => $this->reportName,
                'generatedBy' => $this->generatedBy,
                'user' => $notifiable,
            ])
            ->attach($this->pdfPath);
    }
}
