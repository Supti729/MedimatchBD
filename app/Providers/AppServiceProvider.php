<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Conversation;
use App\Models\Interaction;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Partner;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationSubmission;
use App\Support\UnreadMessages;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        // Short, stable names in polymorphic columns instead of PHP class names.
        Relation::enforceMorphMap([
            'user' => User::class,
            'listing' => MedicineListing::class,
            'request' => MedicineRequest::class,
            'medicine' => Medicine::class,
            'conversation' => Conversation::class,
            'interaction' => Interaction::class,
            'verification' => VerificationSubmission::class,
            'report' => Report::class,
            'partner' => Partner::class,
            'announcement' => Announcement::class,
        ]);

        /* ---- Role gates (always checked on the server, never trusted from the browser) ---- */
        Gate::define('access-admin', fn (User $user) => $user->isStaff());          // admin area at all
        Gate::define('admin', fn (User $user) => $user->isAdmin());                 // users, listings, reports, settings…
        Gate::define('review-verifications', fn (User $user) => $user->isReviewer()); // sensitive documents

        View::composer('components.layouts.app', function ($view) {
            $user = auth()->user();
            $view->with([
                'currentUser' => $user,
                'headerNotifications' => $user->notifications()->latest()->limit(5)->get(),
                'unreadCount' => $user->unreadNotifications()->count(),
                'unreadMessages' => UnreadMessages::countFor($user),
            ]);
        });

        View::composer('components.layouts.admin', function ($view) {
            $view->with('currentUser', auth()->user());
        });
    }
}
