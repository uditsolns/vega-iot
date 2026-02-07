<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\CreateCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Services\Support\TicketCommentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TicketCommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TicketCommentService $ticketCommentService
    ) {}

    public function index(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $canSeeInternal = $ticket->canUserSeeInternalComments(request()->user());

        $comments = $this->ticketCommentService->getComments(
            $ticket,
            request()->user(),
            $canSeeInternal
        );

        return $this->success(TicketCommentResource::collection($comments));
    }

    public function store(CreateCommentRequest $request, Ticket $ticket): JsonResponse
    {
        // Check if user can add comment
        $this->authorize('addComment', $ticket);

        // If trying to add internal comment, check specific permission
        if ($request->boolean('is_internal')) {
            $this->authorize('addInternalComment', $ticket);
        }

        $comment = $this->ticketCommentService->addComment(
            $ticket,
            $request->validated(),
            $request->user(),
        );

        return $this->created(
            new TicketCommentResource($comment),
            'Comment added successfully'
        );
    }
}
