<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A subscription lifecycle notice to a company's admins: an expiry reminder
 * (`expiring`) or a suspension (`suspended`). Stored as a database notification
 * and rendered by the in-app bell.
 */
class SubscriptionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Company $company,
        public string $event,   // 'expiring' | 'grace' | 'suspended'
        public string $message,
        public ?int $daysRemaining = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription',
            'event' => $this->event,
            'message' => $this->message,
            'company_id' => $this->company->id,
            'days_remaining' => $this->daysRemaining,
            'icon' => $this->event === 'suspended' ? 'fa-ban' : 'fa-clock',
            'color' => $this->event === 'suspended' ? 'red' : 'amber',
            'url' => route('dashboard', absolute: false),
        ];
    }
}
