<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\CreateCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\Support\TicketCommentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketCommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TicketCommentService $ticketCommentService
    ) {}

    public function index(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $includeInternal = request()->user()->can('viewInternalComments', $ticket);

        $comments = $this->ticketCommentService->getComments($ticket, request()->user(), $includeInternal);

        return $this->success(TicketCommentResource::collection($comments));
    }

    public function store(CreateCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('addComment', $ticket);

        $comment = $this->ticketCommentService->addComment(
            $ticket,
            $request->validated(),
            $request->user(),
        );

        return $this->created(new TicketCommentResource($comment), 'Comment added successfully');
    }
}
