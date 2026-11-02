<?php

namespace Tests\Feature;

use App\Models\Expenditure;
use App\Models\Investment;
use App\Models\JournalEntry;
use App\Models\StaffRemunerationCalculation;
use App\Models\StaffRemunerationPolicy;
use App\Models\SystemAccount;
use App\Models\User;
use App\Services\MobileMoneyService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StaffRemunerationPaymentFlowTest extends TestCase
{
    private StaffRemunerationPolicy $policy;

    private SystemAccount $expenseAccount;

    private SystemAccount $cashAccount;

    private Investment $investment;

    protected function setUp(): void
    {
        parent::setUp();
        // Never use RefreshDatabase or migrate:fresh against the application's connection.
        config(['database.default' => 'remuneration_flow', 'database.connections.remuneration_flow' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('remuneration_flow');
        Carbon::setTestNow(Carbon::parse('2026-10-26 12:00:00', 'Africa/Kampala'));
        Storage::fake('local');
        Http::preventStrayRequests();
        Schema::create('users', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->string('user_type');
            $t->string('status');
            $t->string('designation')->nullable();
            $t->integer('branch_id')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->integer('status')->default(1);
        });
        foreach (['products', 'funds', 'investors'] as $table) {
            Schema::create($table, fn (Blueprint $t) => $t->id());
        }
        Schema::create('personal_loans', function (Blueprint $t): void {
            $t->id();
            $t->integer('assigned_to');
            $t->integer('member_id');
            $t->integer('status')->default(2);
            $t->date('datecreated');
            $t->date('date_closed')->nullable();
            $t->string('frequency')->default('weekly');
        });
        Schema::create('loan_schedules', function (Blueprint $t): void {
            $t->id();
            $t->integer('loan_id');
            $t->date('payment_date');
            $t->decimal('payment', 15, 2);
            $t->decimal('interest', 15, 2);
            $t->decimal('principal', 15, 2);
            $t->decimal('paid', 15, 2)->default(0);
            $t->integer('status')->default(0);
        });
        Schema::create('repayments', function (Blueprint $t): void {
            $t->id();
            $t->integer('loan_id');
            $t->integer('schedule_id');
            $t->decimal('amount', 15, 2);
            $t->integer('status')->default(1);
            $t->string('payment_status')->default('Completed');
            $t->dateTime('date_created');
        });
        Schema::create('fees_types', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
        });
        Schema::create('fees', function (Blueprint $t): void {
            $t->id();
            $t->integer('loan_id')->nullable();
            $t->integer('added_by');
            $t->integer('fees_type_id');
            $t->integer('status')->default(1);
            $t->dateTime('datecreated');
            $t->decimal('amount', 15, 2);
            $t->string('description');
        });
        Schema::create('loan_follow_ups', function (Blueprint $t): void {
            $t->id();
            $t->integer('assigned_to');
            $t->integer('loan_id');
            $t->string('loan_type')->default('personal');
            $t->dateTime('follow_up_at');
        });
        Schema::create('raw_payments', function (Blueprint $t): void {
            $t->id();
            $t->integer('expenditure_id');
            $t->string('txn_id');
            $t->string('amount');
            $t->string('status');
            $t->string('pay_status');
            $t->text('raw_message');
            $t->timestamps();
        });
        foreach ([
            '2025_10_29_145600_create_permission_tables.php',
            '2025_11_01_083741_create_system_accounts_table.php',
            '2025_11_01_085707_create_investment_table.php',
            '2026_02_09_000001_create_journal_entries_table.php',
            '2026_02_09_000002_create_journal_lines_table.php',
            '2026_04_21_085907_add_inv_id_to_journal_entries_table.php',
            '2026_06_14_000001_create_expenditures_tables.php',
            '2026_06_14_000002_add_mobile_money_fields_to_expenditures.php',
            '2026_07_01_000001_add_investment_account_to_expenditures.php',
            '2026_08_24_000001_harden_expenditure_payment_state.php',
            '2026_10_24_000001_create_staff_remuneration_policies.php',
            '2026_10_24_000002_create_staff_remuneration_weekly_calculations.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        foreach ([1 => 'Officer', 2 => 'Preparer', 3 => 'Approver', 4 => 'Payer'] as $id => $name) {
            User::create([
                'name' => $name, 'email' => strtolower($name).'@example.test', 'password' => 'TestPassword123',
                'user_type' => $id === 1 ? 'branch' : 'super_admin', 'status' => 'active', 'email_verified_at' => now(),
            ]);
        }
        (new \Database\Seeders\StaffRemunerationDraftSeeder)->run();
        foreach (['access-ebmis-modules', 'view-own-remuneration'] as $name) {
            User::findOrFail(1)->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->policy = StaffRemunerationPolicy::firstOrFail();
        $source = SystemAccount::create(['code' => '32000', 'name' => 'Retained Earnings', 'category' => 'Equity']);
        $this->artisan('remuneration:configure-ledger', ['--source' => $source->Id, '--actor' => 2, '--apply' => true])->assertSuccessful();
        $this->policy->refresh();
        $this->expenseAccount = SystemAccount::create(['code' => '51000', 'name' => 'Staff remuneration expense', 'category' => 'Expense']);
        $this->cashAccount = SystemAccount::create(['code' => '11000', 'name' => 'Test cash', 'category' => 'Asset', 'is_cash_bank' => true, 'running_balance' => 2000000]);
        DB::table('investors')->insert(['id' => 1]);
        $this->investment = Investment::create(['userid' => 1, 'type' => 1, 'name' => 'Test funding', 'amount' => 1000000,
            'period' => 1, 'percentage' => 0, 'start' => '2026-01-01', 'end' => '2027-01-01', 'interest' => 0, 'status' => 1]);
        for ($i = 1; $i <= 2; $i++) {
            DB::table('personal_loans')->insert(['id' => $i, 'assigned_to' => 1, 'member_id' => $i, 'datecreated' => $i === 1 ? '2026-01-01' : '2026-10-20']);
            DB::table('loan_schedules')->insert(['id' => $i, 'loan_id' => $i, 'payment_date' => '2026-11-30', 'payment' => 5000000, 'interest' => 1000000, 'principal' => 4000000]);
            DB::table('repayments')->insert(['loan_id' => $i, 'schedule_id' => $i, 'amount' => 1000000, 'date_created' => $i === 1 ? '2026-10-15' : '2026-10-22']);
            DB::table('loan_follow_ups')->insert(['assigned_to' => 1, 'loan_id' => $i, 'follow_up_at' => '2026-10-22']);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('remuneration_flow');
        parent::tearDown();
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        // Each actor represents a separate browser session, with session-auth middleware intact.
        $this->flushSession();

        return parent::actingAs($user, $guard);
    }

    private function prepareRequest(): StaffRemunerationCalculation
    {
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.remuneration.policies.approve', $this->policy))->assertSessionHasNoErrors();
        $this->post(route('admin.expenditures.remuneration.policies.activate', $this->policy))->assertSessionHasNoErrors();
        $this->assertSame('active', $this->policy->fresh()->status);
        $this->actingAs(User::findOrFail(1))->post(route('admin.remuneration.spending-report.store'), [
            'week_start' => '2026-10-19', 'received_amount' => 100000, 'funding_reference' => 'TEST-ADVANCE-001',
            'items' => [['date' => '2026-10-22', 'category' => 'transport', 'description' => 'Field visits', 'amount' => 80000, 'reference' => 'TEST-RECEIPT-001']],
            'report_complete' => 1, 'activities_completed' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $record = StaffRemunerationCalculation::firstOrFail();
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.remuneration.calculations.report-review', $record), [
            'decision' => 'approve', 'reason' => 'Verified advance and all receipts', 'funding_verified' => 1, 'expenses_verified' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->requestPayout($record);

        return $record->fresh();
    }

    private function requestPayout(StaffRemunerationCalculation $record): void
    {
        $this->actingAs(User::findOrFail(2))->post(route('admin.expenditures.remuneration.calculations.payout', $record), [
            'expense_account_id' => $this->expenseAccount->Id,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function approveAndAllocate(StaffRemunerationCalculation $record): Expenditure
    {
        $expense = $record->fresh()->expenditure;
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.approve', $expense))->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->post(route('admin.expenditures.remuneration.calculations.funds', $record))->assertSessionHas('success')->assertSessionHasNoErrors();

        return $expense->fresh();
    }

    private function paymentData(): array
    {
        return ['payment_account_id' => $this->cashAccount->Id, 'investment_id' => $this->investment->id,
            'mobile_money_phone' => '0771234567', 'mobile_money_network' => 'MTN'];
    }

    public function test_general_expenses_reject_manual_staff_payments_even_with_a_forged_calculation_id(): void
    {
        $this->actingAs(User::findOrFail(2))->postJson(route('admin.expenditures.store'), [
            'type' => 'performance_payout', 'title' => 'Bypass attempt', 'expense_account_id' => $this->expenseAccount->Id,
            'amount' => 100000, 'expense_date' => '2026-10-26', 'remuneration_calculation_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->assertSame(0, Expenditure::count());
    }

    public function test_unlinked_legacy_staff_request_cannot_be_approved_or_paid(): void
    {
        $expense = Expenditure::create(['expense_number' => 'LEGACY-001', 'type' => 'performance_payout', 'title' => 'Unlinked legacy request',
            'expense_account_id' => $this->expenseAccount->Id, 'requested_by' => 2, 'assigned_user_id' => 1, 'amount' => 100000,
            'expense_date' => '2026-10-26', 'status' => 'pending']);
        $this->actingAs(User::findOrFail(3))->postJson(route('admin.expenditures.approve', $expense))->assertUnprocessable();
        $expense->update(['status' => 'approved', 'approved_by' => 3, 'approved_at' => now()]);
        $this->actingAs(User::findOrFail(4))->postJson(route('admin.expenditures.pay', $expense), $this->paymentData())->assertUnprocessable();
        $this->assertSame(1000000.0, (float) $this->investment->fresh()->amount);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_rejection_preserves_old_request_and_requires_refresh_before_replacement(): void
    {
        $record = $this->prepareRequest();
        $original = $record->expenditure;
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.reject', $original), ['rejection_reason' => 'Please correct the supporting note.'])->assertSessionHas('success');
        $html = view('admin.staff-remuneration.partials.administrative-review', ['savedCalculation' => $record->fresh(), 'policy' => $this->policy->fresh()])->render();
        $this->assertStringContainsString('Reopen rejected payout for correction', $html);
        $this->post(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'Correction authorized after reviewing the rejection.'])->assertSessionHas('success');
        $this->assertNull($record->fresh()->expenditure_id);
        $this->assertFalse(data_get($record->fresh()->input_snapshot, 'ready_for_payment'));
        $this->assertSame('rejected', $original->fresh()->status);
        $this->postJson(route('admin.expenditures.remuneration.calculations.payout', $record), [
            'expense_account_id' => $this->expenseAccount->Id,
        ])->assertUnprocessable(); // still unresolved: must not create anything
        $this->assertSame(1, Expenditure::count());
        $this->post(route('admin.expenditures.remuneration.calculations.store'), [
            'officer_id' => 1, 'period_start' => '2026-10-19', 'period_end' => '2026-10-25',
        ])->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->requestPayout($record);
        $this->assertNotSame($original->id, $record->fresh()->expenditure_id);
        $this->assertSame('240000.00', $record->fresh()->expenditure->amount);
        $this->assertSame($original->id, data_get($record->fresh()->input_snapshot, 'payout_history.0.expenditure_id'));
        $this->assertSame(2, Expenditure::count());
        $html = view('admin.staff-remuneration.partials.administrative-review', ['savedCalculation' => $record->fresh(), 'policy' => $this->policy->fresh()])->render();
        $this->assertStringContainsString('Previous payout requests', $html);
    }

    public function test_approved_allocations_are_reversed_when_rejected_request_is_reopened(): void
    {
        $record = $this->prepareRequest();
        $expense = $this->approveAndAllocate($record);
        $journalId = data_get($record->fresh()->input_snapshot, 'fund_posting.journal_id');
        $this->post(route('admin.expenditures.reject', $expense), ['rejection_reason' => 'Approved payout needs correction before payment.'])->assertSessionHas('success');
        $this->post(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'Reverse allocations and authorize a corrected request.'])->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->assertSame('reversed', JournalEntry::findOrFail($journalId)->status);
        $reversal = JournalEntry::where('reference_type', 'Reversal')->firstOrFail();
        $this->assertTrue($reversal->isBalanced());
        $this->assertSame(1, (int) $reversal->officer_id);
        foreach (data_get($this->policy->fresh()->configuration, 'fund_allocation.accounts') as $id) {
            $this->assertSame(0.0, (float) SystemAccount::findOrFail($id)->running_balance);
        }
        $this->assertNull(data_get($record->fresh()->input_snapshot, 'fund_posting'));
        $this->assertSame('rejected', $expense->fresh()->status);
    }

    public function test_full_payment_flow_handles_failure_retry_pending_and_confirmed_payment_exactly_once(): void
    {
        $record = $this->prepareRequest();
        $expense = $this->approveAndAllocate($record);
        $this->mock(MobileMoneyService::class, function ($mock): void {
            $mock->shouldReceive('validatePhoneNumber')->twice()->andReturn(['valid' => true, 'formatted_phone' => '256771234567', 'network' => 'MTN']);
            $mock->shouldReceive('disburse')->once()->ordered()->andReturn(['success' => false, 'reference' => 'TEST-FAILED-001', 'status_code' => 'FAILED', 'message' => 'Rejected by test provider']);
            $mock->shouldReceive('disburse')->once()->ordered()->andReturn(['success' => true, 'reference' => 'TEST-PENDING-002', 'status_code' => 'PENDING', 'message' => 'Accepted by test provider']);
            $mock->shouldReceive('checkTransactionStatus')->once()->with('TEST-PENDING-002', 'MTN')->andReturn(['success' => true, 'status' => 'completed', 'message' => 'Confirmed by test provider']);
        });
        $this->actingAs(User::findOrFail(4))->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('error', 'Rejected by test provider');
        $this->assertSame('payment_failed', $expense->fresh()->status);
        $this->assertSame('payment_failed', $record->fresh()->status);
        $this->assertNull($expense->fresh()->investment_debited_at);
        $this->assertSame(1000000.0, (float) $this->investment->fresh()->amount);
        $this->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('info');
        $this->assertSame('payment_pending', $expense->fresh()->status);
        $this->assertSame('payment_pending', $record->fresh()->status);
        $this->assertSame(760000.0, (float) $this->investment->fresh()->amount);
        $this->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('error');
        $this->post(route('admin.expenditures.mobile-money.status', $expense))->assertSessionHas('success');
        $this->assertSame('paid', $record->fresh()->status);
        $this->assertSame('paid', $expense->fresh()->status);
        $this->assertSame(760000.0, (float) $this->investment->fresh()->amount);
        $this->assertSame(2, JournalEntry::count());
        $this->assertSame(1, JournalEntry::where('reference_type', 'Expenditure')->where('reference_id', $expense->id)->count());
        $this->post(route('admin.expenditures.mobile-money.status', $expense))->assertSessionHas('info');
        $this->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('error');
        $this->assertSame(2, JournalEntry::count());
        $this->assertSame(2, DB::table('raw_payments')->count());
        $this->assertSame('02', DB::table('raw_payments')->where('txn_id', 'TEST-FAILED-001')->value('status'));
        $this->assertSame('01', DB::table('raw_payments')->where('txn_id', 'TEST-PENDING-002')->value('status'));
    }

    public function test_payment_requires_fund_posting_and_matching_saved_amount(): void
    {
        $record = $this->prepareRequest();
        $expense = $record->expenditure;
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.approve', $expense))->assertSessionHas('success');
        $this->actingAs(User::findOrFail(4))->postJson(route('admin.expenditures.pay', $expense), $this->paymentData())->assertUnprocessable();
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.remuneration.calculations.funds', $record))->assertSessionHas('success');
        $expense->update(['amount' => 250000]);
        $this->actingAs(User::findOrFail(4))->postJson(route('admin.expenditures.pay', $expense), $this->paymentData())->assertUnprocessable();
        $this->assertSame(1000000.0, (float) $this->investment->fresh()->amount);
    }

    public function test_ledger_setup_is_idempotent_and_does_not_approve_or_activate_the_policy(): void
    {
        $accounts = SystemAccount::count();
        $this->artisan('remuneration:configure-ledger', ['--source' => 1, '--actor' => 2, '--apply' => true])->assertSuccessful();
        $this->assertSame($accounts, SystemAccount::count());
        $this->assertSame('draft', $this->policy->fresh()->status);
        $this->assertNull($this->policy->fresh()->approved_by);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_maker_checker_controls_and_non_admin_reopening_are_enforced(): void
    {
        $record = $this->prepareRequest();
        $expense = $record->expenditure;
        $this->actingAs(User::findOrFail(2))->post(route('admin.expenditures.approve', $expense))
            ->assertSessionHas('error', 'Maker-checker control: the requester cannot approve this expenditure.');
        $this->assertSame('pending', $expense->fresh()->status);
        $expense = $this->approveAndAllocate($record);
        $this->post(route('admin.expenditures.pay', $expense), $this->paymentData())
            ->assertSessionHas('error', 'Maker-checker control: the approver cannot also pay this expenditure.');
        $this->actingAs(User::findOrFail(2))->post(route('admin.expenditures.pay', $expense), $this->paymentData())
            ->assertSessionHas('error', 'Maker-checker control: the requester cannot pay this expenditure.');
        $this->actingAs(User::findOrFail(1))->postJson(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'Officer attempts to reopen an approved payout.'])->assertForbidden();
        $this->assertSame(1000000.0, (float) $this->investment->fresh()->amount);
    }

    public function test_provider_exception_releases_reserved_funding_and_keeps_calculation_in_sync(): void
    {
        $record = $this->prepareRequest();
        $expense = $this->approveAndAllocate($record);
        $this->mock(MobileMoneyService::class, function ($mock): void {
            $mock->shouldReceive('validatePhoneNumber')->once()->andReturn(['valid' => true, 'formatted_phone' => '256771234567', 'network' => 'MTN']);
            $mock->shouldReceive('disburse')->once()->andThrow(new \RuntimeException('Mock provider unavailable'));
        });
        $this->actingAs(User::findOrFail(4))->post(route('admin.expenditures.pay', $expense), $this->paymentData())
            ->assertSessionHas('error', 'Disbursement failed: Mock provider unavailable');
        $this->assertSame('payment_failed', $record->fresh()->status);
        $this->assertSame('payment_failed', $expense->fresh()->status);
        $this->assertSame(1000000.0, (float) $this->investment->fresh()->amount);
        $this->assertNull($expense->fresh()->investment_debited_at);
        $this->assertSame(0, JournalEntry::where('reference_type', 'Expenditure')->count());
    }

    public function test_reopening_and_status_checks_cannot_revive_approved_or_replaced_requests(): void
    {
        $record = $this->prepareRequest();
        $expense = $this->approveAndAllocate($record);
        $this->actingAs(User::findOrFail(3))->postJson(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'An approved request must not be reopened.'])->assertUnprocessable();
        $this->post(route('admin.expenditures.reject', $expense), ['rejection_reason' => 'Request rejected before sending any payment.'])->assertSessionHas('success');
        $this->post(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'Preserve the rejected request and prepare a replacement.'])->assertSessionHas('success');
        $expense->update(['mobile_money_reference' => 'OLD-FAILED-REFERENCE']);
        $this->post(route('admin.expenditures.mobile-money.status', $expense))->assertSessionHas('error');
        $this->assertSame('rejected', $expense->fresh()->status);
        $this->postJson(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'Duplicate reopen must not reverse allocations twice.'])->assertUnprocessable();
        $this->assertSame(1, JournalEntry::where('reference_type', 'Reversal')->count());
        $this->assertSame(1000000.0, (float) $this->investment->fresh()->amount);
    }

    public function test_immediate_confirmation_pays_only_saved_net_once_and_paid_week_cannot_reopen(): void
    {
        $record = $this->prepareRequest();
        $expense = $this->approveAndAllocate($record);
        $this->mock(MobileMoneyService::class, function ($mock): void {
            $mock->shouldReceive('validatePhoneNumber')->once()->andReturn(['valid' => true, 'formatted_phone' => '256771234567', 'network' => 'MTN']);
            $mock->shouldReceive('disburse')->once()->with('256771234567', 240000.0, 'MTN', 'Officer', \Mockery::type('string'))
                ->andReturn(['success' => true, 'reference' => 'TEST-IMMEDIATE-001', 'status_code' => '00', 'message' => 'Confirmed immediately']);
        });
        $this->actingAs(User::findOrFail(4))->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('success');
        $this->assertSame('paid', $expense->fresh()->status);
        $this->assertSame('paid', $record->fresh()->status);
        $this->assertSame(760000.0, (float) $this->investment->fresh()->amount);
        $this->postJson(route('admin.expenditures.remuneration.calculations.reopen', $record), ['reason' => 'Paid remuneration must remain immutable.'])->assertUnprocessable();
        $this->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('error');
        $this->assertSame(2, JournalEntry::count());
        $this->assertSame(1, DB::table('raw_payments')->count());
    }

    private function policyFormData(string $version): array
    {
        $configuration = $this->policy->fresh()->configuration;
        $bands = [];
        foreach ($this->policy->fresh()->bonusBands as $band) {
            $bands[$band->metric_code][] = (array) $band;
        }

        return [
            'version' => $version, 'title' => 'Reviewed policy copy', 'base_current_loan_threshold' => 90,
            'base_rate_above_threshold' => 12, 'base_rate_at_or_below_threshold' => 5, 'budget_allocation_rate' => 10,
            'operating_budget_score_method' => 'inverse_utilization_capped', 'par_overdue_days' => 120, 'par_bonus_maximum_rate' => 3,
            'rounding_scale' => 2, 'timezone' => 'Africa/Kampala', 'weekly_growth_target' => 1,
            'par_bands' => $configuration['par_score_mapping'], 'fund_revolving_rate' => 45, 'fund_growth_rate' => 19, 'fund_bonus_budget_rate' => 14,
            'fund_accounts' => $configuration['fund_allocation']['accounts'], 'bands' => $bands,
        ];
    }

    public function test_policy_create_update_and_retire_routes_preserve_versions_and_audit(): void
    {
        $this->actingAs(User::findOrFail(2));
        $data = $this->policyFormData('crud-copy');
        $this->post(route('admin.expenditures.remuneration.policies.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $copy = StaffRemunerationPolicy::where('version', 'crud-copy')->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertCount(20, $copy->bonusBands);
        $data['title'] = 'Updated reviewed title';
        $this->put(route('admin.expenditures.remuneration.policies.update', $copy), $data)->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->assertSame('Updated reviewed title', $copy->fresh()->title);
        $this->delete(route('admin.expenditures.remuneration.policies.destroy', $copy))->assertSessionHas('success');
        $this->assertSame('retired', $copy->fresh()->status);
        $this->assertSame(2, StaffRemunerationPolicy::count());
        $this->assertNotEmpty(data_get($copy->fresh()->configuration, 'audit_history'));
    }

    public function test_policy_selection_and_activation_keep_only_one_active_version(): void
    {
        $this->actingAs(User::findOrFail(2));
        $this->post(route('admin.expenditures.remuneration.policies.approve', $this->policy))->assertSessionHas('success');
        $this->post(route('admin.expenditures.remuneration.policies.activate', $this->policy))->assertSessionHas('success');
        $this->post(route('admin.expenditures.remuneration.policies.store'), $this->policyFormData('replacement-version'))->assertSessionHasNoErrors();
        $next = StaffRemunerationPolicy::where('version', 'replacement-version')->firstOrFail();
        $this->assertSame($this->policy->id, StaffRemunerationPolicy::currentForReview()->id);
        $this->post(route('admin.expenditures.remuneration.policies.approve', $next))->assertSessionHas('success');
        $this->post(route('admin.expenditures.remuneration.policies.activate', $next))->assertSessionHas('success');
        $this->assertSame('retired', $this->policy->fresh()->status);
        $this->assertSame($next->id, StaffRemunerationPolicy::currentForReview()->id);
        $this->assertSame(1, StaffRemunerationPolicy::where('status', 'active')->count());
        $this->post(route('admin.expenditures.remuneration.policies.activate', $next))->assertSessionHas('error');
        $this->assertSame(1, StaffRemunerationPolicy::where('status', 'active')->count());
    }

    public function test_referenced_draft_rules_cannot_be_overwritten(): void
    {
        $preview = app(\App\Services\StaffRemuneration\WeeklyRemunerationPreviewService::class);
        $preview->persistPreview($preview->preview(1, $this->policy, '2026-10-19', '2026-10-25'), $this->policy, 2);
        $originalTitle = $this->policy->title;
        $this->actingAs(User::findOrFail(2))->put(route('admin.expenditures.remuneration.policies.update', $this->policy), $this->policyFormData($this->policy->version))
            ->assertSessionHas('error');
        $this->assertSame($originalTitle, $this->policy->fresh()->title);
        $this->assertSame(1, StaffRemunerationCalculation::count());
    }

    public function test_normal_operating_expenses_still_use_the_existing_approval_and_payment_workflow(): void
    {
        $this->actingAs(User::findOrFail(2))->post(route('admin.expenditures.store'), [
            'type' => 'operational', 'title' => 'Office supplies', 'expense_account_id' => $this->expenseAccount->Id,
            'amount' => 50000, 'expense_date' => '2026-10-26',
        ])->assertSessionHas('success')->assertSessionHasNoErrors();
        $expense = Expenditure::firstOrFail();
        $this->actingAs(User::findOrFail(3))->post(route('admin.expenditures.approve', $expense))->assertSessionHas('success');
        $this->mock(MobileMoneyService::class, function ($mock): void {
            $mock->shouldReceive('validatePhoneNumber')->once()->andReturn(['valid' => true, 'formatted_phone' => '256771234567', 'network' => 'MTN']);
            $mock->shouldReceive('disburse')->once()->andReturn(['success' => true, 'reference' => 'TEST-OPERATING-001', 'status_code' => '00']);
        });
        $this->actingAs(User::findOrFail(4))->post(route('admin.expenditures.pay', $expense), $this->paymentData())->assertSessionHas('success');
        $this->assertSame('paid', $expense->fresh()->status);
        $this->assertSame(950000.0, (float) $this->investment->fresh()->amount);
        $this->assertSame(0, StaffRemunerationCalculation::count());
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_all_required_remuneration_screens_render_through_real_authenticated_routes(): void
    {
        $this->actingAs(User::findOrFail(2));
        foreach ([
            ['admin.expenditures.remuneration', ['officer_id' => 1, 'period_start' => '2026-10-19'], 'Staff remuneration review'],
            ['admin.expenditures.remuneration.policies.index', [], 'Weekly remuneration policies'],
            ['admin.expenditures.remuneration.policies.create', [], 'New remuneration policy version'],
            ['admin.expenditures.remuneration.policies.show', ['policy' => $this->policy->id], $this->policy->version],
            ['admin.expenditures.remuneration.policies.edit', ['policy' => $this->policy->id], 'Edit draft policy'],
        ] as [$route,$parameters,$text]) {
            $response = $this->get(route($route, $parameters))->assertOk()->assertSee($text);
            if ($route === 'admin.expenditures.remuneration') {
                $response->assertSee('performance summary')
                    ->assertSee('Why this result is not payable')
                    ->assertSee('Open policy management');
            }
        }
        $this->actingAs(User::findOrFail(1))->get(route('admin.remuneration.performance', ['week_start' => '2026-10-19']))
            ->assertOk()->assertSee('My remuneration performance')->assertSee('My weekly spending report');
        $this->get(route('admin.remuneration.performance', ['view' => 'month', 'month' => '2026-10']))->assertOk()->assertSee('October 2026');
    }

    public function test_http_retries_do_not_duplicate_weekly_calculations_payouts_or_allocation_journals(): void
    {
        $record = $this->prepareRequest();
        $originalId = $record->expenditure_id;
        $this->requestPayout($record);
        $this->assertSame(1, StaffRemunerationCalculation::count());
        $this->assertSame(1, Expenditure::count());
        $this->assertSame($originalId, $record->fresh()->expenditure_id);
        $this->approveAndAllocate($record);
        $this->post(route('admin.expenditures.remuneration.calculations.funds', $record))->assertSessionHas('success');
        $this->assertSame(1, JournalEntry::where('reference_type', 'StaffRemunerationAllocation')->count());
        $this->assertSame(450000.0, (float) SystemAccount::findOrFail(data_get($this->policy->fresh()->configuration, 'fund_allocation.accounts.revolving'))->running_balance);
    }

    public function test_invalid_performance_dates_and_malformed_report_rows_return_validation_errors(): void
    {
        $this->actingAs(User::findOrFail(1))->getJson(route('admin.remuneration.performance', ['view' => 'month', 'month' => 'not-a-month']))
            ->assertUnprocessable()->assertJsonValidationErrors('month');
        $this->postJson(route('admin.remuneration.spending-report.store'), ['week_start' => '2026-10-19', 'items' => ['not-an-expense-array']])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0');
        $this->assertSame(0, StaffRemunerationCalculation::count());
    }

    public function test_legacy_generator_routes_are_retired_but_read_only_history_is_preserved(): void
    {
        $this->actingAs(User::findOrFail(2))->get(route('admin.expenditures.rollout'))->assertRedirect(route('admin.expenditures.remuneration'));
        $this->post(route('admin.expenditures.rollout.generate'))->assertStatus(410);
        $this->post(route('admin.expenditures.rollout.individual'))->assertStatus(410);
        $rollout = \App\Models\ExpenditureRollout::create([
            'rollout_number' => 'HISTORICAL-001', 'title' => 'Historical staff batch', 'period_start' => '2026-01-05', 'period_end' => '2026-01-11',
            'expense_account_id' => $this->expenseAccount->Id, 'status' => 'paid', 'total_amount' => 75000, 'generated_by' => 2, 'basis' => ['minimum_wage' => 75000],
        ]);
        $this->get(route('admin.expenditures.rollout.show', $rollout))->assertOk()->assertSee('historical legacy staff rollout');
        $this->post(route('admin.expenditures.rollout.approve', $rollout))->assertStatus(410);
        $this->post(route('admin.expenditures.rollout.pay', $rollout))->assertStatus(410);
        $this->assertSame('paid', $rollout->fresh()->status);
        $this->assertSame(0, Expenditure::count());
    }
}
