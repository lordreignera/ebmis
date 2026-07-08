<?php

namespace App\Http\Middleware;

use App\Services\LoanAccessService;
use App\Support\EbmisPermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EbimsPermissionAccess
{
    private LoanAccessService $loanAccessService;

    public function __construct(?LoanAccessService $loanAccessService = null)
    {
        $this->loanAccessService = $loanAccessService ?? app(LoanAccessService::class);
    }

    /**
     * Enforce the granular permission mapped to the current EBIMS route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (
            in_array($routeName, config('ebmis_permissions.sensitive_loan_operations_admin_routes', []), true)
            && $this->loanAccessService->canManageSensitiveLoanOperations($user)
        ) {
            return $next($request);
        }

        if (
            in_array($routeName, config('ebmis_permissions.sensitive_staff_payment_rollout_routes', []), true)
            && $user->canManageStaffPaymentRollout()
        ) {
            return $next($request);
        }

        $permission = EbmisPermissionRegistry::routePermission($routeName);

        if (!$permission) {
            abort(403, 'Access denied. This operational route has not been assigned a permission.');
        }

        if (!$user->can($permission)) {
            abort(403, "Access denied. Your role does not include the {$permission} permission.");
        }

        return $next($request);
    }
}
