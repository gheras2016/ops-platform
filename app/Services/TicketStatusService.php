<?php

namespace App\Services;

use App\Models\TicketStatus;

class TicketStatusService
{
    public function list()
    {
        return TicketStatus::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return TicketStatus::create($data);
    }

    public function update(TicketStatus $status, array $data)
    {
        $status->update($data);
        return $status;
    }

    public function delete(TicketStatus $status)
    {
        return $status->delete();
    }
}
