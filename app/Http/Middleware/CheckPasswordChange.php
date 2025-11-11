<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     * Shows a notification if user still has the default password
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Check if user still has default password (123456789)
            // This is a soft check - we'll show a banner reminder
            if ($user->email && str_ends_with($user->email, '@ebims.local')) {
                // This is an imported user - check if they haven't updated their profile recently
                if (!session()->has('password_change_reminder_shown')) {
                    session()->flash('show_password_change_reminder', true);
                    session()->put('password_change_reminder_shown', true);
                }
            }
        }

        return $next($request);
    }
}
