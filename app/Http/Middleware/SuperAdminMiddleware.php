<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Check if user has Super Administrator, Branch Manager, or Administrator role
        $hasAccess = $user->hasRole('Super Administrator') 
                        || $user->hasRole('superadmin')
                        || $user->hasRole('Branch Manager')
                        || $user->hasRole('Administrator')
                        || $user->user_type === 'super_admin';

        if (!$hasAccess) {
            abort(403, 'Access denied. Administrator role required.');
        }

        return $next($request);
    }
}
