<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /** Mark one notification read, then go to its ticket. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        // Redirect to the PATH only, dropping any scheme/host that may have been baked
        // in (e.g. an old absolute http://localhost link). This keeps the user on the
        // same host/port they are browsing from, so it works under artisan or Apache.
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return redirect($path . ($query ? '?' . $query : ''));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة');
    }
}
