<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AccountVerificationController extends Controller
{
    public function __construct(private AccountVerificationService $service) {}

    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->isVerifiedAccount()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-account', ['channel' => $this->service->channelFor($request->user())]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $this->service->verify($request->user(), $request->input('code'));

        return redirect()->route('dashboard')->with('success', 'Your account is verified. Welcome to Medimatch BD!');
    }

    public function resend(Request $request): RedirectResponse
    {
        $key = 'verification-resend:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->with('warning', 'Please wait '.RateLimiter::availableIn($key).' seconds before requesting another code.');
        }
        RateLimiter::hit($key, 60);
        $code = $this->service->issue($request->user());

        return back()->with('success', 'A new code has been issued.')->with('dev_code', config('medimatch.show_dev_codes') ? $code : null);
    }
}
