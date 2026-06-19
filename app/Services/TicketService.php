<?php

namespace App\Services;

use App\Models\Ticket;

class TicketService
{
    /**
     * Get paginated list of tickets with relations
     */
    public function list()
    {
        return Ticket::with([
            'status',
            'priority',
            'requester',
            'asset',
            'department'
        ])->latest()->paginate(20);
    }

    /**
     * Create a new ticket
     */
    public function create(array $data)
    {
        return Ticket::create($data);
    }

    /**
     * Update an existing ticket
     */
    public function update(Ticket $ticket, array $data)
    {
        $ticket->update($data);
        return $ticket;
    }

    /**
     * Delete a ticket
     */
    public function delete(Ticket $ticket)
    {
        return $ticket->delete();
    }
}
