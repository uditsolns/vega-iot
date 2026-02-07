<?php

namespace App\Notifications;

use App\Channels\MsgClubEmailChannel;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Notifications\Messages\MsgClubEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketCommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $commentId,
        public readonly string $commentText,
        public readonly int $commentedBy
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

        $comment = TicketComment::with('user')->find($this->commentId);

        return (new MsgClubEmailMessage)
            ->subject("New Comment on Ticket #{$this->ticketId}")
            ->view('emails.tickets.comment-added', [
                'ticket' => $ticket,
                'comment' => $comment,
                'user' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'comment_id' => $this->commentId,
            'comment_preview' => substr($this->commentText, 0, 100),
            'commented_by' => $this->commentedBy,
            'event' => 'comment_added',
        ];
    }
}
