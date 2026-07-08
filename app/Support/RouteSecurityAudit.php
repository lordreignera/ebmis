<?php

namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RouteSecurityAudit
{
    public function __construct(private Router $router) {}

    public function matrix(): Collection
    {
        return collect($this->router->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => Str::startsWith((string) $route->getName(), 'admin.'))
            ->map(function (Route $route): array {
                $middleware = $route->gatherMiddleware();
                $isSuperAdmin = in_array('super_admin', $middleware, true);

                return [
                    'name' => (string) $route->getName(),
                    'methods' => implode('|', array_diff($route->methods(), ['HEAD'])),
                    'uri' => $route->uri(),
                    'access' => $isSuperAdmin ? 'super_admin' : 'operational',
                    'permission' => $isSuperAdmin
                        ? null
                        : EbmisPermissionRegistry::routePermission($route->getName()),
                    'middleware' => $middleware,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    public function issues(): Collection
    {
        $issues = collect();

        foreach ($this->matrix() as $route) {
            if (!$this->hasAuthenticationMiddleware($route['middleware'])) {
                $issues->push("Admin route {$route['name']} is missing authentication middleware.");
            }

            if ($route['access'] === 'super_admin') {
                continue;
            }

            if (!in_array('ebims_module', $route['middleware'], true)) {
                $issues->push("Operational route {$route['name']} is missing ebims_module middleware.");
            }

            if (!in_array('ebmis_permission', $route['middleware'], true)) {
                $issues->push("Operational route {$route['name']} is missing ebmis_permission middleware.");
            }

            if (!$route['permission']) {
                $issues->push("Operational route {$route['name']} has no explicit permission mapping.");
            }
        }

        foreach (config('ebmis_permissions.route_permissions', []) as $routeName => $permission) {
            if (str_contains($routeName, '*')) {
                $issues->push("Permission mapping {$routeName} uses a wildcard. Map each route explicitly.");
            }

            if (!$this->router->getRoutes()->getByName($routeName)) {
                $issues->push("Permission mapping {$routeName} points to a missing route.");
            }

            if (!$permission) {
                $issues->push("Permission mapping {$routeName} has an empty permission.");
            }
        }

        foreach (config('ebmis_permissions.sensitive_super_admin_routes', []) as $routeName) {
            $route = $this->router->getRoutes()->getByName($routeName);

            if (!$route) {
                $issues->push("Sensitive route {$routeName} does not exist.");
                continue;
            }

            if (!in_array('super_admin', $route->gatherMiddleware(), true)) {
                $issues->push("Sensitive route {$routeName} must require super_admin middleware.");
            }
        }

        foreach (config('ebmis_permissions.sensitive_loan_operations_admin_routes', []) as $routeName) {
            $route = $this->router->getRoutes()->getByName($routeName);

            if (!$route) {
                $issues->push("Sensitive loan operation route {$routeName} does not exist.");
                continue;
            }

            if (!in_array('loan_operations_admin', $route->gatherMiddleware(), true)) {
                $issues->push("Sensitive loan operation route {$routeName} must require loan_operations_admin middleware.");
            }
        }

        foreach (config('ebmis_permissions.sensitive_staff_payment_rollout_routes', []) as $routeName) {
            $route = $this->router->getRoutes()->getByName($routeName);

            if (!$route) {
                $issues->push("Sensitive staff payment rollout route {$routeName} does not exist.");
                continue;
            }

            if (!in_array('staff_payment_rollout', $route->gatherMiddleware(), true)) {
                $issues->push("Sensitive staff payment rollout route {$routeName} must require staff_payment_rollout middleware.");
            }
        }

        foreach (config('ebmis_permissions.public_application_routes', []) as $routeName) {
            if (!$this->router->getRoutes()->getByName($routeName)) {
                $issues->push("Public application route {$routeName} does not exist.");
            }
        }

        foreach (config('ebmis_permissions.throttled_public_routes', []) as $routeName) {
            $route = $this->router->getRoutes()->getByName($routeName);

            if (!$route) {
                $issues->push("Throttled public route {$routeName} does not exist.");
                continue;
            }

            if (!$this->hasThrottleMiddleware($route->gatherMiddleware())) {
                $issues->push("Public route {$routeName} must include throttle middleware.");
            }
        }

        foreach (config('ebmis_permissions.authenticated_application_routes', []) as $routeName) {
            $route = $this->router->getRoutes()->getByName($routeName);

            if (!$route) {
                $issues->push("Authenticated application route {$routeName} does not exist.");
                continue;
            }

            if (!$this->hasAuthenticationMiddleware($route->gatherMiddleware())) {
                $issues->push("Application route {$routeName} must include authentication middleware.");
            }
        }

        foreach ($this->schoolWorkspaceRoutes() as $route) {
            if (!$this->hasAuthenticationMiddleware($route->gatherMiddleware())) {
                $issues->push("School workspace route {$route->getName()} is missing authentication middleware.");
            }

            if ($route->getName() !== 'school.dashboard'
                && !in_array('approved_school', $route->gatherMiddleware(), true)) {
                $issues->push("School workspace route {$route->getName()} is missing approved_school middleware.");
            }
        }

        return $issues->unique()->values();
    }

    public function warnings(): Collection
    {
        $warnings = collect();

        if ((string) config('app.cron_secret', '') === '') {
            $warnings->push('CRON_SECRET is empty. The cron endpoint will reject all requests until it is configured.');
        }

        if (config('flexipay.require_callback_secret') && (string) config('flexipay.callback_secret', '') === '') {
            $warnings->push('FLEXIPAY_CALLBACK_SECRET is empty. Mobile-money callbacks will be rejected until it is configured.');
        }

        return $warnings;
    }

    public function summary(): array
    {
        $matrix = $this->matrix();

        return [
            'admin_routes' => $matrix->count(),
            'super_admin_routes' => $matrix->where('access', 'super_admin')->count(),
            'operational_routes' => $matrix->where('access', 'operational')->count(),
            'permission_mappings' => count(config('ebmis_permissions.route_permissions', [])),
            'school_workspace_routes' => $this->schoolWorkspaceRoutes()->count(),
            'public_application_routes' => count(config('ebmis_permissions.public_application_routes', [])),
            'issues' => $this->issues()->count(),
            'warnings' => $this->warnings()->count(),
        ];
    }

    private function hasAuthenticationMiddleware(array $middleware): bool
    {
        return collect($middleware)->contains(fn (string $name) => Str::startsWith($name, 'auth'));
    }

    private function hasThrottleMiddleware(array $middleware): bool
    {
        return collect($middleware)->contains(fn (string $name) => Str::startsWith($name, 'throttle'));
    }

    private function schoolWorkspaceRoutes(): Collection
    {
        $publicRoutes = config('ebmis_permissions.public_application_routes', []);

        return collect($this->router->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => Str::startsWith((string) $route->getName(), 'school.'))
            ->reject(fn (Route $route) => in_array($route->getName(), $publicRoutes, true))
            ->values();
    }
}
