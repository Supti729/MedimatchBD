<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Members must confirm their email or phone before using member features. */
class EnsureAccountIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isVerifiedAccount()) {
            return redirect()->route('verification.notice')->with('warning', 'Please verify your account first.');
        }

        return $next($request);
    }
}
