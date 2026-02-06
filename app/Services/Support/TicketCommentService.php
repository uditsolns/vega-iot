<?php

namespace App\Services\Support;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
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
}
