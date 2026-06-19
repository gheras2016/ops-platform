<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

/**
 * Mobile API for the notification bell. Backed by Laravel's standard database
 * notifications ($user->notifications()), the same store the web app uses.
 */
class NotificationController extends Controller
{
    /** Paginated notifications for the current user (newest first). */
    public function index(Request $request)
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread')) {
            $query = $request->user()->unreadNotifications();
        }

        return NotificationResource::collection($query->paginate(20));
    }

    /** Lightweight unread count for the tab badge. */
    public function unreadCount(Request $request)
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    /** Mark a single notification read. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return new NotificationResource($notification->fresh());
    }

    /** Mark every notification read. */
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['count' => 0]);
    }
}
