<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// A seeded notification (old absolute URL) + what the controller will now redirect to.
$row = DB::table('notifications')->first();
if (! $row) {
    echo "no notifications seeded\n";
    return;
}
$data = json_decode($row->data, true);
$url = $data['url'] ?? '(none)';
$path = parse_url($url, PHP_URL_PATH) ?: '/';
$query = parse_url($url, PHP_URL_QUERY);
$redirect = $path . ($query ? '?' . $query : '');

echo "stored url   : {$url}\n";
echo "redirects to : {$redirect}\n";

// Confirm new notifications now store a relative path.
$ticket = \App\Models\Ticket::withoutGlobalScopes()->first();
echo "new url form : " . route('tickets.show', $ticket, false) . "\n";
