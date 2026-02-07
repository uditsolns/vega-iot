<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\Ticket;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketReopenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $ticketId,
        public readonly string $subject,
        public readonly int $reopenedBy
    ) {}

    public function via($notifiable): array
    {
        return [
            'database',
            MsgClubEmailChannel::class,
        ];
    }

    public function toMsgClubEmail($notifiable): MsgClubEmailMessage
    {
        $ticket = Ticket::with(['user', 'device', 'area.hub.location'])
            ->find($this->ticketId);

        return (new MsgClubEmailMessage)
            ->subject("Ticket #{$this->ticketId} Has Been Reopened")
            ->view('emails.tickets.reopened', [
                'ticket' => $ticket,
                'user' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'subject' => $this->subject,
            'reopened_by' => $this->reopenedBy,
            'event' => 'reopened',
        ];
    }
}
