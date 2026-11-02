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

        // The EBIMS home dashboard is the common landing page for staff users.
        // Access to operational screens remains permission-controlled below.
        if ($routeName === 'admin.home') {
            abort_if($user->user_type === 'school', 403, 'School users must use the school dashboard.');

            return $next($request);
        }

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

        // Module landing pages use the workspace permission as their route
        // mapping. Treat an operational module permission as satisfying that
        // umbrella so legacy roles do not lose access just because the
        // umbrella permission was added after the role was created.
        if (
            $permission === 'access-ebmis-modules'
            && EbmisPermissionRegistry::userCanAccessWorkspace($user)
        ) {
            return $next($request);
        }

        if (!$user->can($permission)) {
            abort(403, "Access denied. Your role does not include the {$permission} permission.");
        }

        return $next($request);
    }
}
