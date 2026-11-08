<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = Str::lower(trim((string) $request->query('q')));
        $status = AccountStatus::tryFrom((string) $request->query('status'));

        return view('admin.users.index', [
            'users' => SampleData::users()
                ->when($q, fn ($c) => $c->filter(fn ($u) => str_contains(Str::lower($u->name.' '.$u->email.' '.$u->district), $q)))
                ->when($status, fn ($c) => $c->where('status', $status)),
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function show(int $user): View
    {
        $user = SampleData::user($user);
        abort_unless($user, 404);

        return view('admin.users.show', [
            'user' => $user,
            'listings' => SampleData::listings()->filter(fn ($l) => $l->owner->id === $user->id),
            'requests' => SampleData::requests()->filter(fn ($r) => $r->owner->id === $user->id),
            'reports' => SampleData::reports()->filter(fn ($r) => $r->reported_user->id === $user->id),
        ]);
    }
}
