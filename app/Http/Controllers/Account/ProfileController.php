<?php

namespace App\Http\Controllers\Account;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Services\AccountVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** The member's own (private) profile. The public view is MemberController. */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $me = $request->user()->load('location');

        return view('account.profile.show', [
            'me' => $me,
            'listingCount' => $me->listings()->count(),
            'requestCount' => $me->medicineRequests()->count(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('account.profile.edit', ['me' => $request->user()->load('location')]);
    }

    public function update(ProfileRequest $request, AccountVerificationService $verification): RedirectResponse
    {
        $user = $request->user();
        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'location_id' => $request->location()->id,
        ]);

        // A changed email or phone must be verified again.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();

        if (! $user->isVerifiedAccount()) {
            $user->forceFill(['status' => AccountStatus::Pending])->save();
            $code = $verification->issue($user);

            return redirect()->route('verification.notice')->with('warning', 'You changed your contact details. Please verify your account again.')
                ->with('dev_code', config('medimatch.show_dev_codes') ? $code : null);
        }

        return redirect()->route('profile.show')->with('success', 'Profile updated.');
    }
}
