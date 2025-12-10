<?php

namespace App\Services\Support;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class TicketCommentService
{
    /**
     * Add a comment to a ticket.
     *
     * @param Ticket $ticket
     * @param array $data
     * @param User $user
     * @param array|null $attachments
     * @return TicketComment
     */
    public function addComment(
        Ticket $ticket,
        array $data,
        User $user,
        ?array $attachments = null
    ): TicketComment {
        // Create comment
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'comment' => $data['comment'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Handle attachments if provided
        if ($attachments && count($attachments) > 0) {
            $this->handleAttachments($comment, $attachments);
        }

        // Update ticket's updated_at timestamp
        $ticket->touch();

        return $comment->load(['user', 'attachments']);
    }

    /**
     * Handle file attachments for a comment.
     *
     * @param TicketComment $comment
     * @param array $files
     * @return array
     */
    public function handleAttachments(TicketComment $comment, array $files): array
    {
        $attachments = [];
        $storagePath = "tickets/$comment->ticket_id";

        foreach ($files as $file) {
            // Store file
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = Storage::disk('local')->putFileAs(
                $storagePath,
                $file,
                $fileName
            );

            // Create attachment record
            $attachment = TicketAttachment::create([
                'ticket_id' => $comment->ticket_id,
                'comment_id' => $comment->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $comment->user_id,
                'uploaded_at' => now(),
            ]);

            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * Get comments for a ticket.
     *
     * @param Ticket $ticket
     * @param User $user
     * @param bool $includeInternal
     * @return Collection
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
            ->with(['user', 'attachments.uploadedBy'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Delete an attachment.
     *
     * @param TicketAttachment $attachment
     * @return bool
     */
    public function deleteAttachment(TicketAttachment $attachment): bool
    {
        // Delete file from storage
        if ($attachment->exists()) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        // Delete attachment record
        return $attachment->delete();
    }

    /**
     * Get attachment file path.
     *
     * @param TicketAttachment $attachment
     * @return string
     */
    public function getAttachmentPath(TicketAttachment $attachment): string
    {
        return Storage::disk('local')->path($attachment->file_path);
    }
}
