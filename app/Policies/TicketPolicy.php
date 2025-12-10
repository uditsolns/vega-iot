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
        if ($ticket->user_id === $user->id && $ticket->is_open) {
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
     * Determine if the user can add comments to a ticket.
     * Users who can view the ticket can comment.
     */
    public function addComment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * Determine if the user can view internal comments.
     * Only super admins or assigned users can view internal comments.
     */
    public function viewInternalComments(User $user, Ticket $ticket): bool
    {
        // Super admins can view internal comments
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Assigned user can view internal comments
        if ($ticket->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Helper method to check if user can access a ticket.
     */
    private function userCanAccessTicket(User $user, Ticket $ticket): bool
    {
        // Super admins can access all tickets
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Ticket creator can access their own tickets
        if ($ticket->user_id === $user->id) {
            return true;
        }

        // Assigned user can access assigned tickets
        if ($ticket->assigned_to === $user->id) {
            return true;
        }

        // Ticket must be in user's company
        if ($ticket->company_id !== $user->company_id) {
            return false;
        }

        // If user has area restrictions, check ticket's area
        if ($user->hasAreaRestrictions) {
            // If ticket has an area_id, user must have access to it
            if ($ticket->area_id) {
                return in_array($ticket->area_id, $user->allowedAreas);
            }

            // If ticket doesn't have area but has location/hub, deny access for area-restricted users
            // Area-restricted users should only see tickets explicitly in their areas
            if ($ticket->location_id || $ticket->hub_id) {
                return false;
            }
        }

        // Users in same company without area restrictions can view
        return true;
    }
}
