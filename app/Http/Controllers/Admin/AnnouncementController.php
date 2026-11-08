<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Location;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Sends in-platform notifications to members. */
class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements', ['items' => Announcement::with('author')->latest()->get(), 'divisions' => array_keys(Location::tree())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'audience_division' => ['nullable', 'required_if:audience,division', Rule::in(array_keys(Location::tree()))],
        ]);

        $announcement = new Announcement($data);
        $announcement->created_by = $request->user()->id;
        $announcement->save();

        $recipients = User::where('role', 'user')->where('status', AccountStatus::Active)
            ->when($data['audience'] === 'division', fn ($q) => $q->whereHas('location', fn ($l) => $l->where('division', $data['audience_division'])))
            ->when($data['audience'] === 'donors', fn ($q) => $q->whereHas('listings', fn ($l) => $l->whereIn('status', ListingStatus::public())));

        $count = 0;
        $recipients->chunkById(200, function ($users) use ($announcement, &$count) {
            Notification::send($users, new AnnouncementPublished($announcement));
            $count += $users->count();
        });
        $announcement->forceFill(['sent_at' => now(), 'recipients_count' => $count])->save();
        AuditLogger::log('announcement.sent', "Announcement #{$announcement->id} \"{$announcement->title}\" to {$count} members", $announcement);

        return back()->with('success', "Announcement sent to {$count} ".str('member')->plural($count).'.');
    }
}
