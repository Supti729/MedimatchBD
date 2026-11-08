<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

/** The member's own (private) profile. The public view is MemberController. */
class ProfileController extends Controller
{
    public function show(): View
    {
        $me = SampleData::currentUser();

        return view('account.profile.show', [
            'me' => $me,
            'listingCount' => SampleData::listings()->filter(fn ($l) => $l->owner->id === $me->id)->count(),
            'requestCount' => SampleData::requests()->filter(fn ($r) => $r->owner->id === $me->id)->count(),
        ]);
    }

    public function edit(): View
    {
        return view('account.profile.edit', ['me' => SampleData::currentUser()]);
    }
}
