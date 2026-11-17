<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function readAll()
    {
        // toBase(): a plain UPDATE that leaves updated_at alone — the bell orders by it
        // (grouped notifications are bumped on new activity), so marking as read must not reshuffle.
        auth()->user()->unreadNotifications()->getQuery()->toBase()->update(['read_at' => now()]);

        return redirect()->back();
    }

    // The bell links here rather than straight to the page: opening a notification marks it as
    // read, then lands on the locality group (or the work page, for the weekly digest).
    public function open(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        $notification->markAsRead();

        $groupId = (int) ($notification->data['group_id'] ?? 0);

        return redirect()->route('georef.index', $groupId > 0 ? ['group' => $groupId] : []);
    }
}
