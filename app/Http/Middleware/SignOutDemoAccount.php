<?php

namespace App\Http\Middleware;

use App\Support\ModeDemo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends a demo session before the login page renders, so a visitor who tried the demo can sign in
 * with their own account instead of being bounced back to the demo dashboard as "already signed in".
 * Runs ahead of the `guest` middleware, which only sees a real session.
 */
class SignOutDemoAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (ModeDemo::aktif()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}
