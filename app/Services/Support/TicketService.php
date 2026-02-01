<?php

namespace App\Services\Support;

use App\Models\Device;
use App\Models\Ticket;
use App\Models\User;
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

        return $ticket->fresh();
    }

    /**
     * Update a ticket.
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->fresh();
    }

    /**
     * Change ticket status.
     */
    public function changeStatus(Ticket $ticket, string $status): Ticket
    {
        $oldStatus = $ticket->status;

        $ticket->changeStatus($status);

        // Audit log
        activity("ticket")
            ->event('changed_status')
            ->performedOn($ticket)
            ->withProperties(['ticket_id' => $ticket->id])
            ->log("Changed status from \"$oldStatus->value\" to \"$ticket->status->value\" for ticket \"$ticket->subject\"");

        return $ticket->fresh();
    }

    /**
     * Assign ticket to a user.
     */
    public function assign(
        Ticket $ticket,
        int $assignedToUserId,
        User $assignedBy,
    ): Ticket {
        $assignedToUser = User::find($assignedToUserId);
        $ticket->assign(User::findOrFail($assignedToUserId));

        // Audit log
        activity("ticket")
            ->event('assigned')
            ->performedOn($ticket)
            ->withProperties([
                'ticket_id' => $ticket->id,
                'assigned_user_id' => $assignedToUser->id,
            ])
            ->log("Assigned ticket \"{$ticket->subject}\" to user \"{$assignedToUser->email}\"");

        // TODO: Send notification to assigned user
        // event(new TicketAssigned($ticket));

        return $ticket->fresh();
    }

    /**
     * Delete a ticket (soft delete).
     *
     * @param Ticket $ticket
     * @return bool
     */
    public function delete(Ticket $ticket): bool
    {

        return $ticket->delete();
    }

    /**
     * Get ticket statistics for a user.
     *
     * @param User $user
     * @return array
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

        // Total waiting (assuming 'waiting' is a valid status or use 'reopened')
        $totalWaiting = (clone $query)->where("status", "reopened")->count();

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
            "total_waiting" => $totalWaiting,
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
}
