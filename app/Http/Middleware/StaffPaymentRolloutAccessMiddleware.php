<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffPaymentRolloutAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->canManageStaffPaymentRollout()) {
            abort(403, 'Access denied. Only the Super Administrator or Administrator can manage staff payment rollout.');
        }

        return $next($request);
    }
}

