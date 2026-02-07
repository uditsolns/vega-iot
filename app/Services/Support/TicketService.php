<?php

namespace App\Services\Support;

use App\Models\Device;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketReopenedNotification;
use App\Notifications\TicketResolvedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class TicketService
{
    /**
     * Get paginated list of tickets.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Ticket::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("subject"),
                AllowedFilter::exact("user_id"),
                AllowedFilter::exact("status"),
                AllowedFilter::exact("priority"),
                AllowedFilter::exact("assigned_to"),
                AllowedFilter::exact("device_id"),
                AllowedFilter::exact("location_id"),
                AllowedFilter::exact("area_id"),
            ])
            ->allowedSorts(["created_at", "updated_at", "priority", "status"])
            ->allowedIncludes([
                "user",
                "assignedTo",
                "resolvedBy",
                "closedBy",
                "device",
                "location",
                "area",
                "company",
            ])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new ticket.
     */
    public function create(array $data, User $user): Ticket
    {
        // Add user_id and company_id
        $data["user_id"] = $user->id;
        $data["company_id"] = $user->company_id;

        // If device_id provided, populate hierarchy fields
        if (isset($data["device_id"])) {
            $device = Device::find($data["device_id"]);
            if ($device) {
                $data["area_id"] = $device->area_id;
                $data["hub_id"] = $device->area?->hub_id;
                $data["location_id"] = $device->area?->hub?->location_id;
            }
        }

        $ticket = Ticket::create($data);

        // Audit log
        activity("ticket")
            ->event('created')
            ->performedOn($ticket)
            ->withProperties(['ticket_id' => $ticket->id])
            ->log("Created ticket \"$ticket->subject\"");

        // Send notification to support team (users with tickets.view permission in the company)
        $this->notifySupportTeam($ticket, 'created');

        return $ticket->fresh();
    }

    /**
     * Update a ticket.
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        // Audit log
        activity("ticket")
            ->event('updated')
            ->performedOn($ticket)
            ->withProperties(['ticket_id' => $ticket->id])
            ->log("Updated ticket \"$ticket->subject\"");

        return $ticket->fresh();
    }

    /**
     * Assign ticket to a system support user.
     */
    public function assign(
        Ticket $ticket,
        int $assignedToUserId,
        User $assignedBy,
    ): Ticket {
        $assignedToUser = User::findOrFail($assignedToUserId);

        // Validate that assigned user is VEGA's internal support (not a customer user)
        if ($assignedToUser->company_id !== null) {
            throw new \InvalidArgumentException(
                'Only system internal support users can be assigned to tickets. Customer users cannot be assigned.'
            );
        }

        $ticket->assign($assignedToUser);

        // Audit log
        activity("ticket")
            ->event('assigned')
            ->performedOn($ticket)
            ->withProperties([
                'ticket_id' => $ticket->id,
                'assigned_to_user_id' => $assignedToUser->id,
                'assigned_by_user_id' => $assignedBy->id,
            ])
            ->log("Assigned ticket \"{$ticket->subject}\" to system support user \"{$assignedToUser->email}\"");

        // Send notification to assigned VEGA support user
        $assignedToUser->notify(
            new TicketAssignedNotification(
                ticketId: $ticket->id,
                subject: $ticket->subject,
                assignedBy: $assignedBy->id,
            )
        );

        return $ticket->fresh();
    }

    /**
     * Resolve a ticket.
     */
    public function resolve(Ticket $ticket, User $resolvedBy, ?string $comment = null): Ticket
    {
        $ticket->resolve($resolvedBy);

        // Add resolution comment if provided
        if ($comment) {
            $ticket->comments()->create([
                'user_id' => $resolvedBy->id,
                'comment' => $comment,
                'is_internal' => false, // Resolution comments are visible to customer
            ]);
        }

        // Audit log
        activity("ticket")
            ->event('resolved')
            ->performedOn($ticket)
            ->withProperties([
                'ticket_id' => $ticket->id,
                'resolved_by_user_id' => $resolvedBy->id,
            ])
            ->log("Resolved ticket \"{$ticket->subject}\"");

        // Send notification to ticket creator
        $ticket->user->notify(
            new TicketResolvedNotification(
                ticketId: $ticket->id,
                subject: $ticket->subject,
                resolvedBy: $resolvedBy->id,
            )
        );

        return $ticket->fresh();
    }

    /**
     * Close a ticket.
     */
    public function close(Ticket $ticket, User $closedBy): Ticket
    {
        $ticket->close($closedBy);

        // Audit log
        activity("ticket")
            ->event('closed')
            ->performedOn($ticket)
            ->withProperties([
                'ticket_id' => $ticket->id,
                'closed_by_user_id' => $closedBy->id,
            ])
            ->log("Closed ticket \"{$ticket->subject}\"");

        // Note: Closing doesn't send notification to customer
        // They were already notified when it was resolved

        return $ticket->fresh();
    }

    /**
     * Reopen a ticket.
     */
    public function reopen(Ticket $ticket, User $reopenedBy): Ticket
    {
        $ticket->reopen();

        // Audit log
        activity("ticket")
            ->event("reopened")
            ->performedOn($ticket)
            ->withProperties([
                'ticket_id' => $ticket->id,
                'reopened_by_user_id' => $reopenedBy->id,
            ])
            ->log("Reopened ticket \"{$ticket->subject}\"");

        // If customer reopened, notify support team
        if ($ticket->isCreatedBy($reopenedBy)) {
            $this->notifySupportTeam($ticket, 'reopened');
        } else {
            // If support reopened, notify customer
            $ticket->user->notify(
                new TicketReopenedNotification(
                    ticketId: $ticket->id,
                    subject: $ticket->subject,
                    reopenedBy: $reopenedBy->id,
                )
            );
        }

        return $ticket->fresh();
    }

    /**
     * Delete a ticket (soft delete).
     */
    public function delete(Ticket $ticket): bool
    {
        // Audit log
        activity("ticket")
            ->event('deleted')
            ->performedOn($ticket)
            ->withProperties(['ticket_id' => $ticket->id])
            ->log("Deleted ticket \"{$ticket->subject}\"");

        return $ticket->delete();
    }

    /**
     * Get ticket statistics for a user.
     */
    public function getStatistics(User $user): array
    {
        $query = Ticket::forUser($user);

        // Total open tickets
        $totalOpen = (clone $query)->where("status", "open")->count();

        // Total in progress
        $totalInProgress = (clone $query)
            ->where("status", "in_progress")
            ->count();

        // Total reopened
        $totalReopened = (clone $query)->where("status", "reopened")->count();

        // Total resolved this week
        $totalResolvedThisWeek = (clone $query)
            ->where("status", "resolved")
            ->where("resolved_at", ">=", Carbon::now()->startOfWeek())
            ->count();

        // Average resolution time (in hours)
        $avgResolutionSeconds = (clone $query)
            ->whereIn("status", ["resolved", "closed"])
            ->whereNotNull("resolved_at")
            ->selectRaw(
                "AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) as avg_seconds",
            )
            ->value("avg_seconds");

        $avgResolutionTimeHours = $avgResolutionSeconds
            ? round($avgResolutionSeconds / 3600, 2)
            : 0;

        // My assigned count
        $myAssignedCount = (clone $query)
            ->where("assigned_to", $user->id)
            ->whereIn("status", ["open", "in_progress", "reopened"])
            ->count();

        // By priority
        $byPriority = (clone $query)
            ->select("priority", DB::raw("COUNT(*) as count"))
            ->whereIn("status", ["open", "in_progress", "reopened"])
            ->groupBy("priority")
            ->pluck("count", "priority")
            ->toArray();

        return [
            "total_open" => $totalOpen,
            "total_in_progress" => $totalInProgress,
            "total_reopened" => $totalReopened,
            "total_resolved_this_week" => $totalResolvedThisWeek,
            "avg_resolution_time_hours" => $avgResolutionTimeHours,
            "my_assigned_count" => $myAssignedCount,
            "by_priority" => [
                "low" => $byPriority["low"] ?? 0,
                "medium" => $byPriority["medium"] ?? 0,
                "high" => $byPriority["high"] ?? 0,
                "urgent" => $byPriority["urgent"] ?? 0,
            ],
        ];
    }

    /**
     * Notify system's internal support team about ticket event
     */
    private function notifySupportTeam(Ticket $ticket, string $event): void
    {
        // Get system's internal support users (users without company_id = VEGA staff)
        $supportUsers = User::whereNull('company_id')
        ->where('is_active', true)
            ->get();

        // Send appropriate notification based on event
        foreach ($supportUsers as $user) {
            if ($event === 'created') {
                $user->notify(
                    new TicketCreatedNotification(
                        ticketId: $ticket->id,
                        subject: $ticket->subject,
                        priority: $ticket->priority->value,
                        createdBy: $ticket->user_id,
                    )
                );
            } elseif ($event === 'reopened') {
                $user->notify(
                    new TicketReopenedNotification(
                        ticketId: $ticket->id,
                        subject: $ticket->subject,
                        reopenedBy: $ticket->user_id,
                    )
                );
            }
        }
    }
}
