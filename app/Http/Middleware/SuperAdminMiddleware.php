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
        
        // Check if user has Super Administrator role OR user_type is super_admin OR has superadmin role
        $isSuperAdmin = $user->hasRole('Super Administrator') 
                        || $user->hasRole('superadmin')
                        || $user->user_type === 'super_admin';

        if (!$isSuperAdmin) {
            abort(403, 'Access denied. Super Administrator role required.');
        }

        return $next($request);
    }
}
