<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EbimsModuleAccess
{
    /**
     * Handle an incoming request.
     * Allows Super Administrators and Branch Managers to access EBIMS modules
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Check if user has Super Administrator role OR Branch Manager role
        $hasEbimsAccess = $user->hasRole('Super Administrator') 
                        || $user->hasRole('superadmin')
                        || $user->hasRole('Branch Manager')
                        || $user->user_type === 'super_admin';

        if (!$hasEbimsAccess) {
            abort(403, 'Access denied. You do not have permission to access EBIMS modules.');
        }

        return $next($request);
    }
}
