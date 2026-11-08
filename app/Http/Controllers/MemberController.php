<?php

namespace App\Http\Controllers;

use App\Support\SampleData;
use Illuminate\View\View;

/** Public member profile — shows only minimum information (no phone, email or address). */
class MemberController extends Controller
{
    public function show(int $member): View
    {
        $member = SampleData::user($member);
        abort_unless($member && $member->role === 'user', 404);

        return view('members.show', [
            'member' => $member,
            'listings' => SampleData::publicListings()->filter(fn ($l) => $l->owner->id === $member->id),
        ]);
    }
}
