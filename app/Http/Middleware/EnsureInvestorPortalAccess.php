<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvestorPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('investor');

        if (!$guard->check()) {
            return redirect()->route('investor.login');
        }

        $investor = $guard->user();

        if (!$investor->can_access_portal) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('investor.login')
                ->with('error', 'Your investor account is not currently approved for portal access.');
        }

        return $next($request);
    }
}
