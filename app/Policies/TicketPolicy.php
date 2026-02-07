<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine if the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.view');
    }

    /**
     * Determine if the user can view a specific ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.view')) {
            return false;
        }

        return $this->userCanAccessTicket($user, $ticket);
    }

    /**
     * Determine if the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('tickets.create');
    }

    /**
     * Determine if the user can update a ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.update')) {
            return false;
        }

        return $this->userCanAccessTicket($user, $ticket);
    }

    /**
     * Determine if the user can delete a ticket.
     * Only super admins or ticket creator (if ticket is still open)
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.delete')) {
            return false;
        }

        // Super admins can delete any ticket
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Ticket creator can delete their own ticket if it's still open
        if ($ticket->isCreatedBy($user) && $ticket->is_open) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can assign a ticket.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.assign')) {
            return false;
        }

        return $this->userCanAccessTicket($user, $ticket);
    }

    /**
     * Determine if the user can resolve a ticket.
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.resolve')) {
            return false;
        }

        // Must be assigned to the ticket or be a super admin/manager
        if (!$user->isSuperAdmin() && !$ticket->isAssignedTo($user)) {
            return false;
        }

        return $this->userCanAccessTicket($user, $ticket);
    }

    /**
     * Determine if the user can close a ticket.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.close')) {
            return false;
        }

        return $this->userCanAccessTicket($user, $ticket);
    }

    /**
     * Determine if the user can reopen a ticket.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.reopen')) {
            return false;
        }

        // Ticket creator can reopen their tickets
        if ($ticket->isCreatedBy($user)) {
            return true;
        }

        // Staff with permission can reopen
        return $this->userCanAccessTicket($user, $ticket);
    }

    /**
     * Determine if the user can add comments to a ticket.
     * Users who can view the ticket can comment.
     */
    public function addComment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * Determine if the user can add internal comments.
     * Only system's internal support staff can add internal comments.
     */
    public function addInternalComment(User $user, Ticket $ticket): bool
    {
        // Customer users cannot add internal comments
        if ($user->company_id !== null) {
            return false;
        }

        // system's internal support team with permission can add internal comments
        if (!$user->hasPermission('tickets.add_internal_comments')) {
            return false;
        }

        return $this->isInternalSupportUser($user) || $user->isSuperAdmin();
    }

    /**
     * Determine if the user can view internal comments.
     * Only system's internal support team can view internal comments.
     */
    public function viewInternalComments(User $user, Ticket $ticket): bool
    {
        // Customer users (those with company_id) can NEVER see internal comments
        if ($user->company_id !== null) {
            return false;
        }

        // system's internal support team can see internal comments
        if ($user->isSuperAdmin() || $this->isInternalSupportUser($user)) {
            return true;
        }

        return false;
    }

    /**
     * Helper method to check if user can access a ticket.
     */
    private function userCanAccessTicket(User $user, Ticket $ticket): bool
    {
        // System's internal support team (super admins and support staff) can access all tickets
        if ($user->isSuperAdmin() || $this->isInternalSupportUser($user)) {
            return true;
        }

        // Ticket creator (customer user) can access their own tickets
        if ($ticket->isCreatedBy($user)) {
            return true;
        }

        // Assigned system support user can access assigned tickets
        if ($ticket->isAssignedTo($user)) {
            return true;
        }

        // Customer users in the same company can view each other's tickets
        // (e.g., company admin can see tickets raised by their employees)
        if ($user->company_id && $ticket->company_id === $user->company_id) {
            // If user has area restrictions, check ticket's area
            if ($user->hasAreaRestrictions ?? false) {
                // If ticket has an area_id, user must have access to it
                if ($ticket->area_id) {
                    return in_array($ticket->area_id, $user->allowedAreas ?? []);
                }

                // If ticket doesn't have area but has location/hub, deny access for area-restricted users
                if ($ticket->location_id || $ticket->hub_id) {
                    return false;
                }
            }

            // Users in same company without area restrictions can view
            return true;
        }

        return false;
    }

    /**
     * Check if user is system's internal support staff
     */
    private function isInternalSupportUser(User $user): bool
    {
        // system's internal users have company_id = null
        if ($user->company_id !== null) {
            return false;
        }

        // Must have tickets-related permissions
//        return $user->hasPermission('tickets.view') ||
//            $user->hasPermission('tickets.assign') ||
//            $user->hasPermission('tickets.resolve');
        return true;
    }
}
