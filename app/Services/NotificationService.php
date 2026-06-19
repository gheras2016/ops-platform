<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function list()
    {
        return Notification::with(['user'])
            ->latest()
            ->paginate(20);
    }

    public function create(array $data)
    {
        return Notification::create($data);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return $notification;
    }

    public function delete(Notification $notification)
    {
        return $notification->delete();
    }
}
