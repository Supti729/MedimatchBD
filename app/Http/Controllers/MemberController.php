<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Models\MedicineListing;
use App\Models\User;
use Illuminate\View\View;

/** Public member profile — minimum information only (no phone, email, address or documents). */
class MemberController extends Controller
{
    public function show(User $member): View
    {
        abort_if($member->isStaff() || $member->status !== AccountStatus::Active, 404);

        return view('members.show', [
            'member' => $member,
            'listings' => MedicineListing::publiclyVisible()->withCardData()->where('user_id', $member->id)->latest('published_at')->get(),
        ]);
    }
}
