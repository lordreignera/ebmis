<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\StaffRemunerationCalculation;
use App\Models\StaffRemunerationPolicy;
use App\Models\SystemAccount;
use App\Services\StaffRemuneration\WeeklyRemunerationPreviewService;
use App\Services\StaffRemuneration\WeeklyRemunerationWorkflowService;
use Database\Seeders\StaffRemunerationDraftSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffRemunerationTest extends TestCase
{
    private StaffRemunerationPolicy $policy;

    private WeeklyRemunerationPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // This suite must never migrate or reset the application database.
        config(['database.default' => 'staff_test', 'database.connections.staff_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('staff_test');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('users')->insert([['id' => 1, 'name' => 'Officer'], ['id' => 2, 'name' => 'Reviewer']]);
        Schema::create('expenditures', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assigned_user_id');
            $table->string('type');
            $table->string('status');
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
        });
        Schema::create('personal_loans', function (Blueprint $table): void {
            $table->id();
            $table->integer('assigned_to');
            $table->integer('member_id');
            $table->integer('status')->default(2);
            $table->date('datecreated');
            $table->date('date_closed')->nullable();
            $table->string('frequency')->default('weekly');
        });
        Schema::create('loan_schedules', function (Blueprint $table): void {
            $table->id();
            $table->integer('loan_id');
            $table->date('payment_date');
            $table->decimal('payment', 15, 2);
            $table->decimal('interest', 15, 2);
            $table->decimal('principal', 15, 2);
            $table->decimal('paid', 15, 2)->default(0);
            $table->integer('status')->default(0);
        });
        Schema::create('repayments', function (Blueprint $table): void {
            $table->id();
            $table->integer('loan_id');
            $table->integer('schedule_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->integer('status')->default(1);
            $table->string('payment_status')->default('Confirmed');
            $table->dateTime('date_created');
        });
        Schema::create('fees_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('fees', function (Blueprint $table): void {
            $table->id();
            $table->integer('loan_id')->nullable();
            $table->integer('added_by');
            $table->integer('fees_type_id');
            $table->integer('status')->default(1);
            $table->dateTime('datecreated');
            $table->decimal('amount', 15, 2);
            $table->string('description');
        });
        Schema::create('loan_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->integer('assigned_to');
            $table->integer('loan_id');
            $table->string('loan_type')->default('personal');
            $table->dateTime('follow_up_at');
        });
        Schema::create('system_accounts', function (Blueprint $table): void {
            $table->bigIncrements('Id');
            $table->string('code');
            $table->string('name');
            $table->string('category');
            $table->boolean('is_cash_bank')->default(false);
            $table->integer('status')->default(1);
            $table->decimal('running_balance', 15, 2)->default(0);
        });
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->bigIncrements('Id');
            $table->string('journal_number')->unique();
            $table->date('transaction_date');
            $table->string('reference_type');
            $table->integer('reference_id');
            $table->integer('officer_id');
            $table->text('narrative');
            $table->integer('posted_by');
            $table->dateTime('posted_at');
            $table->string('status');
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->timestamps();
        });
        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->bigIncrements('Id');
            $table->integer('journal_entry_id');
            $table->integer('line_number');
            $table->integer('account_id');
            $table->decimal('debit_amount', 15, 2);
            $table->decimal('credit_amount', 15, 2);
            $table->text('narrative');
            $table->timestamps();
        });
        (require database_path('migrations/2025_10_29_145600_create_permission_tables.php'))->up();
        foreach ([
            '2026_10_24_000001_create_staff_remuneration_policies.php',
            '2026_10_24_000002_create_staff_remuneration_weekly_calculations.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        (new StaffRemunerationDraftSeeder)->run();
        $this->policy = StaffRemunerationPolicy::firstOrFail();
        $this->service = app(WeeklyRemunerationPreviewService::class);
    }

    protected function tearDown(): void
    {
        DB::disconnect('staff_test');
        parent::tearDown();
    }

    private function loan(string $due = '2026-11-30', int $member = 1, int $officer = 1): array
    {
        $id = DB::table('personal_loans')->insertGetId([
            'assigned_to' => $officer, 'member_id' => $member, 'datecreated' => '2026-01-01', 'status' => 2,
        ]);
        $schedule = DB::table('loan_schedules')->insertGetId([
            'loan_id' => $id, 'payment_date' => $due, 'interest' => 1000000,
            'principal' => 4000000, 'payment' => 5000000,
        ]);

        return [$id, $schedule];
    }

    private function repayment(array $loan, string $date, float $amount = 1000000, int $status = 1): void
    {
        DB::table('repayments')->insert([
            'loan_id' => $loan[0], 'schedule_id' => $loan[1], 'date_created' => $date,
            'amount' => $amount, 'status' => $status, 'payment_status' => 'Completed',
        ]);
    }

    private function expense(float $amount, string $type = 'operational', string $status = 'approved', int $officer = 1): void
    {
        DB::table('expenditures')->insert([
            'assigned_user_id' => $officer, 'expense_date' => '2026-10-22',
            'amount' => $amount, 'type' => $type, 'status' => $status,
        ]);
    }

    private function preview(?float $budget = 100000): array
    {
        return $this->service->preview(1, $this->policy, '2026-10-19', '2026-10-25', $budget);
    }

    public function test_two_migrations_create_only_two_remuneration_tables_and_reseeding_preserves_rules(): void
    {
        $tables = array_column(Schema::getTables(), 'name');
        $this->assertCount(2, array_filter($tables, fn ($name) => str_starts_with($name, 'staff_remuneration_')));
        $this->assertCount(20, $this->policy->bonusBands);
        $this->assertFalse(Schema::hasColumn('staff_remuneration_policies', 'par_trigger_days'));
        $this->assertFalse(Schema::hasColumn('expenditures', 'remuneration_calculation_id'));
        $this->policy->update(['base_rate_above_threshold' => 11, 'status' => 'active']);
        (new StaffRemunerationDraftSeeder)->run();
        $this->assertSame('11.000', $this->policy->fresh()->base_rate_above_threshold);
        $this->assertSame('active', $this->policy->fresh()->status);
        $this->assertSame(1, StaffRemunerationPolicy::count());
    }

    public static function bands(): array
    {
        return [[0, 0], [89.9999, 0], [90, .75], [92.9999, .75], [93, 1.5], [94.9999, 1.5], [95, 2.5], [97.9999, 2.5], [98, 3], [100, 3]];
    }

    #[DataProvider('bands')]
    public function test_all_four_bonus_boundaries_use_unrounded_scores(float $score, float $expected): void
    {
        $method = new \ReflectionMethod($this->service, 'rateForScore');
        foreach (['par', 'follow_up', 'portfolio_growth', 'budget_performance'] as $metric) {
            $this->assertSame($expected, $method->invoke($this->service, $this->policy, $metric, $score));
        }
    }

    public function test_previous_week_budget_has_exact_boundaries_and_officer_attribution(): void
    {
        $loan = $this->loan();
        $this->repayment($loan, '2026-10-12 00:00:00', 500000);
        $this->repayment($loan, '2026-10-18 23:59:59', 500000);
        $this->repayment($this->loan(member: 2, officer: 2), '2026-10-15 12:00:00');
        $budget = $this->preview(null)['budget'];
        $this->assertSame('2026-10-12', $budget['previous_period_start']);
        $this->assertSame('2026-10-18', $budget['previous_period_end']);
        $this->assertSame(1000000.0, $budget['previous_week_qualified_revenue']);
        $this->assertSame(100000.0, $budget['allocated_budget']);
    }

    public static function spending(): array
    {
        return [[80000, 100, 3], [100000, 100, 3], [105000, 95.23809523809524, 2.5], [120000, 83.33333333333334, 0]];
    }

    #[DataProvider('spending')]
    public function test_budget_scoring_and_independent_bonus_amounts(float $spend, float $score, float $rate): void
    {
        $this->repayment($this->loan(), '2026-10-22 12:00:00');
        $this->expense($spend);
        $preview = $this->preview();
        $this->assertEqualsWithDelta($score, $preview['budget']['score'], .0000001);
        $this->assertSame($rate, $preview['metrics']['budget_performance']['earned_rate']);
        $this->assertSame(round(1000000 * $rate / 100, 2), $preview['metrics']['budget_performance']['bonus_amount']);
        $this->assertEquals(array_sum(array_column($preview['metrics'], 'bonus_amount')), $preview['bonus_amount']);
        $this->assertSame(max(0.0, $spend - 100000), $preview['budget']['budget_overrun']);
        $this->assertEquals($preview['gross_remuneration'] - $preview['current_bonus_offset'], $preview['net_remuneration']);
        $this->assertLessThanOrEqual(12, $preview['total_bonus_rate']);
    }

    public function test_payroll_allowances_and_other_officers_expenses_are_excluded_and_pending_expenses_block_payment(): void
    {
        $this->loan();
        $this->expense(80000);
        $this->expense(200000, 'performance_payout');
        $this->expense(200000, 'allowance');
        $this->expense(200000, officer: 2);
        $this->expense(5000, status: 'pending');
        $preview = $this->preview();
        $this->assertSame(80000.0, $preview['budget']['approved_expenses']);
        $this->assertSame(1, $preview['budget']['pending_expense_count']);
        $this->assertCount(1, $preview['budget']['expense_ids']);
        $this->assertFalse($preview['ready_for_payment']);
    }

    public function test_zero_budget_and_unverified_zero_spending_remain_unresolved(): void
    {
        $this->loan();
        $this->assertNull($this->preview(0)['budget']['score']);
        $this->assertNull($this->preview()['budget']['score']);
        $this->assertFalse($this->preview()['ready_for_payment']);
    }

    public function test_followups_count_distinct_active_assigned_loans_and_current_score_only_controls_base(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $loan = $this->loan($i === 0 ? '2026-10-20' : '2026-11-30', $i + 1);
            if ($i < 5) {
                for ($repeat = 0; $repeat < 2; $repeat++) {
                    DB::table('loan_follow_ups')->insert(['assigned_to' => 1, 'loan_id' => $loan[0], 'follow_up_at' => '2026-10-22']);
                }
            }
        }
        $preview = $this->preview();
        $this->assertSame(90.0, $preview['current_loan_score']);
        $this->assertSame(12.0, $preview['base_rate']);
        $this->assertSame(50.0, $preview['follow_ups']['score']);
        $this->assertCount(4, $preview['metrics']);
        $this->assertArrayNotHasKey('current_loan', $preview['metrics']);
        DB::table('loan_schedules')->where('id', 2)->update(['payment_date' => '2026-10-20']);
        $this->assertSame(5.0, $this->preview()['base_rate']);
    }

    public function test_par_is_strictly_more_than_120_calendar_days_and_uses_full_principal_for_every_frequency(): void
    {
        foreach (['daily', 'weekly', 'monthly'] as $frequency) {
            $risk = $this->loan('2026-06-26'); // 121 days before 25 October.
            DB::table('personal_loans')->where('id', $risk[0])->update(['frequency' => $frequency]);
            DB::table('loan_schedules')->insert(['loan_id' => $risk[0], 'payment_date' => '2026-12-01', 'interest' => 0, 'principal' => 1000000, 'payment' => 1000000]);
        }
        $this->loan('2026-06-27'); // Exactly 120 days: excluded from PAR numerator.
        $preview = $this->preview();
        $this->assertSame(15000000.0, $preview['portfolio']['par_risk_principal']);
        $this->assertSame(19000000.0, $preview['portfolio']['par_total_outstanding_principal']);
        $this->assertSame(0.0, $preview['metrics']['par']['score']);
        $this->assertFalse($preview['ready_for_payment']);
    }

    public function test_future_cached_payments_do_not_change_historical_portfolio_and_growth_counts_distinct_clients(): void
    {
        $loan = $this->loan(member: 1);
        $this->loan(member: 1);
        DB::table('loan_schedules')->where('id', $loan[1])->update(['paid' => 5000000]);
        $this->repayment($loan, '2026-10-26', 5000000);
        $preview = $this->preview();
        $this->assertSame(2, $preview['portfolio']['total_active_loans']);
        $this->assertSame(1, $preview['growth']['closing_active_clients']);
        $this->assertSame(1, $preview['growth']['opening_active_clients']);
    }

    public function test_reversed_repayments_and_principal_are_excluded_from_qualified_revenue(): void
    {
        $loan = $this->loan();
        $this->repayment($loan, '2026-10-22', 1500000);
        $this->repayment($loan, '2026-10-22', 1000000, 3);
        $revenue = $this->preview()['qualified_revenue'];
        $this->assertSame(1000000.0, $revenue['qualified_revenue']);
        $this->assertSame(500000.0, $revenue['principal_collected']);
    }

    public function test_saving_is_idempotent_preserves_snapshots_and_rejects_payment_failed_overwrites(): void
    {
        $this->repayment($this->loan(), '2026-10-22');
        $preview = $this->preview();
        $first = $this->service->persistPreview($preview, $this->policy, 2);
        $second = $this->service->persistPreview($preview, $this->policy, 2);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, StaffRemunerationCalculation::count());
        $this->assertCount(2, $second->audit_history);
        $this->assertCount(4, $second->bonus_breakdown);
        $this->assertCount(20, $second->policy_snapshot['bonus_bands']);
        $second->update(['status' => 'payment_failed']);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->persistPreview($preview, $this->policy, 2);
    }

    public function test_unresolved_amounts_stay_null_when_saved_and_payment_link_is_on_calculation(): void
    {
        $calculation = $this->service->persistPreview($this->preview(), $this->policy, 2);
        $this->assertNull($calculation->base_amount);
        $this->assertNull($calculation->net_remuneration);
        $this->expense(1000);
        $calculation->update(['expenditure_id' => 1]);
        $this->assertSame(1, $calculation->expenditure->id);
        $this->assertSame($calculation->id, $calculation->expenditure->remunerationCalculation->id);
    }

    public static function riskBands(): array
    {
        return [[0, 100], [3, 100], [3.000001, 90], [5, 90], [5.000001, 75], [8, 75], [8.000001, 60], [10, 60], [10.000001, 0], [100, 0]];
    }

    #[DataProvider('riskBands')]
    public function test_client_workbook_par_conversion_boundaries(float $risk, float $expected): void
    {
        $this->assertSame($expected, $this->service->parPerformanceScore($risk, $this->policy->configuration['par_score_mapping']));
    }

    public function test_unapproved_or_malformed_par_conversion_stays_unresolved(): void
    {
        $this->assertNull($this->service->parPerformanceScore(2, null));
        $this->assertNull($this->service->parPerformanceScore(null, $this->policy->configuration['par_score_mapping']));
        $this->assertNull($this->service->parPerformanceScore(2, [['upper_risk_percent' => 3, 'score' => 150]]));
    }

    public function test_current_overrun_is_deducted_from_gross_before_prior_deficit_and_cannot_be_deducted_twice(): void
    {
        $result = $this->service->payoutDeductions(110000, 60000, 80000, 20000, $this->policy);
        $this->assertSame(80000.0, $result['gross_offset']);
        $this->assertSame(60000.0, $result['bonus_portion']);
        $this->assertSame(20000.0, $result['recovery']);
        $this->assertSame(10000.0, $result['net']);
        $this->assertSame(0.0, $result['new_deficit']);
        $exhausted = $this->service->payoutDeductions(110000, 60000, 150000, 5000, $this->policy);
        $this->assertSame(0.0, $exhausted['net']);
        $this->assertSame(45000.0, $exhausted['closing_deficit']);
    }

    public function test_unpaid_deficit_rolls_through_subsequent_zero_payout_weeks_until_recovered(): void
    {
        $opening = 20000;
        foreach ([[12000, 0, 8000], [7000, 0, 1000], [9000, 8000, 0]] as [$gross, $net, $closing]) {
            $result = $this->service->payoutDeductions($gross, 0, 0, $opening, $this->policy);
            $this->assertSame((float) $net, $result['net']);
            $this->assertSame((float) $closing, $result['closing_deficit']);
            $opening = $result['closing_deficit'];
        }
    }

    public function test_one_net_new_client_meets_growth_target_and_multiple_loans_do_not_inflate_growth(): void
    {
        $this->loan(member: 1);
        $new = $this->loan(member: 2);
        $repeat = $this->loan(member: 2);
        DB::table('personal_loans')->whereIn('id', [$new[0], $repeat[0]])->update(['datecreated' => '2026-10-20']);
        $preview = $this->preview();
        $this->assertSame(1, $preview['growth']['net_change']);
        $this->assertSame(100, $preview['growth']['score']);
        $this->assertSame(3.0, $preview['metrics']['portfolio_growth']['earned_rate']);
    }

    private function reportFixture(float $spending = 80000): array
    {
        $old = $this->loan(member: 1);
        $this->repayment($old, '2026-10-15');
        $new = $this->loan(member: 2);
        DB::table('personal_loans')->where('id', $new[0])->update(['datecreated' => '2026-10-20']);
        $this->repayment($new, '2026-10-22');
        foreach ([$old, $new] as $loan) {
            DB::table('loan_follow_ups')->insert(['assigned_to' => 1, 'loan_id' => $loan[0], 'follow_up_at' => '2026-10-22']);
        }
        $items = [];
        if ($spending > 0) {
            $this->expense($spending);
            $items[] = ['date' => '2026-10-22', 'category' => 'transport', 'description' => 'Field travel', 'amount' => $spending, 'reference' => 'RECEIPT-001', 'expenditure_id' => 1];
        }

        return ['received_amount' => 100000, 'funding_reference' => 'MM-FUNDING-001', 'items' => $items, 'report_complete' => true, 'activities_completed' => true];
    }

    public function test_officer_report_approval_resolves_budget_and_four_bonuses_and_reconciles_14_percent_pool(): void
    {
        $data = $this->reportFixture();
        $workflow = app(WeeklyRemunerationWorkflowService::class);
        $record = $workflow->submitReport(1, $this->policy, '2026-10-19', $data);
        $this->assertSame('submitted', data_get($record->budget_snapshot, 'report.status'));
        $this->assertFalse(data_get($record->input_snapshot, 'ready_for_payment'));
        $saved = $workflow->reviewReport($record, 2, true, 'Funding and receipts verified.');
        $this->assertTrue(data_get($saved->input_snapshot, 'ready_for_payment'));
        $this->assertSame('12.000', $saved->total_bonus_rate);
        $this->assertSame('240000.00', $saved->gross_remuneration);
        $funds = $saved->input_snapshot['fund_allocations'];
        $this->assertSame(450000.0, (float) $funds['revolving_fund']);
        $this->assertSame(190000.0, (float) $funds['growth_total']);
        $this->assertSame(20000.0, (float) $funds['unallocated_bonus_held']);
        $this->assertSame(20000.0, (float) $funds['operations_reserve']);
        $this->assertSame(0.0, (float) $funds['reconciliation_difference']);
    }

    public function test_zero_spending_earns_full_budget_score_only_after_verified_complete_report(): void
    {
        $workflow = app(WeeklyRemunerationWorkflowService::class);
        $record = $workflow->submitReport(1, $this->policy, '2026-10-19', $this->reportFixture(0));
        $saved = $workflow->reviewReport($record, 2, true, 'Verified zero spending and completed activities.');
        $this->assertSame('100.000', $saved->budget_score);
        $this->assertSame(100000.0, (float) data_get($saved->input_snapshot, 'fund_allocations.operations_reserve'));
    }

    public function test_reporter_cannot_approve_their_own_expenses(): void
    {
        $workflow = app(WeeklyRemunerationWorkflowService::class);
        $record = $workflow->submitReport(1, $this->policy, '2026-10-19', $this->reportFixture());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $workflow->reviewReport($record, 1, true, 'Self approval');
    }

    public function test_duplicate_reported_receipts_are_rejected(): void
    {
        $data = $this->reportFixture();
        $data['items'][] = $data['items'][0];
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(WeeklyRemunerationWorkflowService::class)->submitReport(1, $this->policy, '2026-10-19', $data);
    }

    public function test_reduced_base_growth_transfers_use_earned_bonus_before_offsets(): void
    {
        $funds = $this->service->fundAllocations($this->policy, 1000000, 5, 50000, 60000, ['allocated_budget' => 100000, 'approved_expenses' => 180000, 'received_amount' => 100000]);
        $this->assertSame(70000.0, $funds['base_pay_difference']);
        $this->assertSame(80000.0, $funds['unearned_bonus_to_growth']);
        $this->assertSame(340000.0, $funds['growth_total']);
        $this->assertSame(0.0, $funds['operations_reserve']);
        $this->assertSame(0.0, $funds['reconciliation_difference']);
    }

    public function test_cent_rounding_never_creates_a_negative_fund_holding(): void
    {
        $funds = $this->service->fundAllocations($this->policy, .17, 12, .02, .04, [
            'allocated_budget' => 1, 'approved_expenses' => 1, 'received_amount' => 1,
        ]);
        $this->assertGreaterThanOrEqual(0, $funds['unallocated_bonus_held']);
        $this->assertNotSame(0.0, $funds['reconciliation_difference']);
    }

    public function test_reserve_uses_received_funds_not_the_unfunded_allowance(): void
    {
        $funds = $this->service->fundAllocations($this->policy, 1000000, 12, 120000, 120000, [
            'allocated_budget' => 100000, 'approved_expenses' => 40000, 'received_amount' => 60000,
        ]);
        $this->assertSame(20000.0, $funds['operations_reserve']);
        $this->assertSame(60000.0, $funds['unused_authorized_budget']);
        $this->assertSame(0.0, $funds['reconciliation_difference']);
    }

    public function test_policy_edit_records_previous_rules_and_actor_without_a_third_table(): void
    {
        $before = count($this->policy->configuration['audit_history']);
        $this->actingAs(\App\Models\User::findOrFail(2));
        $this->policy->update(['par_overdue_days' => 150]);
        $audit = $this->policy->fresh()->configuration['audit_history'];
        $this->assertCount($before + 1, $audit);
        $last = $audit[array_key_last($audit)];
        $this->assertSame(2, $last['by']);
        $this->assertSame(120, $last['previous']['par_overdue_days']);
        $this->assertContains('par_overdue_days', $last['changed_fields']);
    }

    public function test_completed_week_displays_frozen_snapshot_after_later_policy_and_source_changes(): void
    {
        $loan = $this->loan();
        $this->repayment($loan, '2026-10-22');
        $record = $this->service->persistPreview($this->preview(), $this->policy, 2);
        $record->update(['status' => 'paid']);
        $this->policy->update(['base_rate_above_threshold' => 10]);
        $this->repayment($loan, '2026-10-23');
        $display = $this->service->displayPreview(1, $this->policy, '2026-10-19', '2026-10-25');
        $this->assertSame(1000000.0, (float) $display['qualified_revenue']['qualified_revenue']);
        $this->assertSame(12.0, (float) $display['base_rate']);
    }

    public function test_incomplete_or_duplicate_fund_account_mapping_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(WeeklyRemunerationWorkflowService::class)->assertAllocationAccounts(['source' => 1, 'management' => 1]);
    }

    public function test_officer_spending_and_administrator_review_views_render_the_same_saved_report(): void
    {
        $workflow = app(WeeklyRemunerationWorkflowService::class);
        $record = $workflow->submitReport(1, $this->policy, '2026-10-19', $this->reportFixture());
        $data = ['calculation' => $record, 'report' => $record->budget_snapshot['report'],
            'periodStart' => $record->period_start, 'periodEnd' => $record->period_end,
            'errors' => new \Illuminate\Support\ViewErrorBag];
        $html = view('admin.staff-remuneration.partials.spending-report', $data)->render();
        $this->assertStringContainsString('My weekly spending report', $html);
        $this->assertStringContainsString('MM-FUNDING-001', $html);
        $this->assertStringContainsString('RECEIPT-001', $html);
        $html = view('admin.staff-remuneration.partials.administrative-review', ['savedCalculation' => $record, 'policy' => $this->policy])->render();
        $this->assertStringContainsString('Approve verified report', $html);
        $this->assertStringContainsString('RECEIPT-001', $html);
        $record = $workflow->reviewReport($record, 2, true, 'Verified.');
        $html = view('admin.staff-remuneration.partials.fund-statement', ['preview' => $record->input_snapshot['preview']])->render();
        $this->assertStringContainsString('450,000.00', $html);
        $this->assertStringContainsString('20,000.00', $html);
    }

    public function test_officer_fund_posting_is_balanced_tagged_and_idempotent_without_mobile_money(): void
    {
        $accounts = [];
        foreach (['source', 'management', 'revolving', 'growth', 'operations_reserve', 'unallocated'] as $index => $key) {
            $account = SystemAccount::create(['code' => 'FUND'.$index, 'name' => $key, 'category' => $key === 'source' ? 'Income' : 'Equity', 'status' => 1, 'running_balance' => 0]);
            $accounts[$key] = $account->Id;
        }
        $config = $this->policy->configuration;
        $config['fund_allocation']['accounts'] = $accounts;
        $this->policy->update(['configuration' => $config]);
        $workflow = app(WeeklyRemunerationWorkflowService::class);
        $record = $workflow->submitReport(1, $this->policy, '2026-10-19', $this->reportFixture());
        $record = $workflow->reviewReport($record, 2, true, 'Verified.');
        $record->update(['status' => 'approved']);
        $journal = $workflow->postFundAllocations($record, 2);
        $second = $workflow->postFundAllocations($record, 2);
        $this->assertSame($journal->Id, $second->Id);
        $this->assertSame(1, JournalEntry::count());
        $this->assertTrue($journal->isBalanced());
        $this->assertSame(1, (int) $journal->officer_id);
        $this->assertSame('450000.00', SystemAccount::find($accounts['revolving'])->running_balance);
        $this->assertSame('20000.00', SystemAccount::find($accounts['operations_reserve'])->running_balance);
    }
}
