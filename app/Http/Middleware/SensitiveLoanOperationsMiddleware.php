<?php

namespace App\Http\Middleware;

use App\Services\LoanAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SensitiveLoanOperationsMiddleware
{
    public function __construct(private LoanAccessService $loanAccessService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!$this->loanAccessService->canManageSensitiveLoanOperations($request->user())) {
            abort(403, 'Access denied. Only the Super Administrator or Administrator can manage sensitive loan operations.');
        }

        return $next($request);
    }
}
