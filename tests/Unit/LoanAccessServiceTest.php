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

    public function test_field_officer_loan_query_is_limited_to_assigned_or_created_loans(): void
    {
        $branch = $this->branch();
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
        PersonalLoan::factory()->create([
            'branch_id' => $branch->id,
            'assigned_to' => $otherOfficer->id,
            'added_by' => $otherOfficer->id,
        ]);

        $loanIds = $this->service
            ->scopeLoanQuery(PersonalLoan::query(), user: $officer)
            ->orderBy('id')
            ->pluck('id');

        $this->assertSame([$assignedLoan->id, $createdLoan->id], $loanIds->all());
    }

    public function test_branch_manager_cannot_open_another_branch_loan(): void
    {
        $manager = $this->userWithRole('Branch Manager', $this->branch('Own Branch')->id);
        $otherLoan = PersonalLoan::factory()->create(['branch_id' => $this->branch('Other Branch')->id]);

        $this->expectException(HttpException::class);

        $this->service->ensureLoanAccess($otherLoan, $manager);
    }

    public function test_field_officer_can_only_enter_the_narrow_operational_workspace(): void
    {
        $officer = $this->userWithRole('Field Officer', $this->branch()->id);
        $this->actingAs($officer);
        $middleware = new EbimsModuleAccess();

        $allowedRequest = $this->namedRequest('admin.loans.active');
        $response = $middleware->handle($allowedRequest, fn () => response('ok'));
        $this->assertSame('ok', $response->getContent());

        $this->expectException(HttpException::class);
        $middleware->handle($this->namedRequest('admin.logs.viewer'), fn () => response('not allowed'));
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
