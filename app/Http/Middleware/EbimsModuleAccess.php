<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EbimsModuleAccess
{
    /**
     * Handle an incoming request.
     * Allows administrators, branch managers, and loan officers to access EBIMS modules.
     * Sensitive operations remain protected by their dedicated middleware and controllers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        $hasEbimsAccess = $user->isSuperAdmin()
                        || $user->hasRole('Branch Manager')
                        || $user->hasAnyRole(['Loan Officer', 'Field Officer']);

        if (!$hasEbimsAccess) {
            abort(403, 'Access denied. You do not have permission to access EBIMS modules.');
        }

        return $next($request);
    }
}
