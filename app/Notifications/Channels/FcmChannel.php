<?php

namespace App\Notifications\Channels;

use App\Services\FcmSender;
use Illuminate\Notifications\Notification;

/**
 * Notification channel that pushes to a notifiable's registered devices via FCM.
 * Only notifications implementing `toFcm($notifiable): ['title','body','data']`
 * are delivered.
 */
class FcmChannel
{
    public function __construct(private FcmSender $sender)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm') || ! method_exists($notifiable, 'deviceTokens')) {
            return;
        }

        $tokens = $notifiable->deviceTokens()->pluck('token')->all();
        if (empty($tokens)) {
            return;
        }

        $payload = $notification->toFcm($notifiable);
        $this->sender->send(
            $tokens,
            $payload['title'] ?? 'تنبيه',
            $payload['body'] ?? '',
            $payload['data'] ?? [],
        );
    }
}
