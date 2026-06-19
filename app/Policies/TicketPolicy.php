<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /** Super admin can do anything (cross-tenant). */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true; // listing is filtered per-role in the controller
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isCompanyAdmin()) {
            return true;
        }

        // Warehouse managers may open a ticket only when it has a parts request to fulfil.
        if ($user->isWarehouseManager() && $ticket->partRequests()->exists()) {
            return true;
        }

        return $ticket->created_by === $user->id
            || $ticket->assigned_to === $user->id
            || $user->managesDepartment($ticket->department_id);
    }

    public function create(User $user): bool
    {
        return true; // any authenticated user may open a ticket
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isCompanyAdmin()
            || ($ticket->created_by === $user->id && $ticket->status === Ticket::STATUS_OPEN);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isCompanyAdmin();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow abilities
    |--------------------------------------------------------------------------
    */

    /** Assign / approve / reject — department head of the ticket's department (or admin). */
    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->isCompanyAdmin() || $user->managesDepartment($ticket->department_id);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->manage($user, $ticket);
    }

    public function approve(User $user, Ticket $ticket): bool
    {
        return $this->manage($user, $ticket);
    }

    /** Accept / start / pause / resolve — the assigned technician (or admin). */
    public function work(User $user, Ticket $ticket): bool
    {
        return $user->isCompanyAdmin() || $ticket->assigned_to === $user->id;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return $user->isCompanyAdmin()
            || $user->managesDepartment($ticket->department_id)
            || $ticket->created_by === $user->id;
    }
}
