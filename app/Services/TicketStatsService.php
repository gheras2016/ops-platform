<?php

namespace App\Services;

use App\Models\Ticket;

class TicketStatsService
{
    public static function getStats()
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::where('status_id', 1)->count(),
            'closed' => Ticket::where('status_id', 2)->count(),
            'with_items' => Ticket::whereNotNull('item_id')->count(),
        ];
    }
}
