<?php

namespace App\Services\Support;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketCommentAddedNotification;
use Illuminate\Database\Eloquent\Collection;

class TicketCommentService
{
    /**
     * Add a comment to a ticket.
     */
    public function addComment(
        Ticket $ticket,
        array $data,
        User $user,
    ): TicketComment {
        // Create comment
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'comment' => $data['comment'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Update ticket's updated_at timestamp
        $ticket->touch();

        // Audit log
        $commentType = $comment->is_internal ? 'internal comment' : 'comment';
        activity("ticket")
            ->event('comment_added')
            ->performedOn($ticket)
            ->withProperties([
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'is_internal' => $comment->is_internal,
            ])
            ->log("Added {$commentType} to ticket \"{$ticket->subject}\"");

        // Send notifications for external comments only
        if (!$comment->is_internal) {
            $this->sendCommentNotifications($ticket, $comment, $user);
        }

        return $comment->load(['user']);
    }

    /**
     * Get comments for a ticket.
     */
    public function getComments(
        Ticket $ticket,
        User $user,
        bool $includeInternal = false
    ): Collection {
        $query = TicketComment::where('ticket_id', $ticket->id);

        // Filter internal comments if needed
        if (!$includeInternal) {
            $query->where('is_internal', false);
        }

        return $query
            ->with(['user'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Send notifications when a comment is added
     */
    private function sendCommentNotifications(Ticket $ticket, TicketComment $comment, User $commentedBy): void
    {
        // Notify ticket creator if someone else commented
        if ($ticket->user_id !== $commentedBy->id) {
            $ticket->user->notify(
                new TicketCommentAddedNotification(
                    ticketId: $ticket->id,
                    commentId: $comment->id,
                    commentText: $comment->comment,
                    commentedBy: $commentedBy->id,
                )
            );
        }

        // Notify assigned user if someone else commented
        if ($ticket->assigned_to && $ticket->assigned_to !== $commentedBy->id) {
            $ticket->assignedTo->notify(
                new TicketCommentAddedNotification(
                    ticketId: $ticket->id,
                    commentId: $comment->id,
                    commentText: $comment->comment,
                    commentedBy: $commentedBy->id,
                )
            );
        }
    }
}
