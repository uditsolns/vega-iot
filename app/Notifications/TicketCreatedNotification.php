<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\Ticket;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $ticketId,
        public readonly string $subject,
        public readonly string $priority,
        public readonly int $createdBy
    ) {
        // Queue configuration can be added here if needed
    }

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
            ->subject("New Support Ticket #{$this->ticketId}: {$this->subject}")
            ->view('emails.tickets.created', [
                'ticket' => $ticket,
                'user' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'created_by' => $this->createdBy,
            'event' => 'created',
        ];
    }
}
