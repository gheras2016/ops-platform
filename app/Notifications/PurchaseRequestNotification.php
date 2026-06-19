<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PurchaseRequestNotification extends Notification
{
    use Queueable;

    public const META = [
        'submitted' => ['fa-file-circle-plus', 'amber'],
        'dept_approved' => ['fa-user-check', 'indigo'],
        'finance_pending' => ['fa-file-invoice-dollar', 'orange'],
        'approved' => ['fa-circle-check', 'green'],
        'rejected' => ['fa-ban', 'red'],
        'received' => ['fa-box-open', 'teal'],
    ];

    public function __construct(
        public PurchaseRequest $pr,
        public string $event,
        public string $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        [$icon, $color] = self::META[$this->event] ?? ['fa-bell', 'gray'];

        return [
            'purchase_request_id' => $this->pr->id,
            'request_number' => $this->pr->request_number,
            'event' => $this->event,
            'message' => $this->message,
            'icon' => $icon,
            'color' => $color,
            // Relative path (no host) — host/port-agnostic link. See TicketNotification.
            'url' => route('purchase-requests.show', $this->pr, false),
        ];
    }
}
