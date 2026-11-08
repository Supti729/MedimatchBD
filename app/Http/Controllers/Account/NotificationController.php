<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Database notifications. A member can only ever see or change their own. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $user = $request->user();

        return view('account.notifications', [
            'notifications' => ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())->latest()->paginate(20)->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Opening a notification marks it as read and goes to the page it is about. */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $n = $request->user()->notifications()->findOrFail($notification);
        $n->markAsRead();
        $url = $n->data['url'] ?? route('notifications.index');

        // Only ever redirect within this site.
        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('notifications.index'));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
