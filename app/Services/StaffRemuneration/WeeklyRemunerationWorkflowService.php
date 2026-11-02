<?php

namespace App\Services\StaffRemuneration;

use App\Models\Expenditure;
use App\Models\JournalEntry;
use App\Models\StaffRemunerationCalculation;
use App\Models\StaffRemunerationPolicy;
use App\Models\SystemAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WeeklyRemunerationWorkflowService
{
    public function __construct(private readonly WeeklyRemunerationPreviewService $calculator) {}

    public function requestPayout(StaffRemunerationCalculation $record, int $actorId, int $expenseAccountId, ?string $notes = null): Expenditure
    {
        return DB::transaction(function () use ($record, $actorId, $expenseAccountId, $notes): Expenditure {
            User::whereKey($record->officer_id)->lockForUpdate()->firstOrFail();
            $record = StaffRemunerationCalculation::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($record->expenditure_id) {
                return $record->expenditure;
            }
            $this->assertReviewable($record);
            $this->assertReleaseReady($record);
            if ($record->net_remuneration === null || (float) $record->net_remuneration <= 0) {
                $this->fail('Positive net remuneration is required for a payout request. Authorize a zero-net week separately.');
            }
            if (! SystemAccount::active()->whereKey($expenseAccountId)->where('category', 'Expense')->exists()) {
                $this->fail('Select an active Expense ledger account for remuneration.');
            }
            $overlap = Expenditure::where('type', 'performance_payout')->where('assigned_user_id', $record->officer_id)
                ->whereNotIn('status', [Expenditure::STATUS_REJECTED, Expenditure::STATUS_CANCELLED])
                ->whereDate('staff_payment_period_start', '<=', $record->period_end)
                ->whereDate('staff_payment_period_end', '>=', $record->period_start)->exists();
            if ($overlap) {
                $this->fail('A staff payment already overlaps this officer and period. Reconcile that request before creating another.');
            }
            $expense = Expenditure::create([
                'expense_number' => Expenditure::nextExpenseNumber(), 'type' => 'performance_payout',
                'title' => 'Staff remuneration - '.($record->officer?->name ?? 'Officer #'.$record->officer_id),
                'description' => 'Weekly remuneration for '.$record->period_start->toDateString().' to '.$record->period_end->toDateString(),
                'expense_account_id' => $expenseAccountId, 'branch_id' => $record->officer?->branch_id,
                'requested_by' => $actorId, 'assigned_user_id' => $record->officer_id,
                'amount' => $record->net_remuneration, 'expense_date' => now()->toDateString(),
                'status' => Expenditure::STATUS_PENDING, 'payment_method' => 'Mobile Money', 'payment_channel' => 'mobile_money',
                'staff_payment_period_start' => $record->period_start->toDateString(),
                'staff_payment_period_end' => $record->period_end->toDateString(),
                'notes' => trim(($notes ?? '')."\nStaff remuneration calculation: ".json_encode([
                    'calculation_id' => $record->id, 'policy_version' => $record->policy_version,
                    'qualified_revenue' => (float) $record->qualified_revenue, 'base_amount' => (float) $record->base_amount,
                    'bonus_amount' => (float) $record->bonus_amount, 'bonus_breakdown' => $record->bonus_breakdown,
                    'budget_snapshot' => $record->budget_snapshot, 'deficit_snapshot' => $record->deficit_snapshot,
                ], JSON_UNESCAPED_SLASHES)),
            ]);
            $record->update([
                'expenditure_id' => $expense->id, 'status' => StaffRemunerationCalculation::STATUS_PENDING_APPROVAL,
                'audit_history' => [...($record->audit_history ?? []), [
                    'event' => 'payout_requested', 'by' => $actorId, 'at' => now()->toIso8601String(), 'expenditure_id' => $expense->id,
                ]],
            ]);

            return $expense;
        });
    }

    public function settleZeroPayout(StaffRemunerationCalculation $record, int $actorId): void
    {
        DB::transaction(function () use ($record, $actorId): void {
            User::whereKey($record->officer_id)->lockForUpdate()->firstOrFail();
            $record = StaffRemunerationCalculation::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->assertReviewable($record);
            $this->assertReleaseReady($record);
            if ($record->net_remuneration === null || (float) $record->net_remuneration !== 0.0) {
                $this->fail('Only a fully resolved zero-net week can be settled without a payout.');
            }
            abort_if((int) $record->generated_by === $actorId || (int) $record->officer_id === $actorId, 403, 'Independent authorization is required.');
            $record->update([
                'status' => StaffRemunerationCalculation::STATUS_SETTLED, 'approved_by' => $actorId, 'approved_at' => now(),
                'audit_history' => [...($record->audit_history ?? []), ['event' => 'zero_payout_settled', 'by' => $actorId, 'at' => now()->toIso8601String()]],
            ]);
        });
    }

    public function submitReport(int $officerId, StaffRemunerationPolicy $policy, string $week, array $data): StaffRemunerationCalculation
    {
        $start = Carbon::parse($week)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        return DB::transaction(function () use ($officerId, $policy, $start, $end, $data) {
            User::whereKey($officerId)->lockForUpdate()->firstOrFail();
            $calculation = StaffRemunerationCalculation::forOfficerPeriod($officerId, $start, $end)->lockForUpdate()->first();
            if ($calculation) {
                $this->assertReviewable($calculation);
            } else {
                $calculation = $this->calculator->persistPreview($this->calculator->preview($officerId, $policy, $start, $end), $policy, $officerId);
            }
            if (data_get($calculation->budget_snapshot, 'report.status') === 'approved') {
                $this->fail('An administrator must return the approved report before it can be changed.');
            }
            $items = [];
            $references = [];
            $ids = [];
            foreach ($data['items'] ?? [] as $item) {
                if (empty($item['amount']) && empty($item['description'])) {
                    continue;
                }
                $date = Carbon::parse($item['date'])->startOfDay();
                if ($date->lt($start) || $date->gt($end)) {
                    $this->fail('Every reported expense must fall within the selected week.');
                }
                $reference = strtolower(trim((string) $item['reference']));
                if ($reference === '' || isset($references[$reference])) {
                    $this->fail('Each expense needs a distinct receipt or evidence reference.');
                }
                $references[$reference] = true;
                $id = ! empty($item['expenditure_id']) ? (int) $item['expenditure_id'] : null;
                if ($id) {
                    if (isset($ids[$id])) {
                        $this->fail('The same expenditure cannot be reported twice.');
                    }
                    $ids[$id] = true;
                    $expense = Expenditure::whereKey($id)->where('assigned_user_id', $officerId)
                        ->where('type', 'operational')->whereDate('expense_date', $date->toDateString())->first();
                    if (! $expense || abs((float) $expense->amount - (float) $item['amount']) > .001) {
                        $this->fail('A linked expenditure must belong to this officer and match the reported date and amount.');
                    }
                }
                if ((float) $item['amount'] <= 0) {
                    $this->fail('Expense amounts must be positive.');
                }
                $items[] = [...$item, 'expenditure_id' => $id, 'amount' => round((float) $item['amount'], 2)];
            }
            $report = [
                'status' => 'submitted', 'received_amount' => round((float) $data['received_amount'], 2),
                'funding_reference' => trim((string) ($data['funding_reference'] ?? '')),
                'items' => $items, 'reported_expenses' => round(collect($items)->sum('amount'), 2),
                'activities_completed' => (bool) ($data['activities_completed'] ?? false),
                'report_complete' => (bool) ($data['report_complete'] ?? false),
                'notes' => $data['notes'] ?? null, 'submitted_by' => $officerId,
                'submitted_at' => now()->toIso8601String(),
            ];
            if ($report['received_amount'] < 0 || ($report['received_amount'] > 0 && $report['funding_reference'] === '')) {
                $this->fail('Record a funding reference for money received.');
            }
            $snapshot = $calculation->budget_snapshot ?? [];
            $previous = $snapshot['report'] ?? null;
            $snapshot['report'] = $report;
            $calculation->update([
                'budget_snapshot' => $snapshot,
                'audit_history' => [...($calculation->audit_history ?? []), [
                    'event' => 'spending_report_submitted', 'by' => $officerId,
                    'at' => now()->toIso8601String(), 'previous_report' => $previous,
                ]],
            ]);

            return $calculation;
        });
    }

    public function reviewReport(StaffRemunerationCalculation $record, int $reviewerId, bool $approve, string $reason): StaffRemunerationCalculation
    {
        return DB::transaction(function () use ($record, $reviewerId, $approve, $reason) {
            $record = StaffRemunerationCalculation::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->assertReviewable($record);
            $report = data_get($record->budget_snapshot, 'report');
            if (! $report || ! in_array($report['status'], ['submitted', 'approved'], true)) {
                $this->fail('A submitted spending report is required.');
            }
            if ((int) $report['submitted_by'] === $reviewerId) {
                $this->fail('The officer who submitted the report cannot approve or return their own report.');
            }
            if ($approve && (empty($report['report_complete']) || ($report['reported_expenses'] == 0 && empty($report['activities_completed'])))) {
                $this->fail('Verify complete expense reporting and, for zero expenses, completion of required activities.');
            }
            if ($approve) {
                $linked = collect($report['items'])->pluck('expenditure_id')->filter()->all();
                $unreported = Expenditure::where('assigned_user_id', $record->officer_id)
                    ->where('type', 'operational')->whereBetween('expense_date', [$record->period_start->toDateString(), $record->period_end->toDateString()])
                    ->whereIn('status', ['approved', 'payment_pending', 'payment_failed', 'paid'])->whereNotIn('id', $linked)->exists();
                if ($unreported) {
                    $this->fail('Include every approved operating expenditure in the weekly report before approval.');
                }
            }
            $report['status'] = $approve ? 'approved' : 'returned';
            $report['reviewed_by'] = $reviewerId;
            $report['reviewed_at'] = now()->toIso8601String();
            $report['review_reason'] = $reason;
            $report['received_amount_verified'] = $approve;
            $snapshot = $record->budget_snapshot ?? [];
            $snapshot['report'] = $report;
            $record->update(['budget_snapshot' => $snapshot]);
            $policy = $record->policy;
            $manual = ($snapshot['allocation_source'] ?? '') === 'manual' ? (float) $snapshot['allocated_budget'] : null;
            $preview = $this->calculator->preview($record->officer_id, $policy, $record->period_start, $record->period_end, $manual);
            $saved = $this->calculator->persistPreview($preview, $policy, $reviewerId);
            $saved->update(['audit_history' => [...($saved->audit_history ?? []), [
                'event' => $approve ? 'spending_report_approved' : 'spending_report_returned',
                'by' => $reviewerId, 'at' => now()->toIso8601String(), 'reason' => $reason,
            ]]]);

            return $saved;
        });
    }

    /** General expenses must never manufacture or change a calculated staff payout. */
    public function assertPayoutMatchesCalculation(Expenditure $expense, bool $requireFundPosting = false): void
    {
        if ($expense->type !== 'performance_payout') {
            return;
        }
        $record = StaffRemunerationCalculation::where('expenditure_id', $expense->id)->first();
        $expectedStatus = match ($expense->status) {
            Expenditure::STATUS_PENDING => StaffRemunerationCalculation::STATUS_PENDING_APPROVAL,
            Expenditure::STATUS_APPROVED => StaffRemunerationCalculation::STATUS_APPROVED,
            Expenditure::STATUS_PAYMENT_FAILED => StaffRemunerationCalculation::STATUS_PAYMENT_FAILED,
            Expenditure::STATUS_PAYMENT_PENDING => StaffRemunerationCalculation::STATUS_PAYMENT_PENDING,
            default => null,
        };
        if (! $record || ! $expectedStatus || $record->status !== $expectedStatus
            || ! data_get($record->input_snapshot, 'ready_for_payment')
            || (int) $record->officer_id !== (int) $expense->assigned_user_id
            || $record->net_remuneration === null || (float) $record->net_remuneration <= 0
            || abs((float) $record->net_remuneration - (float) $expense->amount) > .001
            || $record->period_start->toDateString() !== $expense->staff_payment_period_start?->toDateString()
            || $record->period_end->toDateString() !== $expense->staff_payment_period_end?->toDateString()) {
            $this->fail('Staff payments must match a resolved, saved remuneration calculation. Create the request from remuneration review, not general expenses.');
        }
        if ($requireFundPosting && data_get($record->input_snapshot, 'fund_allocations.enabled')) {
            $journalId = data_get($record->input_snapshot, 'fund_posting.journal_id');
            if (! $journalId || ! JournalEntry::whereKey($journalId)->where('status', 'posted')
                ->where('reference_type', 'StaffRemunerationAllocation')->where('reference_id', $record->id)->exists()) {
                $this->fail('Post and reconcile this officer\'s fund allocations before sending remuneration.');
            }
        }
    }

    /** Preserve the rejected request and reverse its non-cash allocations before unlocking. */
    public function reopenRejectedPayout(StaffRemunerationCalculation $record, int $actorId, string $reason): StaffRemunerationCalculation
    {
        if (trim($reason) === '') {
            $this->fail('A correction reason is required.');
        }

        return DB::transaction(function () use ($record, $actorId, $reason) {
            User::whereKey($record->officer_id)->lockForUpdate()->firstOrFail();
            $record = StaffRemunerationCalculation::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $expense = Expenditure::whereKey($record->expenditure_id)->lockForUpdate()->first();
            if (! $expense || ! in_array($expense->status, [Expenditure::STATUS_REJECTED, Expenditure::STATUS_CANCELLED], true)
                || $record->status !== StaffRemunerationCalculation::STATUS_REVIEW) {
                $this->fail('Only a rejected or cancelled unpaid request can be reopened for correction.');
            }
            if ($expense->paid_at || $expense->investment_debited_at || $expense->journal_entry_id
                || in_array($expense->mobile_money_status, ['pending', 'completed', '00'], true)
                || JournalEntry::where('reference_type', 'Expenditure')->where('reference_id', $expense->id)->where('status', '!=', 'reversed')->exists()) {
                $this->fail('Reconcile payment and funding first; a paid or potentially in-flight request cannot be replaced.');
            }
            $journal = JournalEntry::where('reference_type', 'StaffRemunerationAllocation')
                ->where('reference_id', $record->id)->where('status', 'posted')->lockForUpdate()->first();
            $reversalId = null;
            if ($journal) {
                if ((int) $record->officer_id === $actorId || (int) $record->generated_by === $actorId) {
                    $this->fail('An independent administrator must reverse posted fund allocations when reopening this request.');
                }
                SystemAccount::whereIn('Id', $journal->lines()->pluck('account_id'))->orderBy('Id')->lockForUpdate()->get();
                $reversal = $journal->reverse(mb_substr('Rejected remuneration request '.$expense->expense_number.': '.$reason, 0, 500));
                $journal->update(['reversed_by' => $actorId]);
                $reversal->update(['officer_id' => $record->officer_id, 'posted_by' => $actorId]);
                $reversalId = $reversal->Id;
            }
            $previousInput = $record->input_snapshot ?? [];
            $input = $previousInput;
            unset($input['fund_posting']);
            $input['payout_history'] = [...($input['payout_history'] ?? []), [
                'expenditure_id' => $expense->id, 'expense_number' => $expense->expense_number,
                'status' => $expense->status, 'amount' => $expense->amount,
                'reopened_by' => $actorId, 'reopened_at' => now()->toIso8601String(),
                'reason' => $reason, 'allocation_reversal_id' => $reversalId,
            ]];
            $input['ready_for_payment'] = false;
            $input['issues'] = ['Refresh and review the calculation after reopening the rejected request.'];
            if (isset($input['preview'])) {
                $input['preview']['ready_for_payment'] = false;
                $input['preview']['issues'] = $input['issues'];
            }
            $record->update([
                'expenditure_id' => null, 'approved_by' => null, 'approved_at' => null,
                'input_snapshot' => $input,
                'audit_history' => [...($record->audit_history ?? []), [
                    'event' => 'rejected_payout_reopened', 'by' => $actorId, 'at' => now()->toIso8601String(),
                    'expenditure_id' => $expense->id, 'reason' => $reason,
                    'allocation_reversal_id' => $reversalId, 'previous_input_snapshot' => $previousInput,
                    'previous_policy_snapshot' => $record->policy_snapshot,
                ]],
            ]);

            return $record->fresh();
        });
    }

    public function assertReleaseReady(StaffRemunerationCalculation $record): void
    {
        if (! data_get($record->input_snapshot, 'ready_for_payment') || $record->policy->status !== 'active') {
            $this->fail('A fully resolved calculation under an active policy is required.');
        }
        if ($record->period_end->copy()->endOfDay()->gte(now())) {
            $this->fail('Close the reporting week before releasing remuneration.');
        }
        $pendingEarlier = StaffRemunerationCalculation::where('officer_id', $record->officer_id)
            ->whereDate('period_end', '<', $record->period_start)->whereNotIn('status', ['paid', 'settled'])->exists();
        if ($pendingEarlier) {
            $this->fail('Settle earlier saved weeks before releasing this week so deficits are recovered in order.');
        }
        if (data_get($record->input_snapshot, 'fund_allocations.enabled')) {
            $this->assertAllocationAccounts(data_get($record->input_snapshot, 'fund_allocations.accounts', []));
        }
        $manual = data_get($record->budget_snapshot, 'allocation_source') === 'manual' ? (float) $record->allocated_budget : null;
        $fresh = $this->calculator->preview($record->officer_id, $record->policy, $record->period_start, $record->period_end, $manual);
        foreach (['qualified_revenue' => 'qualified_revenue.qualified_revenue', 'base_amount' => 'base_amount', 'bonus_amount' => 'bonus_amount', 'net_remuneration' => 'net_remuneration', 'deficit_recovery_amount' => 'deficit_recovery_amount', 'approved_budget_expenses' => 'budget.approved_expenses'] as $column => $path) {
            if (abs((float) $record->$column - (float) data_get($fresh, $path)) > .001) {
                $this->fail('Inputs or the opening deficit changed. Refresh and review the calculation before release.');
            }
        }
        if (! $fresh['ready_for_payment']) {
            $this->fail('Current source records require further review: '.implode(' ', $fresh['issues']));
        }
    }

    public function postFundAllocations(StaffRemunerationCalculation $record, int $actorId): JournalEntry
    {
        return DB::transaction(function () use ($record, $actorId) {
            $record = StaffRemunerationCalculation::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if (! in_array($record->status, ['approved', 'payment_pending', 'payment_failed', 'paid', 'settled'], true)
                || ! data_get($record->input_snapshot, 'ready_for_payment')) {
                $this->fail('Approve a fully reconciled calculation before posting its fund allocations.');
            }
            if ((int) $record->generated_by === $actorId || (int) $record->officer_id === $actorId) {
                $this->fail('Fund allocations require an administrator independent of the officer and calculation preparer.');
            }
            $existing = JournalEntry::where('reference_type', 'StaffRemunerationAllocation')->where('reference_id', $record->id)->where('status', '!=', 'reversed')->first();
            if ($existing) {
                return $existing;
            }
            $funds = data_get($record->input_snapshot, 'fund_allocations');
            if (empty($funds['enabled']) || abs($funds['reconciliation_difference'] ?? 1) > .01 || $funds['operations_reserve'] === null) {
                $this->fail('Fund amounts and approved received-funds reporting must reconcile before posting.');
            }
            $accounts = $funds['accounts'] ?? [];
            $this->assertAllocationAccounts($accounts, true);
            $holding = SystemAccount::findOrFail($accounts['management']);
            if ((float) $holding->running_balance + (float) $funds['management_allocation'] < (float) $funds['operations_reserve']) {
                $this->fail('The management holding account cannot fund the reserve transfer; reconcile earlier budget funding first.');
            }
            $credits = ['management' => $funds['management_allocation'], 'revolving' => $funds['revolving_fund'], 'growth' => $funds['growth_total'], 'unallocated' => $funds['unallocated_bonus_held']];
            $lines = [['account_id' => $accounts['source'], 'debit' => array_sum($credits), 'credit' => 0, 'narrative' => 'Weekly revenue allocation; officer '.$record->officer_id]];
            foreach ($credits as $key => $amount) {
                if ($amount > 0) {
                    $lines[] = ['account_id' => $accounts[$key], 'debit' => 0, 'credit' => $amount, 'narrative' => $key.'; officer '.$record->officer_id];
                }
            }
            if ($funds['operations_reserve'] > 0) {
                $lines[] = ['account_id' => $accounts['management'], 'debit' => $funds['operations_reserve'], 'credit' => 0, 'narrative' => 'Unused received operating funds reclassified; earlier budget funding'];
                $lines[] = ['account_id' => $accounts['operations_reserve'], 'debit' => 0, 'credit' => $funds['operations_reserve'], 'narrative' => 'Officer-tagged internal operations reserve'];
            }
            $journal = JournalEntry::postJournal([
                'transaction_date' => $record->period_end->toDateString(),
                'reference_type' => 'StaffRemunerationAllocation', 'reference_id' => $record->id,
                'officer_id' => $record->officer_id, 'posted_by' => $actorId,
                'narrative' => 'Remuneration allocations #'.$record->id.'; policy '.$record->policy_version,
            ], $lines);
            $input = $record->input_snapshot;
            $input['fund_posting'] = ['journal_id' => $journal->Id, 'posted_by' => $actorId, 'posted_at' => now()->toIso8601String()];
            $record->update(['input_snapshot' => $input, 'audit_history' => [...($record->audit_history ?? []), ['event' => 'funds_posted', ...$input['fund_posting']]]]);

            return $journal;
        });
    }

    public function assertAllocationAccounts(array $accounts, bool $lock = false): void
    {
        $keys = ['source', 'management', 'revolving', 'growth', 'operations_reserve', 'unallocated'];
        $ids = array_map(fn ($key) => (int) ($accounts[$key] ?? 0), $keys);
        if (in_array(0, $ids, true) || count(array_unique($ids)) !== count($keys)) {
            $this->fail('Configure six distinct ledger accounts in this policy version before approving or releasing payouts.');
        }
        foreach ($keys as $key) {
            $account = SystemAccount::active()->whereKey($accounts[$key])->when($lock, fn ($query) => $query->lockForUpdate())->first();
            $categories = $key === 'source' ? ['Income', 'Equity'] : ['Liability', 'Equity'];
            if (! $account || $account->is_cash_bank || ! in_array($account->category, $categories, true)) {
                $this->fail('Invalid allocation ledger account for '.$key.'. Use a non-cash Income/Equity source and Liability/Equity holding accounts.');
            }
        }
    }

    private function assertReviewable(StaffRemunerationCalculation $record): void
    {
        if (! $record->isReviewable()) {
            $this->fail('This week is already in the approval/payment workflow and its spending report is locked.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['report' => $message]);
    }
}
