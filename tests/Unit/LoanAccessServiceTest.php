<?php

namespace Tests\Unit;

use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\EbimsModuleAccess;
use App\Http\Middleware\EbimsPermissionAccess;
use App\Models\Branch;
use App\Models\PersonalLoan;
use App\Models\User;
use App\Services\LoanAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
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

    public function test_field_officer_active_loan_query_includes_unassigned_loans_from_all_branches(): void
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
        $otherBranchLoan = PersonalLoan::factory()->create([
            'branch_id' => $otherBranch->id,
            'assigned_to' => $otherOfficer->id,
            'added_by' => $otherOfficer->id,
        ]);

        $loanIds = $this->service
            ->scopeActiveLoanQuery(PersonalLoan::query(), user: $officer)
            ->orderBy('id')
            ->pluck('id');

        $this->assertSame([$assignedLoan->id, $createdLoan->id, $otherOfficerLoan->id, $otherBranchLoan->id], $loanIds->all());
    }

    public function test_branch_manager_active_loan_query_includes_loans_from_all_branches(): void
    {
        $ownBranch = $this->branch('Own Branch');
        $otherBranch = $this->branch('Other Branch');
        $manager = $this->userWithRole('Branch Manager', $ownBranch->id);
        $ownLoan = PersonalLoan::factory()->create(['branch_id' => $ownBranch->id]);
        $otherLoan = PersonalLoan::factory()->create(['branch_id' => $otherBranch->id]);

        $loanIds = $this->service
            ->scopeActiveLoanQuery(PersonalLoan::query(), user: $manager)
            ->orderBy('id')
            ->pluck('id');

        $this->assertSame([$ownLoan->id, $otherLoan->id], $loanIds->all());
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

    public function test_field_officer_can_open_an_active_loan_from_another_branch(): void
    {
        $officer = $this->userWithRole('Loan Officer', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create([
            'branch_id' => $this->branch('Other Branch')->id,
            'status' => 2,
        ]);

        $this->service->ensureLoanAccess($otherLoan, $officer);

        $this->addToAssertionCount(1);
    }

    public function test_field_officer_cannot_open_a_non_active_loan_from_another_branch(): void
    {
        $officer = $this->userWithRole('Loan Officer', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create([
            'branch_id' => $this->branch('Other Branch')->id,
            'status' => 1,
        ]);

        $this->expectException(HttpException::class);

        $this->service->ensureLoanAccess($otherLoan, $officer);
    }

    public function test_branch_manager_can_open_an_active_loan_from_another_branch(): void
    {
        $manager = $this->userWithRole('Branch Manager', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create([
            'branch_id' => $this->branch('Other Branch')->id,
            'status' => 2,
        ]);

        $this->service->ensureLoanAccess($otherLoan, $manager);

        $this->addToAssertionCount(1);
    }

    public function test_branch_manager_cannot_open_a_non_active_loan_from_another_branch(): void
    {
        $manager = $this->userWithRole('Branch Manager', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create([
            'branch_id' => $this->branch('Other Branch')->id,
            'status' => 1,
        ]);

        $this->expectException(HttpException::class);

        $this->service->ensureLoanAccess($otherLoan, $manager);
    }

    public function test_branch_manager_active_loan_filters_include_all_branches(): void
    {
        $ownBranch = $this->branch('Own Branch');
        $otherBranch = $this->branch('Other Branch');
        $manager = $this->userWithRole('Branch Manager', $ownBranch->id);

        $branchIds = $this->service
            ->branchesForActiveLoanOperations(Branch::query(), $manager)
            ->orderBy('id')
            ->pluck('id');

        $this->assertSame([$ownBranch->id, $otherBranch->id], $branchIds->all());
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

    public function test_role_without_workspace_permission_cannot_enter_ebims_modules(): void
    {
        $user = $this->userWithRole('Custom Read Only', $this->branch()->id);
        $this->actingAs($user);

        $this->expectException(HttpException::class);

        (new EbimsModuleAccess())->handle(
            $this->namedRequest('admin.home'),
            fn () => response('not allowed')
        );
    }

    public function test_workspace_permission_can_enable_a_custom_role_from_the_ui(): void
    {
        $user = $this->userWithRole('Custom Operations', $this->branch()->id);
        $user->givePermissionTo(Permission::findOrCreate('access-ebmis-modules', 'web'));
        $this->actingAs($user);

        $response = (new EbimsModuleAccess())->handle(
            $this->namedRequest('admin.home'),
            fn () => response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_route_permission_can_be_granted_to_a_custom_role_from_the_ui(): void
    {
        $user = $this->userWithRole('Collateral Clerk', $this->branch()->id);
        $user->givePermissionTo(Permission::findOrCreate('access-ebmis-modules', 'web'));
        $this->actingAs($user);
        $middleware = new EbimsPermissionAccess();

        try {
            $middleware->handle(
                $this->namedRequest('admin.loans.collateral.store'),
                fn () => response('not allowed')
            );

            $this->fail('Collateral access should require the mapped permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $user->givePermissionTo(Permission::findOrCreate('record-loan-collateral', 'web'));

        $response = $middleware->handle(
            $this->namedRequest('admin.loans.collateral.store'),
            fn () => response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_unmapped_operational_route_is_denied_by_default(): void
    {
        $user = $this->userWithRole('Custom Operations', $this->branch()->id);
        $user->givePermissionTo(Permission::findOrCreate('access-ebmis-modules', 'web'));
        $this->actingAs($user);

        $this->expectException(HttpException::class);

        (new EbimsPermissionAccess())->handle(
            $this->namedRequest('admin.unmapped-operational-route'),
            fn () => response('not allowed')
        );
    }

    public function test_user_password_is_mass_assignable_and_hashed(): void
    {
        $user = User::create([
            'name' => 'Password Test',
            'email' => 'password-test@example.com',
            'password' => 'SecurePassword123',
        ]);

        $this->assertTrue(Hash::check('SecurePassword123', $user->password));
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
            $this->assertContains('ebmis_permission', $route->middleware(), 'Route must enforce UI-controlled permissions: ' . $routeName);
        }
    }

    public function test_unused_general_public_registration_is_disabled(): void
    {
        $this->assertNull(app('router')->getRoutes()->getByName('register'));
        $this->assertNull(app('router')->getRoutes()->getByName('register.store'));
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
