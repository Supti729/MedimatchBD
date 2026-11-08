<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $all = SampleData::notifications();

        return view('account.notifications', [
            'notifications' => $filter === 'unread' ? $all->where('read', false) : $all,
            'filter' => $filter,
            'unreadCount' => $all->where('read', false)->count(),
        ]);
    }
}
