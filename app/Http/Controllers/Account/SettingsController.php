<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function privacy(Request $request): View
    {
        return view('account.settings.privacy', ['me' => $request->user()]);
    }

    public function updatePrivacy(Request $request): RedirectResponse
    {
        $data = $request->validate(['who_can_message' => ['required', Rule::in(['matched', 'none'])]]);
        $request->user()->forceFill(['privacy' => [
            'show_photo' => $request->boolean('show_photo'),
            'show_contributions' => $request->boolean('show_contributions'),
            'show_upazila' => $request->boolean('show_upazila'),
            'who_can_message' => $data['who_can_message'],
            'notify_email' => $request->boolean('notify_email'),
        ]])->save();

        return back()->with('success', 'Privacy settings saved.');
    }

    public function security(Request $request): View
    {
        return view('account.settings.security', [
            'me' => $request->user(),
            'sessions' => DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get()
                ->map(fn ($s) => (object) [
                    'current' => $s->id === $request->session()->getId(),
                    'agent' => self::describeAgent($s->user_agent),
                    'ip' => preg_replace('/^(\d+)\.\d+\.\d+\.(\d+)$/', '$1.••.••.$2', (string) $s->ip_address),
                    'last_active' => Carbon::createFromTimestamp($s->last_activity),
                ]),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ]);
        $request->user()->forceFill(['password' => $data['password']])->save();
        Auth::logoutOtherDevices($data['password']);
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

        return back()->with('success', 'Password updated. Other devices were signed out.');
    }

    public function destroySessions(Request $request): RedirectResponse
    {
        $count = DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

        return back()->with('success', "Signed out {$count} other ".str('session')->plural($count).'.');
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();

        DB::transaction(function () use ($user) {
            // Close everything the member had open so nobody waits on a deleted account.
            $user->listings()->whereIn('status', [ListingStatus::Draft, ListingStatus::PendingReview, ListingStatus::Available, ListingStatus::Requested, ListingStatus::Reserved, ListingStatus::Paused])
                ->update(['status' => ListingStatus::Cancelled]);
            $user->medicineRequests()->whereIn('status', RequestStatus::open())->update(['status' => RequestStatus::Cancelled]);
            $user->savedListings()->detach();
            // Free the email/phone and remove personal details; the soft-deleted row keeps history consistent.
            $user->forceFill(['email' => null, 'phone' => null, 'name' => 'Deleted member', 'avatar_path' => null, 'privacy' => null])->save();
            $user->delete();
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Your account has been deleted.');
    }

    private static function describeAgent(?string $agent): string
    {
        $agent = (string) $agent;
        $browser = collect(['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'])->first(fn ($n, $k) => str_contains($agent, $k)) ?? 'Browser';
        $os = collect(['Android' => 'Android', 'iPhone' => 'iOS', 'Windows' => 'Windows', 'Mac OS' => 'macOS', 'Linux' => 'Linux'])->first(fn ($n, $k) => str_contains($agent, $k)) ?? 'unknown device';

        return "{$browser} on {$os}";
    }
}
