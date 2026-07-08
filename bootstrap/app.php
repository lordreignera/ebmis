<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register Spatie Permission middleware
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'loan_operations_admin' => \App\Http\Middleware\SensitiveLoanOperationsMiddleware::class,
            'staff_payment_rollout' => \App\Http\Middleware\StaffPaymentRolloutAccessMiddleware::class,
            'ebims_module' => \App\Http\Middleware\EbimsModuleAccess::class,
            'ebmis_permission' => \App\Http\Middleware\EbimsPermissionAccess::class,
            'approved_school' => \App\Http\Middleware\EnsureSchoolIsApproved::class,
            'check_password_change' => \App\Http\Middleware\CheckPasswordChange::class,
        ]);
        
        // Add password change check to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\CheckPasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 419 && request()->is('login') && !request()->expectsJson()) {
                return redirect()->route('login', ['expired' => 1]);
            }

            return $response;
        });
    })->create();
