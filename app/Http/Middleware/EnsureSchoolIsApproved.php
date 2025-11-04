<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Check if user is a school user
        if ($user->user_type !== 'school') {
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. School account required.');
        }

        // Check if school exists
        if (!$user->school) {
            return redirect()->route('dashboard')
                ->with('error', 'School record not found.');
        }

        // Check if school is approved
        if ($user->school->status !== 'approved') {
            return redirect()->route('school.dashboard')
                ->with('error', 'Your school must be approved before accessing this feature.');
        }

        return $next($request);
    }
}
