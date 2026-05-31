<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EbimsModuleAccess
{
    /**
     * Handle an incoming request.
     * Allows administrators and branch managers to access EBIMS modules.
     * Field officers receive a deliberately narrow operational surface.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        $isFieldOfficer = $user->hasAnyRole(['Loan Officer', 'Field Officer']);
        $hasEbimsAccess = $user->isSuperAdmin()
                        || $user->hasRole('Branch Manager')
                        || $isFieldOfficer;

        if (!$hasEbimsAccess) {
            abort(403, 'Access denied. You do not have permission to access EBIMS modules.');
        }

        if ($isFieldOfficer && !$request->routeIs([
            'admin.client-applications.index',
            'admin.client-applications.show',
            'admin.client-applications.verify',
            'admin.client-applications.verify.submit',
            'admin.loans.active',
            'admin.loans.show',
            'admin.loans.collateral.show',
            'admin.loans.collateral.store',
            'admin.loans.follow-ups.store',
            'admin.loans.repayments.schedules',
            'admin.loans.next-schedule',
        ])) {
            abort(403, 'Access denied. This action is outside the Field Officer workspace.');
        }

        return $next($request);
    }
}
