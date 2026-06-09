<?php

namespace App\Http\Middleware;

use App\Support\EbmisPermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EbimsPermissionAccess
{
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

        $permission = EbmisPermissionRegistry::routePermission($request->route()?->getName());

        if (!$permission) {
            abort(403, 'Access denied. This operational route has not been assigned a permission.');
        }

        if (!$user->can($permission)) {
            abort(403, "Access denied. Your role does not include the {$permission} permission.");
        }

        return $next($request);
    }
}
