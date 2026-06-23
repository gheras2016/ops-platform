<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single database notification raised on ticket lifecycle events.
 * The workflow service decides recipients; this just formats the payload.
 */
class TicketNotification extends Notification
{
    use Queueable;

    /** event type => [icon, color] for the bell UI. */
    public const META = [
        'assigned' => ['fa-user-check', 'indigo'],
        'accepted' => ['fa-handshake', 'blue'],
        'started'  => ['fa-play', 'amber'],
        'paused'   => ['fa-pause', 'orange'],
        'resumed'  => ['fa-play', 'amber'],
        'resolved' => ['fa-flag-checkered', 'teal'],
        'approved' => ['fa-circle-check', 'green'],
        'rejected' => ['fa-rotate-left', 'red'],
        'commented'=> ['fa-comment', 'slate'],
        'progress' => ['fa-bars-progress', 'blue'],
        'part_requested' => ['fa-cart-plus', 'amber'],
        'part_approved'  => ['fa-clipboard-check', 'indigo'],
        'part_rejected'  => ['fa-ban', 'red'],
        'part_issued'    => ['fa-dolly', 'green'],
        'procurement'      => ['fa-truck', 'blue'],
        'purchase_approved'=> ['fa-circle-check', 'green'],
        'purchase_rejected'=> ['fa-ban', 'red'],
        'stock_received'   => ['fa-box-open', 'teal'],
    ];

    public function __construct(
        public Ticket $ticket,
        public string $event,
        public string $message,
        public ?User $actor = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    /** Push payload for the FCM channel (background/closed-app delivery). */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'بلاغ ' . $this->ticket->ticket_number,
            'body' => $this->message,
            'data' => [
                'ticket_id' => $this->ticket->id,
                'event' => $this->event,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        [$icon, $color] = self::META[$this->event] ?? ['fa-bell', 'gray'];

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_title' => $this->ticket->title,
            'event' => $this->event,
            'message' => $this->message,
            'actor' => $this->actor?->name,
            'icon' => $icon,
            'color' => $color,
            // Relative path (no host) so the link works regardless of how the app is
            // served (artisan :8000 / Apache) and never points at a stale APP_URL host.
            'url' => route('tickets.show', $this->ticket, false),
        ];
    }
}
