<?php

namespace App\Providers;

use App\Support\SampleData;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.medimatch');

        // Header data for the member layout (current user, notification dropdown, unread counts).
        // PART 1: sample data. PART 2: auth()->user() and $user->unreadNotifications().
        View::composer('components.layouts.app', function ($view) {
            $notifications = SampleData::notifications();
            $view->with([
                'currentUser' => SampleData::currentUser(),
                'headerNotifications' => $notifications->take(5),
                'unreadCount' => $notifications->where('read', false)->count(),
                'unreadMessages' => SampleData::conversations()->sum('unread'),
            ]);
        });
    }
}
