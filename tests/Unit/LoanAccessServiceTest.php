<?php

namespace Tests\Unit;

use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\EbimsModuleAccess;
use App\Models\Branch;
use App\Models\PersonalLoan;
use App\Models\User;
use App\Services\LoanAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LoanAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoanAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LoanAccessService::class);
    }

    public function test_super_admin_middleware_rejects_branch_managers(): void
    {
        $user = $this->userWithRole('Branch Manager', $this->branch()->id);
        $this->actingAs($user);

        $this->expectException(HttpException::class);

        (new SuperAdminMiddleware())->handle(Request::create('/admin/settings'), fn () => response('ok'));
    }

    public function test_super_admin_middleware_accepts_super_administrator(): void
    {
        $user = $this->userWithRole('Super Administrator');
        $this->actingAs($user);

        $response = (new SuperAdminMiddleware())->handle(
            Request::create('/admin/settings'),
            fn () => response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_branch_manager_loan_query_is_limited_to_own_branch(): void
    {
        $ownBranch = $this->branch('Own Branch');
        $otherBranch = $this->branch('Other Branch');
        $manager = $this->userWithRole('Branch Manager', $ownBranch->id);
        $ownLoan = PersonalLoan::factory()->create(['branch_id' => $ownBranch->id]);
        PersonalLoan::factory()->create(['branch_id' => $otherBranch->id]);

        $loanIds = $this->service
            ->scopeLoanQuery(PersonalLoan::query(), user: $manager)
            ->pluck('id');

        $this->assertSame([$ownLoan->id], $loanIds->all());
    }

    public function test_field_officer_loan_query_includes_all_loans_in_their_branch(): void
    {
        $branch = $this->branch();
        $otherBranch = $this->branch('Other Branch');
        $officer = $this->userWithRole('Loan Officer', $branch->id);
        $otherOfficer = User::factory()->create(['branch_id' => $branch->id]);
        $assignedLoan = PersonalLoan::factory()->create([
            'branch_id' => $branch->id,
            'assigned_to' => $officer->id,
            'added_by' => $otherOfficer->id,
        ]);
        $createdLoan = PersonalLoan::factory()->create([
            'branch_id' => $branch->id,
            'assigned_to' => $otherOfficer->id,
            'added_by' => $officer->id,
        ]);
        $otherOfficerLoan = PersonalLoan::factory()->create([
            'branch_id' => $branch->id,
            'assigned_to' => $otherOfficer->id,
            'added_by' => $otherOfficer->id,
        ]);
        PersonalLoan::factory()->create([
            'branch_id' => $otherBranch->id,
            'assigned_to' => $otherOfficer->id,
            'added_by' => $otherOfficer->id,
        ]);

        $loanIds = $this->service
            ->scopeLoanQuery(PersonalLoan::query(), user: $officer)
            ->orderBy('id')
            ->pluck('id');

        $this->assertSame([$assignedLoan->id, $createdLoan->id, $otherOfficerLoan->id], $loanIds->all());
    }

    public function test_field_officer_can_open_another_officers_loan_in_their_branch(): void
    {
        $branch = $this->branch();
        $officer = $this->userWithRole('Loan Officer', $branch->id);
        $otherOfficer = User::factory()->create(['branch_id' => $branch->id]);
        $loan = PersonalLoan::factory()->create([
            'branch_id' => $branch->id,
            'assigned_to' => $otherOfficer->id,
            'added_by' => $otherOfficer->id,
        ]);

        $this->service->ensureLoanAccess($loan, $officer);

        $this->addToAssertionCount(1);
    }

    public function test_field_officer_cannot_open_a_loan_from_another_branch(): void
    {
        $officer = $this->userWithRole('Loan Officer', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create(['branch_id' => $this->branch('Other Branch')->id]);

        $this->expectException(HttpException::class);

        $this->service->ensureLoanAccess($otherLoan, $officer);
    }

    public function test_branch_manager_cannot_open_another_branch_loan(): void
    {
        $manager = $this->userWithRole('Branch Manager', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create(['branch_id' => $this->branch('Other Branch')->id]);

        $this->expectException(HttpException::class);

        $this->service->ensureLoanAccess($otherLoan, $manager);
    }

    public function test_field_officer_can_enter_the_ebims_module_workspace(): void
    {
        $officer = $this->userWithRole('Field Officer', $this->branch()->id);
        $this->actingAs($officer);
        $middleware = new EbimsModuleAccess();

        foreach ([
            'admin.members.index',
            'admin.umra.dashboard',
            'admin.loans.follow-ups.store',
            'admin.loans.collateral.store',
        ] as $routeName) {
            $response = $middleware->handle($this->namedRequest($routeName), fn () => response('ok'));
            $this->assertSame('ok', $response->getContent());
        }
    }

    public function test_loan_officer_dashboard_redirects_to_admin_home(): void
    {
        $officer = $this->userWithRole('Loan Officer', $this->branch()->id);

        $this->actingAs($officer)
            ->get('/dashboard')
            ->assertRedirect(route('admin.home'));
    }

    public function test_field_officer_still_cannot_enter_super_admin_pages(): void
    {
        $officer = $this->userWithRole('Field Officer', $this->branch()->id);
        $this->actingAs($officer);
        $this->expectException(HttpException::class);

        (new SuperAdminMiddleware())->handle(Request::create('/admin/logs'), fn () => response('not allowed'));
    }

    public function test_stop_loan_and_disbursement_mutations_require_super_admin_middleware(): void
    {
        foreach ([
            'admin.loan-management.disbursements.process',
            'admin.disbursements.create',
            'admin.disbursements.store',
            'admin.disbursements.edit',
            'admin.disbursements.update',
            'admin.disbursements.destroy',
            'admin.disbursements.complete',
            'admin.disbursements.cancel',
            'admin.disbursements.retry',
            'admin.disbursements.approve.show',
            'admin.disbursements.approve',
            'admin.loans.disbursements.approve.show',
            'admin.loans.disbursements.approve',
            'admin.loans.disbursements.check-status',
            'admin.loans.stop',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Route not found: ' . $routeName);
            $this->assertContains('super_admin', $route->middleware(), 'Route must require super_admin: ' . $routeName);
        }
    }

    public function test_operational_loan_routes_remain_available_without_super_admin_middleware(): void
    {
        foreach ([
            'admin.home',
            'admin.umra.dashboard',
            'admin.loans.active',
            'admin.loans.follow-ups.store',
            'admin.loans.collateral.store',
            'admin.loans.disbursements.pending',
            'admin.loans.repayments.store-mobile-money',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Route not found: ' . $routeName);
            $this->assertNotContains('super_admin', $route->middleware(), 'Route must remain operationally accessible: ' . $routeName);
        }
    }

    public function test_settings_and_user_management_routes_remain_super_admin_only(): void
    {
        foreach ([
            'admin.settings.dashboard',
            'admin.settings.branches',
            'admin.settings.loan-products',
            'admin.settings.system-accounts',
            'admin.users.index',
            'admin.users.edit',
            'admin.roles.index',
            'admin.permissions.index',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Route not found: ' . $routeName);
            $this->assertContains('super_admin', $route->middleware(), 'Route must require super_admin: ' . $routeName);
        }
    }

    public function test_self_service_profile_and_password_routes_remain_available_to_authenticated_users(): void
    {
        foreach ([
            'profile.show',
            'user-profile-information.update',
            'user-password.update',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Route not found: ' . $routeName);
            $this->assertNotContains('super_admin', $route->middleware(), 'Self-service route must not require super_admin: ' . $routeName);
        }
    }

    private function branch(string $name = 'Main Branch'): Branch
    {
        return Branch::create([
            'name' => $name . ' ' . fake()->unique()->numberBetween(1, 99999),
            'is_active' => true,
        ]);
    }

    private function userWithRole(string $roleName, ?int $branchId = null): User
    {
        $user = User::factory()->create(['branch_id' => $branchId]);
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    private function namedRequest(string $name): Request
    {
        $route = (new Route(['GET'], '/', fn () => null))->name($name);
        $request = Request::create('/');
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
