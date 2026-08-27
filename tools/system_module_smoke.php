<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();
$http = $app->make(HttpKernel::class);

$admin = User::query()->where('user_type', 'super_admin')->first();
$school = User::query()->where('user_type', 'school')->whereNotNull('school_id')->first();

$publicRoutes = ['login', 'client.apply', 'school.register'];

$checks = [
    ['Public authentication', 'login', null],
    ['Public client application', 'client.apply', null],
    ['Public school registration', 'school.register', null],
    ['School portal dashboard', 'school.dashboard', $school],
    ['School classes', 'school.classes.index', $school],
    ['School students', 'school.students.index', $school],
    ['School staff', 'school.staff.index', $school],
    ['Admin dashboard', 'admin.home', $admin],
    ['Module workspace', 'admin.modules.dashboard', $admin],
    ['Clients', 'admin.members.index', $admin],
    ['Client applications', 'admin.client-applications.index', $admin],
    ['Groups', 'admin.groups.index', $admin],
    ['Bulk SMS', 'admin.bulk-sms.index', $admin],
    ['Loan applications', 'admin.loans.index', $admin],
    ['Loan approvals', 'admin.loans.approvals', $admin],
    ['Active loans', 'admin.loans.active', $admin],
    ['Active-loan Excel export', 'admin.loans.active.export', $admin, ['format' => 'excel']],
    ['Disbursements', 'admin.disbursements.index', $admin],
    ['Repayments', 'admin.repayments.index', $admin],
    ['Pending repayments', 'admin.repayments.pending', $admin],
    ['Repayment history', 'admin.repayments.history', $admin],
    ['Late fees', 'admin.late-fees.index', $admin],
    ['Fees', 'admin.fees.index', $admin],
    ['Cash securities', 'admin.cash-securities.index', $admin],
    ['Savings', 'admin.savings.index', $admin],
    ['Pending savings', 'admin.savings.pending', $admin],
    ['Approved savings', 'admin.savings.approved', $admin],
    ['Portfolio module', 'admin.modules.loan-portfolio', $admin],
    ['Portfolio pending status', 'admin.portfolio.pending', $admin],
    ['Portfolio approved status', 'admin.portfolio.approved', $admin],
    ['Portfolio active status', 'admin.portfolio.running', $admin],
    ['Portfolio overdue condition', 'admin.portfolio.overdue', $admin],
    ['Portfolio closed status', 'admin.portfolio.paid', $admin],
    ['Portfolio rejected status', 'admin.portfolio.rejected', $admin],
    ['Portfolio restructured status', 'admin.portfolio.restructured', $admin],
    ['Portfolio stopped status', 'admin.portfolio.stopped', $admin],
    ['Portfolio by branch', 'admin.portfolio.branch', $admin],
    ['Portfolio by product', 'admin.portfolio.product', $admin],
    ['Group loan portfolio', 'admin.portfolio.group', $admin],
    ['Operational reports', 'admin.reports.pending-loans', $admin],
    ['Accounting', 'admin.accounting.journal-entries', $admin],
    ['UMRA reporting', 'admin.umra.dashboard', $admin],
    ['Investments', 'admin.investments.index', $admin],
    ['Investment statistics API', 'admin.investments.api.investment-statistics', $admin],
    ['Expenditures', 'admin.expenditures.index', $admin],
    ['School loans', 'admin.school.loans.create', $admin],
    ['School loan portfolio', 'admin.school.loans.portfolio', $admin, ['type' => 'school']],
    ['Access control', 'admin.access-control.index', $admin],
    ['School administration', 'admin.schools.index', $admin],
    ['Admin create school', 'admin.schools.create', $admin],
    ['System settings', 'admin.settings.dashboard', $admin],
];

$results = [];

foreach ($checks as $check) {
    [$module, $routeName, $user, $parameters] = array_pad($check, 4, []);
    if ($user === null && str_starts_with($routeName, 'admin.')) {
        $results[] = compact('module', 'routeName') + [
            'status' => null,
            'result' => 'blocked',
            'detail' => 'No super-admin account exists for a read-only smoke request.',
        ];
        continue;
    }

    if ($user === null && str_starts_with($routeName, 'school.') && ! in_array($routeName, $publicRoutes, true)) {
        $results[] = compact('module', 'routeName') + [
            'status' => null,
            'result' => 'blocked',
            'detail' => 'No school account exists for a read-only smoke request.',
        ];
        continue;
    }

    Auth::forgetGuards();

    if ($user !== null) {
        Auth::guard('web')->setUser($user);
        Auth::guard('sanctum')->setUser($user);
    } else {
        Auth::guard('web')->forgetUser();
    }

    try {
        $path = route($routeName, $parameters, false);
        $request = Request::create($path, 'GET');

        if ($user !== null) {
            $request->setUserResolver(static fn () => $user);
        }

        $response = $http->handle($request);
        $status = $response->getStatusCode();
        $location = $response->headers->get('Location');

        // Switching between the school and admin audit identities can cause
        // Laravel's authenticated-session middleware to invalidate the first
        // synthetic request. Retry once with a fresh guard; real browser
        // sessions do not switch identities inside one process this way.
        if ($user !== null && $status === 302 && $location !== null && str_ends_with($location, '/login')) {
            $http->terminate($request, $response);
            Auth::forgetGuards();
            Auth::guard('web')->setUser($user);
            Auth::guard('sanctum')->setUser($user);
            $request = Request::create($path, 'GET');
            $request->setUserResolver(static fn () => $user);
            $response = $http->handle($request);
            $status = $response->getStatusCode();
            $location = $response->headers->get('Location');
        }

        $results[] = compact('module', 'routeName', 'status') + [
            'result' => $status >= 200 && $status < 400 ? 'pass' : 'fail',
            'detail' => $location ? 'Redirect: '.$location : $response->headers->get('Content-Type'),
        ];

        $http->terminate($request, $response);
    } catch (Throwable $exception) {
        $results[] = compact('module', 'routeName') + [
            'status' => 500,
            'result' => 'fail',
            'detail' => $exception::class.': '.$exception->getMessage(),
        ];
    }
}

$summary = [
    'generated_at' => now()->toIso8601String(),
    'read_only' => true,
    'total' => count($results),
    'passed' => count(array_filter($results, static fn (array $item): bool => $item['result'] === 'pass')),
    'failed' => count(array_filter($results, static fn (array $item): bool => $item['result'] === 'fail')),
    'blocked' => count(array_filter($results, static fn (array $item): bool => $item['result'] === 'blocked')),
    'checks' => $results,
];

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

exit($summary['failed'] === 0 ? 0 : 1);
