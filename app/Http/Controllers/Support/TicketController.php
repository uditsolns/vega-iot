<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\ChangeStatusRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Ticket::class);

        $tickets = $this->ticketService->list(
            $request->all(),
            $request->user(),
        );

        return $this->collection(TicketResource::collection($tickets));
    }

    public function store(CreateTicketRequest $request): JsonResponse
    {
        $this->authorize("create", Ticket::class);

        $ticket = $this->ticketService->create(
            $request->validated(),
            $request->user(),
        );

        return $this->created(
            new TicketResource($ticket),
            "Ticket created successfully",
        );
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize("view", $ticket);

        $ticket->load([
            "user",
            "assignedTo",
            "device",
            "location",
            "area",
            "comments.user",
        ]);

        return $this->success(new TicketResource($ticket));
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
    ): JsonResponse {
        $this->authorize("update", $ticket);

        $ticket = $this->ticketService->update($ticket, $request->validated());

        return $this->success(
            new TicketResource($ticket),
            "Ticket updated successfully",
        );
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize("delete", $ticket);

        $this->ticketService->delete($ticket);

        return $this->success(null, "Ticket deleted successfully");
    }

    public function changeStatus(
        ChangeStatusRequest $request,
        Ticket $ticket,
    ): JsonResponse {
        // Authorize based on status - if closing, check 'close' permission, otherwise 'update'
        if ($request->validated()["status"] === "closed") {
            $this->authorize("close", $ticket);
        } else {
            $this->authorize("update", $ticket);
        }

        $ticket = $this->ticketService->changeStatus(
            $ticket,
            $request->validated()["status"],
        );

        return $this->success(
            new TicketResource($ticket),
            "Ticket status updated successfully",
        );
    }

    public function assign(
        AssignTicketRequest $request,
        Ticket $ticket,
    ): JsonResponse {
        $this->authorize("assign", $ticket);

        $ticket = $this->ticketService->assign(
            $ticket,
            $request->validated()["assigned_to"],
            $request->user(),
        );

        return $this->success(
            new TicketResource($ticket),
            "Ticket assigned successfully",
        );
    }
}
