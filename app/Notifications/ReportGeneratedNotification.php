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

    public function __construct(
        public readonly string $reportName,
        public readonly string $pdfPath,
        public readonly int $generatedById,
        public readonly string $generatedByName,
        public readonly string $generatedAt
    ) {
//        $this->onQueue(config('notifications.queue', 'notifications'));
    }

    public function via($notifiable): array
    {
        return [MsgClubEmailChannel::class, 'database'];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $generatedBy = User::find($this->generatedById);

        return (new MsgClubEmailMessage)
            ->subject("Report Generated: {$this->reportName}")
            ->view('emails.reports.generated', [
                'reportName' => $this->reportName,
                'generatedBy' => $generatedBy,
                'user' => $notifiable,
            ])
            ->attach($this->pdfPath);
    }

    public function toArray($notifiable): array
    {
        return [
            'report_name' => $this->reportName,
            'generated_by_id' => $this->generatedById,
            'generated_by_name' => $this->generatedByName,
            'generated_at' => $this->generatedAt,
            'file_name' => basename($this->pdfPath),
        ];
    }
}
