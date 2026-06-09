<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\RepaymentController;
use App\Http\Controllers\Admin\LateFeeController;
use App\Models\Branch;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupLoanSchedule;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Models\PersonalLoan;
use App\Models\Repayment;
use App\Models\User;
use App\Services\RepaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class RepaymentFinancialRulesTest extends TestCase
{
    use RefreshDatabase;

    private RepaymentService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RepaymentService::class);
        $this->user = User::factory()->create();
    }

    private function createSchedule(PersonalLoan $loan, array $attributes = []): LoanSchedule
    {
        $principal = $attributes['principal'] ?? 100000;
        $interest = $attributes['interest'] ?? 10000;

        return LoanSchedule::create(array_merge([
            'loan_id' => $loan->id,
            'payment_date' => now()->subDays(10)->format('Y-m-d'),
            'principal' => $principal,
            'interest' => $interest,
            'payment' => $principal + $interest,
            'balance' => $principal + $interest,
            'paid' => 0,
            'pending_count' => 0,
            'status' => 0,
        ], $attributes));
    }

    public function test_schedule_outstanding_uses_principal_interest_late_fees_waivers_and_valid_payments(): void
    {
        $member = Member::factory()->create();
        $loan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
            'principal' => 100000,
        ]);
        $schedule = $this->createSchedule($loan);

        DB::table('late_fees')->insert([
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'member_id' => $member->id,
            'amount' => 20000,
            'days_overdue' => 10,
            'periods_overdue' => 10,
            'period_type' => 'Daily',
            'schedule_due_date' => $schedule->payment_date,
            'calculated_date' => now()->format('Y-m-d'),
            'status' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Repayment::create([
            'type' => 1,
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'amount' => 30000,
            'date_created' => now(),
            'added_by' => $this->user->id,
            'status' => 1,
            'payment_status' => 'Completed',
            'platform' => 'Web',
        ]);

        $components = $this->service->getScheduleOutstandingComponents($schedule, $loan->fresh('product'));

        $this->assertEquals(66000.0, $components['late_fee_gross']);
        $this->assertEquals(20000.0, $components['late_fee_waived']);
        $this->assertEquals(46000.0, $components['late_fee_net']);
        $this->assertEquals(126000.0, $components['outstanding']);
    }

    public function test_payment_amount_must_equal_exact_schedule_balance(): void
    {
        $loan = PersonalLoan::factory()->create(['status' => 2]);
        $schedule = $this->createSchedule($loan, [
            'payment_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $method = new ReflectionMethod($this->service, 'validatePaymentAmount');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, $schedule, 100000)['valid']);
        $this->assertFalse($method->invoke($this->service, $schedule, 120000)['valid']);
        $this->assertTrue($method->invoke($this->service, $schedule, 110000)['valid']);
    }

    public function test_loan_does_not_close_when_late_fees_are_unpaid(): void
    {
        $loan = PersonalLoan::factory()->create(['status' => 2]);
        $schedule = $this->createSchedule($loan);

        Repayment::create([
            'type' => 1,
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'amount' => 110000,
            'date_created' => now(),
            'added_by' => $this->user->id,
            'status' => 1,
            'payment_status' => 'Completed',
            'platform' => 'Web',
        ]);

        $closed = $this->service->checkAndCloseLoanIfComplete($loan->id);

        $this->assertFalse($closed);
        $this->assertEquals(2, $loan->fresh()->status);
    }

    public function test_loan_closes_only_when_all_schedule_balances_are_zero(): void
    {
        $loan = PersonalLoan::factory()->create(['status' => 2]);
        $schedule = $this->createSchedule($loan);

        Repayment::create([
            'type' => 1,
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'amount' => 176000,
            'date_created' => now(),
            'added_by' => $this->user->id,
            'status' => 1,
            'payment_status' => 'Completed',
            'platform' => 'Web',
        ]);

        $closed = $this->service->checkAndCloseLoanIfComplete($loan->id);

        $this->assertTrue($closed);
        $this->assertEquals(3, $loan->fresh()->status);
        $this->assertEquals(1, $schedule->fresh()->status);
        $this->assertNotNull($schedule->fresh()->date_cleared);
    }

    public function test_super_admin_can_waive_calculated_group_loan_late_fee(): void
    {
        $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
        $this->actingAs($superAdmin);

        $branch = Branch::create([
            'name' => 'Group Waiver Branch',
            'is_active' => true,
        ]);

        $group = Group::create([
            'code' => 'GRP-WAIVE',
            'name' => 'Waiver Test Group',
            'inception_date' => now()->toDateString(),
            'address' => 'Test address',
            'sector' => 'Trade',
            'type' => 1,
            'verified' => 1,
            'branch_id' => $branch->id,
            'added_by' => $superAdmin->id,
        ]);

        $loan = GroupLoan::create([
            'group_id' => $group->id,
            'product_type' => 1,
            'code' => 'GL-WAIVE',
            'interest' => '10',
            'period' => '1',
            'principal' => '100000',
            'status' => 2,
            'verified' => 1,
            'branch_id' => $branch->id,
            'added_by' => $superAdmin->id,
            'datecreated' => now(),
        ]);

        $schedule = GroupLoanSchedule::create([
            'loan_id' => $loan->id,
            'payment_date' => now()->subDays(10)->format('Y-m-d'),
            'principal' => 100000,
            'interest' => 10000,
            'payment' => 110000,
            'balance' => 110000,
            'status' => 0,
        ]);

        $request = Request::create('/admin/loans/late-fees/waive', 'POST', [
            'loan_id' => $loan->id,
            'loan_type' => 'group',
            'late_fees' => json_encode([[
                'schedule_id' => $schedule->id,
                'amount' => 12000,
                'schedule_date' => $schedule->payment_date,
            ]]),
            'waiver_reason' => 'Approved test group waiver',
        ]);

        $response = app(RepaymentController::class)->waiveLateFees($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertDatabaseHas('late_fees', [
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'member_id' => $group->id,
            'amount' => 12000,
            'status' => 2,
            'waiver_reason' => 'Approved test group waiver',
            'waived_by' => $superAdmin->id,
        ]);
    }

    public function test_late_fee_page_stats_match_active_running_loan_late_fee_math(): void
    {
        $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
        $this->actingAs($superAdmin);

        $member = Member::factory()->create();
        $loan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
            'principal' => 100000,
        ]);

        $schedule = $this->createSchedule($loan, [
            'payment_date' => now()->subDays(10)->format('Y-m-d'),
            'principal' => 100000,
            'interest' => 10000,
            'payment' => 110000,
            'balance' => 110000,
            'status' => 0,
        ]);

        DB::table('late_fees')->insert([
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'member_id' => $member->id,
            'amount' => 20000,
            'days_overdue' => 10,
            'periods_overdue' => 10,
            'period_type' => 'Daily',
            'schedule_due_date' => $schedule->payment_date,
            'calculated_date' => now()->format('Y-m-d'),
            'status' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $closedLoan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 3,
            'principal' => 100000,
        ]);
        $this->createSchedule($closedLoan, [
            'payment_date' => now()->subDays(10)->format('Y-m-d'),
            'principal' => 100000,
            'interest' => 10000,
            'payment' => 110000,
            'balance' => 0,
            'status' => 1,
            'date_cleared' => now(),
        ]);

        $method = new ReflectionMethod(app(LateFeeController::class), 'activeLoanLateFeeStats');
        $method->setAccessible(true);
        $stats = $method->invoke(app(LateFeeController::class));

        $this->assertEquals(66000.0, $stats['gross']);
        $this->assertEquals(20000.0, $stats['waived']);
        $this->assertEquals(46000.0, $stats['outstanding']);
        $this->assertEquals(46000.0, $stats['total']);
        $this->assertSame(1, $stats['count']);
        $this->assertSame(1, $stats['pending_count']);
    }

    public function test_late_fee_page_stats_count_paid_and_waived_amounts_for_selected_month(): void
    {
        $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
        $this->actingAs($superAdmin);

        $member = Member::factory()->create();
        $loan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
            'principal' => 100000,
        ]);

        $schedule = $this->createSchedule($loan, [
            'payment_date' => now()->startOfMonth()->toDateString(),
            'principal' => 100000,
            'interest' => 10000,
            'payment' => 110000,
            'balance' => 110000,
            'status' => 0,
        ]);

        DB::table('late_fees')->insert([
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'member_id' => $member->id,
            'amount' => 20000,
            'days_overdue' => 5,
            'periods_overdue' => 5,
            'period_type' => 'Daily',
            'schedule_due_date' => $schedule->payment_date,
            'calculated_date' => now()->format('Y-m-d'),
            'status' => 2,
            'waived_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Repayment::create([
            'type' => 1,
            'loan_id' => $loan->id,
            'schedule_id' => $schedule->id,
            'amount' => 120000,
            'date_created' => now(),
            'added_by' => $superAdmin->id,
            'status' => 1,
            'payment_status' => 'Completed',
            'platform' => 'Web',
        ]);

        $gross = $this->service->calculateLateFee($schedule, $loan->fresh('product'))['gross'];
        $method = new ReflectionMethod(app(LateFeeController::class), 'activeLoanLateFeeStats');
        $method->setAccessible(true);
        $stats = $method->invoke(
            app(LateFeeController::class),
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertEquals($gross, $stats['gross']);
        $this->assertEquals(20000.0, $stats['waived']);
        $this->assertEquals(10000.0, $stats['paid']);
        $this->assertEquals($gross - 30000.0, $stats['outstanding']);
    }
}
