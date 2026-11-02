<?php

namespace App\Services\StaffRemuneration;

use App\Models\StaffRemunerationCalculation;
use App\Models\StaffRemunerationPolicy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WeeklyRemunerationPreviewService
{
    public function previewMany(
        iterable $officerIds,
        StaffRemunerationPolicy $policy,
        Carbon|string $periodStart,
        Carbon|string $periodEnd,
        ?float $manualBudget = null
    ): array {
        $previews = [];

        foreach (array_values(array_unique(array_map('intval', is_array($officerIds) ? $officerIds : iterator_to_array($officerIds)))) as $officerId) {
            if ($officerId > 0) {
                $previews[$officerId] = $manualBudget === null
                    ? $this->displayPreview($officerId, $policy, $periodStart, $periodEnd)
                    : $this->preview($officerId, $policy, $periodStart, $periodEnd, $manualBudget);
            }
        }

        return $previews;
    }

    /**
     * Build an informational monthly view from complete weekly calendar periods
     * whose Monday falls inside the selected month. Weekly remuneration remains
     * the source calculation; this does not invent a monthly pay formula.
     */
    public function monthlyPreview(
        int $officerId,
        StaffRemunerationPolicy $policy,
        Carbon|string $month
    ): array {
        $monthDate = $this->date($month)->startOfMonth();
        $monthEnd = $monthDate->copy()->endOfMonth();
        $cursor = $monthDate->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];

        while ($cursor->lte($monthEnd)) {
            if ($cursor->month === $monthDate->month && $cursor->year === $monthDate->year) {
                $weekStart = $cursor->copy()->startOfDay();
                $weekEnd = $cursor->copy()->addDays(6)->endOfDay();
                $weeks[] = $this->displayPreview($officerId, $policy, $weekStart, $weekEnd);
            }

            $cursor->addWeek();
        }

        return [
            'month' => $monthDate->format('Y-m'),
            'period_rule' => 'Complete Monday-Sunday weeks whose Monday falls within the selected month.',
            'weeks' => $weeks,
            'week_count' => count($weeks),
            'qualified_revenue' => round(array_sum(array_map(
                fn (array $week): float => (float) $week['qualified_revenue']['qualified_revenue'],
                $weeks
            )), 2),
            'base_amount' => $this->sumNullable($weeks, 'base_amount'),
            'bonus_amount' => $this->sumNullable($weeks, 'bonus_amount'),
            'gross_remuneration' => $this->sumNullable($weeks, 'gross_remuneration'),
            'net_remuneration' => $this->sumNullable($weeks, 'net_remuneration'),
            'ready_for_payment' => count($weeks) > 0
                && count(array_filter($weeks, fn (array $week): bool => ! $week['ready_for_payment'])) === 0,
            'issues' => array_values(array_unique(array_merge(
                ...array_map(fn (array $week): array => $week['issues'], $weeks) ?: [[]]
            ))),
        ];
    }

    /** Completed/approved weeks display their saved result, never today's rules. */
    public function displayPreview(int $officerId, StaffRemunerationPolicy $policy, Carbon|string $start, Carbon|string $end): array
    {
        $saved = StaffRemunerationCalculation::forOfficerPeriod($officerId, $start, $end)->first();
        if ($saved && ! $saved->isReviewable()
            && is_array(data_get($saved->input_snapshot, 'preview'))) {
            return $saved->input_snapshot['preview'];
        }

        return $this->preview($officerId, $policy, $start, $end);
    }

    public function preview(
        int $officerId,
        StaffRemunerationPolicy $policy,
        Carbon|string $periodStart,
        Carbon|string $periodEnd,
        ?float $manualBudget = null
    ): array {
        $start = $this->date($periodStart)->startOfDay();
        $end = $this->date($periodEnd)->endOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('The remuneration period end must be on or after the period start.');
        }

        $issues = [];
        $revenue = $this->qualifiedRevenue($officerId, $start, $end, $issues);
        $portfolio = $this->portfolioMetrics($officerId, $policy, $start, $end, $issues);
        $followUps = $this->followUpMetrics($officerId, $start, $end, $portfolio['active_loan_ids'] ?? []);
        $growth = $this->growthMetrics($officerId, $start, $end, $policy, $issues);
        $budget = $this->budgetMetrics($officerId, $policy, $start, $end, $manualBudget, $issues);
        $parScore = $this->parPerformanceScore($portfolio['par_percent'], $policy->configuration['par_score_mapping'] ?? null);

        $currentLoanScore = $portfolio['total_active_loans'] > 0
            ? ($portfolio['current_active_loans'] / $portfolio['total_active_loans']) * 100
            : null;

        $baseRate = $currentLoanScore === null
            ? null
            : ($currentLoanScore >= (float) $policy->base_current_loan_threshold
                ? (float) $policy->base_rate_above_threshold
                : (float) $policy->base_rate_at_or_below_threshold);
        $baseAmount = $baseRate === null
            ? null
            : round($revenue['qualified_revenue'] * ($baseRate / 100), 2);

        $metrics = [
            'par' => $this->metricResult(
                'par',
                $parScore,
                $policy,
                $policy->configuration['par_score_mapping'] ?? null,
                $portfolio['par_percent'] === null
                    ? 'PAR cannot be scored because total outstanding principal is zero.'
                    : 'PAR risk is '.$portfolio['par_percent'].'%. The approved PAR performance-score conversion is required before awarding this bonus.'
            ),
            'follow_up' => $this->metricResult(
                'follow_up',
                $followUps['score'],
                $policy,
                null,
                $followUps['score'] === null
                    ? 'No active assigned loans were available for follow-up scoring.'
                    : 'Follow-up score is completed assigned loans divided by active assigned loans.'
            ),
            'portfolio_growth' => $this->metricResult(
                'portfolio_growth',
                $growth['score'],
                $policy,
                null,
                $growth['score'] === null
                    ? 'A weekly growth target or growth score mapping is not configured.'
                    : 'Growth score is calculated from distinct active-client change against the configured target.'
            ),
            'budget_performance' => $this->metricResult(
                'budget_performance',
                $budget['score'],
                $policy,
                $policy->configuration['budget_score_mapping'] ?? null,
                'Budget-performance score conversion is not configured.'
            ),
        ];

        $bonusMetrics = array_map(function (array $metric) use ($revenue): array {
            return [
                ...$metric,
                'bonus_amount' => round(
                    $revenue['qualified_revenue'] * ((float) $metric['earned_rate'] / 100),
                    2
                ),
            ];
        }, $metrics);

        $bonusRates = array_map(fn (array $metric): float => (float) $metric['earned_rate'], $bonusMetrics);
        $totalBonusRate = round(array_sum($bonusRates), 3);
        $bonusAmount = $baseAmount === null
            ? null
            : round(array_sum(array_map(
                fn (array $metric): float => (float) $metric['bonus_amount'],
                $bonusMetrics
            )), 2);
        $grossRemuneration = $baseAmount === null || $bonusAmount === null
            ? null
            : round($baseAmount + $bonusAmount, 2);

        $outstandingDeficit = (float) StaffRemunerationCalculation::query()
            ->where('officer_id', $officerId)
            ->whereIn('status', [StaffRemunerationCalculation::STATUS_PAID, StaffRemunerationCalculation::STATUS_SETTLED])
            ->whereDate('period_end', '<', $start->toDateString())
            ->selectRaw('COALESCE(SUM(new_deficit_amount - deficit_recovery_amount), 0) AS balance')
            ->value('balance');
        $deductions = $this->payoutDeductions($grossRemuneration, $bonusAmount, $budget['budget_overrun'], max(0, $outstandingDeficit), $policy);
        $currentBonusOffset = $deductions['bonus_portion'];
        $newDeficit = $deductions['new_deficit'];
        $netRemuneration = $deductions['net'];
        $fundAllocations = $this->fundAllocations($policy, $revenue['qualified_revenue'], $baseRate, $baseAmount, $bonusAmount, $budget);
        if ($fundAllocations['enabled'] && (abs($fundAllocations['reconciliation_difference'] ?? 0) > .01 || abs($fundAllocations['rate_total'] - 100) > .00001)) {
            $issues[] = 'Revenue allocations do not reconcile; review policy allocation rates.';
        }

        if ($currentLoanScore === null) {
            $issues[] = 'No active loans with outstanding obligations were available for the current-loan score.';
        }

        if (count(array_filter($metrics, fn (array $metric): bool => ! $metric['is_resolved'])) > 0) {
            $issues[] = 'One or more independent bonus measures remain unresolved; this preview is not payable.';
        }

        return [
            'officer_id' => $officerId,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'policy_id' => $policy->id,
            'policy_version' => $policy->version,
            'policy_settings' => [
                'par_overdue_days' => (int) $policy->par_overdue_days,
                'par_bonus_maximum_rate' => (float) $policy->par_bonus_maximum_rate,
                'base_current_loan_threshold' => (float) $policy->base_current_loan_threshold,
            ],
            'qualified_revenue' => $revenue,
            'portfolio' => $portfolio,
            'follow_ups' => $followUps,
            'growth' => $growth,
            'budget' => $budget,
            'current_loan_score' => $currentLoanScore,
            'base_rate' => $baseRate,
            'base_amount' => $baseAmount,
            'metrics' => $bonusMetrics,
            'total_bonus_rate' => $totalBonusRate,
            'bonus_amount' => $bonusAmount,
            'gross_remuneration' => $grossRemuneration,
            'current_bonus_offset' => $currentBonusOffset,
            'new_deficit_amount' => $newDeficit,
            'outstanding_deficit' => max(0, $outstandingDeficit),
            'gross_overrun_deduction' => $deductions['gross_offset'],
            'deficit_recovery_amount' => $deductions['recovery'],
            'closing_deficit' => $deductions['closing_deficit'],
            'fund_allocations' => $fundAllocations,
            'net_remuneration' => $netRemuneration,
            'ready_for_payment' => $baseAmount !== null
                && $grossRemuneration !== null
                && $netRemuneration !== null
                && $issues === [],
            'issues' => array_values(array_unique($issues)),
        ];
    }

    /**
     * Persist one reviewed preview as the calculation input/output snapshot
     * used by the later approval and payment workflow.
     */
    public function persistPreview(
        array $preview,
        StaffRemunerationPolicy $policy,
        ?int $reviewedBy = null
    ): StaffRemunerationCalculation {
        return DB::transaction(function () use ($preview, $policy, $reviewedBy): StaffRemunerationCalculation {
            if ((int) $preview['policy_id'] !== (int) $policy->id || $preview['policy_version'] !== $policy->version) {
                throw new InvalidArgumentException('The preview and policy version do not match.');
            }
            // Lock an existing parent row, including when the weekly row is new.
            User::whereKey((int) $preview['officer_id'])->lockForUpdate()->firstOrFail();
            $calculation = StaffRemunerationCalculation::forOfficerPeriod((int) $preview['officer_id'], $preview['period_start'], $preview['period_end'])
                ->lockForUpdate()
                ->first();

            if ($calculation && ! $calculation->isReviewable()) {
                throw new InvalidArgumentException(
                    'This remuneration calculation has already entered the approval or payment workflow and cannot be overwritten.'
                );
            }

            $metrics = collect($preview['metrics'] ?? [])->values()->all();
            $policySnapshot = [
                'id' => $policy->id,
                'policy_code' => $policy->policy_code,
                'version' => $policy->version,
                'title' => $policy->title,
                'status' => $policy->status,
                'effective_from' => $policy->effective_from?->toDateString(),
                'effective_to' => $policy->effective_to?->toDateString(),
                'base_current_loan_threshold' => (float) $policy->base_current_loan_threshold,
                'base_rate_above_threshold' => (float) $policy->base_rate_above_threshold,
                'base_rate_at_or_below_threshold' => (float) $policy->base_rate_at_or_below_threshold,
                'budget_allocation_rate' => (float) $policy->budget_allocation_rate,
                'par_overdue_days' => (int) $policy->par_overdue_days,
                'par_bonus_maximum_rate' => (float) $policy->par_bonus_maximum_rate,
                'rounding_scale' => (int) $policy->rounding_scale,
                'timezone' => $policy->timezone,
                'configuration' => $policy->configuration,
                'bonus_bands' => $policy->bonusBands
                    ->map(fn ($band): array => [
                        'metric_code' => $band->metric_code,
                        'lower_score' => $band->lower_score,
                        'lower_inclusive' => (bool) $band->lower_inclusive,
                        'upper_score' => $band->upper_score,
                        'upper_inclusive' => (bool) $band->upper_inclusive,
                        'earned_rate' => $band->earned_rate,
                        'maximum_rate' => $band->maximum_rate,
                        'sort_order' => $band->sort_order,
                    ])->values()->all(),
            ];

            $attributes = [
                'policy_id' => $policy->id,
                'policy_version' => $policy->version,
                'officer_id' => (int) $preview['officer_id'],
                'period_start' => $preview['period_start'],
                'period_end' => $preview['period_end'],
                'status' => StaffRemunerationCalculation::STATUS_REVIEW,
                'interest_revenue' => data_get($preview, 'qualified_revenue.interest_revenue', 0),
                'administration_fee_revenue' => data_get($preview, 'qualified_revenue.administration_fee_revenue', 0),
                'registration_fee_revenue' => data_get($preview, 'qualified_revenue.registration_fee_revenue', 0),
                'late_fee_revenue' => data_get($preview, 'qualified_revenue.late_fee_revenue', 0)
                    + data_get($preview, 'qualified_revenue.late_fee_from_repayments', 0),
                'restructuring_fee_revenue' => data_get($preview, 'qualified_revenue.restructuring_fee_revenue', 0),
                'other_qualified_revenue' => data_get($preview, 'qualified_revenue.other_qualified_revenue', 0),
                'qualified_revenue' => data_get($preview, 'qualified_revenue.qualified_revenue', 0),
                'total_active_loans' => data_get($preview, 'portfolio.total_active_loans', 0),
                'current_active_loans' => data_get($preview, 'portfolio.current_active_loans', 0),
                'current_loan_score' => $preview['current_loan_score'],
                'base_rate' => $preview['base_rate'],
                'base_amount' => $preview['base_amount'],
                'par_risk_principal' => data_get($preview, 'portfolio.par_risk_principal', 0),
                'par_total_outstanding_principal' => data_get($preview, 'portfolio.par_total_outstanding_principal', 0),
                'par_overdue_days' => data_get($preview, 'policy_settings.par_overdue_days'),
                'par_bonus_maximum_rate' => data_get($preview, 'policy_settings.par_bonus_maximum_rate'),
                'par_percent' => data_get($preview, 'portfolio.par_percent'),
                'par_score' => collect($metrics)->firstWhere('metric_code', 'par')['score'] ?? null,
                'follow_up_due_count' => data_get($preview, 'follow_ups.due_count', 0),
                'follow_up_completed_count' => data_get($preview, 'follow_ups.completed_count', 0),
                'follow_up_score' => data_get($preview, 'follow_ups.score'),
                'opening_active_clients' => data_get($preview, 'growth.opening_active_clients', 0),
                'closing_active_clients' => data_get($preview, 'growth.closing_active_clients', 0),
                'growth_target' => data_get($preview, 'growth.target'),
                'growth_score' => data_get($preview, 'growth.score'),
                'previous_week_qualified_revenue' => data_get($preview, 'budget.previous_week_qualified_revenue', 0),
                'allocated_budget' => data_get($preview, 'budget.allocated_budget', 0),
                'approved_budget_expenses' => data_get($preview, 'budget.approved_expenses', 0),
                'budget_overrun' => data_get($preview, 'budget.budget_overrun', 0),
                'budget_score' => data_get($preview, 'budget.score'),
                'budget_bonus_rate' => $this->metricRate($metrics, 'budget_performance'),
                'par_bonus_rate' => $this->metricRate($metrics, 'par'),
                'follow_up_bonus_rate' => $this->metricRate($metrics, 'follow_up'),
                'growth_bonus_rate' => $this->metricRate($metrics, 'portfolio_growth'),
                'total_bonus_rate' => data_get($preview, 'total_bonus_rate', 0),
                'bonus_amount' => $preview['bonus_amount'],
                'gross_remuneration' => $preview['gross_remuneration'],
                'current_bonus_offset' => $preview['current_bonus_offset'],
                'deficit_recovery_amount' => $preview['deficit_recovery_amount'] ?? 0,
                'net_remuneration' => $preview['net_remuneration'],
                'new_deficit_amount' => $preview['new_deficit_amount'],
                'policy_snapshot' => $policySnapshot,
                'qualified_revenue_snapshot' => $preview['qualified_revenue'],
                'input_snapshot' => [
                    'payout_history' => data_get($calculation?->input_snapshot, 'payout_history', []),
                    'portfolio' => $preview['portfolio'],
                    'follow_ups' => $preview['follow_ups'],
                    'growth' => $preview['growth'],
                    'issues' => $preview['issues'],
                    'ready_for_payment' => (bool) $preview['ready_for_payment'],
                    'preview' => $preview,
                    'fund_allocations' => $preview['fund_allocations'] ?? [],
                ],
                'bonus_breakdown' => $metrics,
                'budget_snapshot' => [
                    ...$preview['budget'],
                    'report' => data_get($calculation?->budget_snapshot, 'report'),
                ],
                'deficit_snapshot' => [
                    'current_bonus_offset' => $preview['current_bonus_offset'],
                    'deficit_recovery_amount' => $preview['deficit_recovery_amount'] ?? 0,
                    'new_deficit_amount' => $preview['new_deficit_amount'],
                    'recovery_rule' => data_get($policy->configuration, 'uncovered_deficit_recovery'),
                    'opening_balance' => $preview['outstanding_deficit'] ?? 0,
                    'gross_overrun_deduction' => $preview['gross_overrun_deduction'] ?? null,
                    'closing_balance' => $preview['closing_deficit'] ?? null,
                ],
                'calculation_notes' => $preview['issues'] === []
                    ? 'Calculation saved for administrative review.'
                    : implode("\n", $preview['issues']),
                'generated_by' => $calculation?->generated_by ?? $reviewedBy,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
                'audit_history' => [
                    ...($calculation?->audit_history ?? []),
                    [
                        'event' => $calculation ? 'review_updated' : 'review_created',
                        'by' => $reviewedBy,
                        'at' => now()->toIso8601String(),
                        'previous_input' => $calculation?->input_snapshot,
                        'previous_policy_snapshot' => $calculation?->policy_snapshot,
                    ],
                ],
            ];

            if (! $calculation) {
                $calculation = new StaffRemunerationCalculation;
            }

            $calculation->fill($attributes);
            $calculation->save();

            return $calculation;
        });
    }

    public function parPerformanceScore(?float $risk, mixed $mapping): ?float
    {
        if ($risk === null || $risk < 0 || $risk > 100 || ! is_array($mapping) || $mapping === []) {
            return null;
        }
        $previous = -1.0;
        $result = null;
        foreach (array_values($mapping) as $index => $band) {
            if (! is_array($band) || ! is_numeric($band['score'] ?? null)) {
                return null;
            }
            $score = (float) $band['score'];
            $upper = $band['upper_risk_percent'] ?? null;
            if ($score < 0 || $score > 100 || ($upper === null && $index !== count($mapping) - 1)
                || ($upper !== null && (! is_numeric($upper) || (float) $upper <= $previous || (float) $upper > 100))) {
                return null;
            }
            if ($result === null && ($upper === null || $risk <= (float) $upper)) {
                $result = $score;
            }
            $previous = $upper === null ? 100 : (float) $upper;
        }

        return ($mapping[array_key_last($mapping)]['upper_risk_percent'] ?? null) === null ? $result : null;
    }

    public function payoutDeductions(?float $gross, ?float $bonus, float $overrun, float $opening, StaffRemunerationPolicy $policy): array
    {
        if ($gross === null || $bonus === null) {
            return array_fill_keys(['gross_offset', 'bonus_portion', 'new_deficit', 'recovery', 'net', 'closing_deficit'], null);
        }
        $capacity = data_get($policy->configuration, 'budget_overrun_offset') === 'gross_remuneration' ? $gross : $bonus;
        $offset = round(min(max(0, $overrun), $capacity), 2);
        $newDeficit = round(max(0, $overrun - $offset), 2);
        $available = round(max(0, $gross - $offset), 2);
        $recovery = round(min(max(0, $opening), $available), 2);

        return [
            'gross_offset' => $offset,
            'bonus_portion' => min($bonus, $offset),
            'new_deficit' => $newDeficit,
            'recovery' => $recovery,
            'net' => round($available - $recovery, 2),
            'closing_deficit' => round(max(0, $opening - $recovery) + $newDeficit, 2),
        ];
    }

    public function fundAllocations(StaffRemunerationPolicy $policy, float $revenue, ?float $baseRate, ?float $base, ?float $bonus, array $budget): array
    {
        $config = data_get($policy->configuration, 'fund_allocation', []);
        if (! ($config['enabled'] ?? false)) {
            return ['enabled' => false];
        }
        $rates = [
            'management' => (float) $policy->budget_allocation_rate,
            'revolving' => (float) ($config['revolving_rate'] ?? 45),
            'growth' => (float) ($config['growth_rate'] ?? 19),
            'bonus_budget' => (float) ($config['bonus_budget_rate'] ?? 14),
            'standard_base' => (float) $policy->base_rate_above_threshold,
        ];
        $workingBudget = (float) ($budget['allocated_budget'] ?? 0);
        $spending = (float) ($budget['approved_expenses'] ?? 0);
        $received = $budget['received_amount'] ?? null;
        $result = [
            'enabled' => true,
            'policy_version' => $policy->version,
            'rates' => $rates,
            'management_allocation' => round($revenue * $rates['management'] / 100, 2),
            'management_allocation_purpose' => 'Next week operating budget; current spending uses the previous week allocation.',
            'revolving_fund' => round($revenue * $rates['revolving'] / 100, 2),
            'growth_baseline' => round($revenue * $rates['growth'] / 100, 2),
            'bonus_budget' => round($revenue * $rates['bonus_budget'] / 100, 2),
            'unused_authorized_budget' => round(max(0, $workingBudget - $spending), 2),
            // Only money actually received and left unused is available to transfer.
            'operations_reserve' => $received === null ? null : round(max(0, (float) $received - $spending), 2),
            'base_pay_difference' => $baseRate === null ? null : round($revenue * max(0, $rates['standard_base'] - $baseRate) / 100, 2),
            'accounts' => $config['accounts'] ?? [],
            'rate_total' => array_sum($rates),
            'reconciliation_difference' => null,
        ];
        if ($base === null || $bonus === null) {
            return $result;
        }
        $reduced = $baseRate < $rates['standard_base'];
        $unearned = round(max(0, $result['bonus_budget'] - $bonus), 2);
        $result['unearned_bonus'] = $unearned;
        $result['unearned_bonus_to_growth'] = $reduced ? $unearned : 0.0;
        $result['unallocated_bonus_held'] = $reduced ? 0.0 : $unearned;
        $result['growth_total'] = round($result['growth_baseline'] + $result['base_pay_difference'] + $result['unearned_bonus_to_growth'], 2);
        $total = $result['management_allocation'] + $result['revolving_fund'] + $result['growth_total']
            + $result['unallocated_bonus_held'] + $base + $bonus;
        $difference = round($revenue - $total, 2);
        if (abs($difference) <= .03 && abs(array_sum($rates) - 100) < .00001
            && round($result['unallocated_bonus_held'] + $difference, 2) >= 0) {
            $result['rounding_adjustment'] = $difference;
            $result['unallocated_bonus_held'] = round($result['unallocated_bonus_held'] + $difference, 2);
            $total += $difference;
        }
        $result['total_current_revenue_allocated'] = round($total, 2);
        $result['reconciliation_difference'] = round($revenue - $total, 2);

        return $result;
    }

    private function metricRate(array $metrics, string $metricCode): float
    {
        return (float) (collect($metrics)->firstWhere('metric_code', $metricCode)['earned_rate'] ?? 0);
    }

    private function qualifiedRevenue(int $officerId, Carbon $start, Carbon $end, array &$issues): array
    {
        $repayments = $this->confirmedRepaymentQuery($officerId)
            ->leftJoin('loan_schedules as ls', 'ls.id', '=', 'r.schedule_id')
            ->whereBetween('r.date_created', [$start, $end])
            ->orderBy('r.schedule_id')
            ->orderBy('r.date_created')
            ->orderBy('r.id')
            ->get([
                'r.id',
                'r.schedule_id',
                'ls.id as matched_schedule_id',
                'r.amount',
                'ls.interest',
                'ls.principal',
            ]);

        $scheduleIds = $repayments->pluck('schedule_id')->filter()->unique()->values();
        $paidBefore = $scheduleIds->isEmpty()
            ? collect()
            : $this->confirmedRepaymentQuery($officerId)
                ->whereIn('r.schedule_id', $scheduleIds)
                ->where('r.date_created', '<', $start)
                ->groupBy('r.schedule_id')
                ->selectRaw('r.schedule_id, SUM(r.amount) as total')
                ->pluck('total', 'schedule_id');

        $runningPaid = $paidBefore->map(fn ($amount): float => (float) $amount)->all();
        $interest = 0.0;
        $principal = 0.0;
        $lateFees = 0.0;

        foreach ($repayments as $repayment) {
            if (! $repayment->matched_schedule_id) {
                $issues[] = 'Repayment #'.$repayment->id.' has no matching schedule; its revenue allocation requires reconciliation.';

                continue;
            }
            $scheduleId = (int) ($repayment->schedule_id ?? 0);
            $paidToDate = (float) ($runningPaid[$scheduleId] ?? 0);
            $interestDue = (float) ($repayment->interest ?? 0);
            $principalDue = (float) ($repayment->principal ?? 0);
            $remaining = (float) $repayment->amount;

            $interestBefore = min($paidToDate, $interestDue);
            $principalBefore = min(max($paidToDate - $interestDue, 0), $principalDue);
            $interestPart = min($remaining, max($interestDue - $interestBefore, 0));
            $remaining -= $interestPart;
            $principalPart = min($remaining, max($principalDue - $principalBefore, 0));
            $remaining -= $principalPart;

            $interest += $interestPart;
            $principal += $principalPart;
            $lateFees += max($remaining, 0);

            if ($scheduleId > 0) {
                $runningPaid[$scheduleId] = $paidToDate + (float) $repayment->amount;
            }
        }

        $feeTotals = [
            'administration_fee_revenue' => 0.0,
            'registration_fee_revenue' => 0.0,
            'late_fee_revenue' => 0.0,
            'restructuring_fee_revenue' => 0.0,
            'other_qualified_revenue' => 0.0,
            'excluded_fee_revenue' => 0.0,
        ];

        $fees = DB::table('fees as f')
            ->leftJoin('personal_loans as pl', 'pl.id', '=', 'f.loan_id')
            ->leftJoin('fees_types as ft', 'ft.id', '=', 'f.fees_type_id')
            ->whereRaw('COALESCE(pl.assigned_to, f.added_by) = ?', [$officerId])
            ->where('f.status', 1)
            ->whereBetween('f.datecreated', [$start, $end])
            ->whereRaw('CAST(f.amount AS DECIMAL(15,2)) > 0')
            ->get([
                'f.id',
                'f.amount',
                'f.description',
                'ft.name as fee_type',
            ]);

        foreach ($fees as $fee) {
            $amount = (float) $fee->amount;
            $label = strtolower(trim(($fee->fee_type ?? '').' '.($fee->description ?? '')));

            if ($this->containsAny($label, ['insurance', 'security', 'savings', 'deposit'])) {
                $feeTotals['excluded_fee_revenue'] += $amount;
            } elseif ($this->containsAny($label, ['admin', 'administration'])) {
                $feeTotals['administration_fee_revenue'] += $amount;
            } elseif ($this->containsAny($label, ['registration', 'registration fees'])) {
                $feeTotals['registration_fee_revenue'] += $amount;
            } elseif ($this->containsAny($label, ['late'])) {
                $feeTotals['late_fee_revenue'] += $amount;
            } elseif ($this->containsAny($label, ['restructur', 'reschedul'])) {
                $feeTotals['restructuring_fee_revenue'] += $amount;
            } else {
                $feeTotals['other_qualified_revenue'] += $amount;
            }
        }

        $feeQualified = array_sum([
            $feeTotals['administration_fee_revenue'],
            $feeTotals['registration_fee_revenue'],
            $feeTotals['late_fee_revenue'],
            $feeTotals['restructuring_fee_revenue'],
            $feeTotals['other_qualified_revenue'],
        ]);

        return [
            'interest_revenue' => round($interest, 2),
            'principal_collected' => round($principal, 2),
            'late_fee_from_repayments' => round($lateFees, 2),
            ...array_map(fn (float $value): float => round($value, 2), $feeTotals),
            'qualified_revenue' => round($interest + $lateFees + $feeQualified, 2),
            'repayment_count' => $repayments->count(),
            'fee_count' => $fees->count(),
            'repayment_ids' => $repayments->pluck('id')->all(),
            'fee_ids' => $fees->pluck('id')->all(),
        ];
    }

    private function portfolioMetrics(
        int $officerId,
        StaffRemunerationPolicy $policy,
        Carbon $start,
        Carbon $end,
        array &$issues
    ): array {
        $loans = DB::table('personal_loans')
            ->where('assigned_to', $officerId)
            ->whereIn('status', [2, 3])
            ->whereDate('datecreated', '<=', $end->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('date_closed')
                    ->orWhere('date_closed', '>', $end->toDateString());
            })
            ->get(['id', 'member_id', 'status', 'datecreated', 'date_closed']);

        if ($loans->isEmpty()) {
            return [
                'total_active_loans' => 0,
                'current_active_loans' => 0,
                'par_risk_principal' => 0.0,
                'par_total_outstanding_principal' => 0.0,
                'par_percent' => null,
                'par_score' => null,
                'active_client_ids' => [],
                'active_loan_ids' => [],
            ];
        }

        $loanIds = $loans->pluck('id')->all();
        $schedules = DB::table('loan_schedules')
            ->whereIn('loan_id', $loanIds)
            ->get(['id', 'loan_id', 'payment_date', 'payment', 'interest', 'principal', 'paid', 'status']);
        $schedulesByLoan = $schedules->groupBy('loan_id');
        $scheduleIds = $schedules->pluck('id')->all();
        $paidBySchedule = $scheduleIds === []
            ? collect()
            : $this->confirmedRepaymentQuery($officerId)
                ->whereIn('r.schedule_id', $scheduleIds)
                ->where('r.date_created', '<=', $end)
                ->groupBy('r.schedule_id')
                ->selectRaw('r.schedule_id, SUM(r.amount) as total')
                ->pluck('total', 'schedule_id');

        $totalActive = 0;
        $currentActive = 0;
        $parRiskPrincipal = 0.0;
        $totalOutstandingPrincipal = 0.0;
        $activeClientIds = [];
        $activeLoanIds = [];
        $threshold = max(1, (int) $policy->par_overdue_days);

        foreach ($loans as $loan) {
            $loanSchedules = $schedulesByLoan->get($loan->id, collect());
            if ($loanSchedules->isEmpty()) {
                $issues[] = 'Loan #'.$loan->id.' has no repayment schedule and was excluded from active-loan scoring.';

                continue;
            }

            $outstanding = 0.0;
            $outstandingPrincipal = 0.0;
            $hasUnpaidDue = false;
            $isAtRisk = false;
            $oldestUnpaidDueDate = null;

            foreach ($loanSchedules as $schedule) {
                $payment = (float) $schedule->payment;
                // The schedule's cached paid amount includes payments after the
                // selected week. Only confirmed transactions as of this cutoff count.
                $paid = (float) ($paidBySchedule[$schedule->id] ?? 0);
                $remaining = max($payment - $paid, 0);
                if ($remaining <= 0) {
                    continue;
                }

                $outstanding += $remaining;
                $interestDue = (float) ($schedule->interest ?? 0);
                $principalDue = (float) ($schedule->principal ?? 0);
                $interestPaid = min($paid, $interestDue);
                $principalPaid = min(max($paid - $interestDue, 0), $principalDue);
                $outstandingPrincipal += max($principalDue - $principalPaid, 0);

                $dueDate = $this->legacyDate($schedule->payment_date);
                if (! $dueDate) {
                    $issues[] = 'Schedule #'.$schedule->id.' has an unreadable payment date.';

                    continue;
                }

                if ($dueDate->lte($end)) {
                    $hasUnpaidDue = true;
                    if ($oldestUnpaidDueDate === null || $dueDate->lt($oldestUnpaidDueDate)) {
                        $oldestUnpaidDueDate = $dueDate->copy()->startOfDay();
                    }
                }
            }

            if ($outstanding <= 0) {
                continue;
            }

            $totalActive++;
            $activeLoanIds[] = (int) $loan->id;
            $activeClientIds[] = (int) $loan->member_id;
            $totalOutstandingPrincipal += $outstandingPrincipal;

            if ($oldestUnpaidDueDate !== null
                && $oldestUnpaidDueDate->diffInDays($end->copy()->startOfDay()) > $threshold) {
                $isAtRisk = true;
            }

            if (! $hasUnpaidDue) {
                $currentActive++;
            }

            if ($isAtRisk) {
                $parRiskPrincipal += $outstandingPrincipal;
            }
        }

        return [
            'total_active_loans' => $totalActive,
            'current_active_loans' => $currentActive,
            'par_risk_principal' => round($parRiskPrincipal, 2),
            'par_total_outstanding_principal' => round($totalOutstandingPrincipal, 2),
            'par_overdue_days' => $threshold,
            'par_percent' => $totalOutstandingPrincipal > 0
                ? ($parRiskPrincipal / $totalOutstandingPrincipal) * 100
                : null,
            'par_score' => null,
            'active_client_ids' => array_values(array_unique($activeClientIds)),
            'active_loan_ids' => array_values(array_unique($activeLoanIds)),
        ];
    }

    private function followUpMetrics(int $officerId, Carbon $start, Carbon $end, array $activeLoanIds): array
    {
        $dueCount = count($activeLoanIds);
        $completed = DB::table('loan_follow_ups')
            ->where('assigned_to', $officerId)
            ->where('loan_type', 'personal')
            ->whereIn('loan_id', $activeLoanIds ?: [0])
            ->whereBetween('follow_up_at', [$start, $end])
            ->distinct('loan_id')
            ->count('loan_id');

        return [
            'due_count' => $dueCount,
            'completed_count' => $completed,
            'score' => $dueCount > 0
                ? ($completed / $dueCount) * 100
                : null,
        ];
    }

    private function growthMetrics(
        int $officerId,
        Carbon $start,
        Carbon $end,
        StaffRemunerationPolicy $policy,
        array &$issues
    ): array {
        $opening = $this->activeClientIdsAt($officerId, $start->copy()->subDay(), $issues);
        $closing = $this->activeClientIdsAt($officerId, $end, $issues);
        $target = data_get($policy->configuration, 'weekly_growth_target');
        $delta = count($closing) - count($opening);
        $score = is_numeric($target) && (float) $target > 0
            ? max(0, min(100, ($delta / (float) $target) * 100))
            : null;

        return [
            'opening_active_clients' => count($opening),
            'closing_active_clients' => count($closing),
            'net_change' => $delta,
            'target' => is_numeric($target) ? (float) $target : null,
            'score' => $score,
        ];
    }

    private function budgetMetrics(
        int $officerId,
        StaffRemunerationPolicy $policy,
        Carbon $start,
        Carbon $end,
        ?float $manualBudget,
        array &$issues
    ): array {
        $saved = StaffRemunerationCalculation::forOfficerPeriod($officerId, $start, $end)->first();
        $report = data_get($saved?->budget_snapshot, 'report');
        if ($manualBudget === null && data_get($saved?->budget_snapshot, 'allocation_source') === 'manual') {
            $manualBudget = (float) data_get($saved->budget_snapshot, 'allocated_budget');
        }
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subSecond();
        $previousRevenue = $this->qualifiedRevenue($officerId, $previousStart, $previousEnd, $issues)['qualified_revenue'];

        $allocated = $manualBudget !== null
            ? max(0, $manualBudget)
            : round($previousRevenue * ((float) $policy->budget_allocation_rate / 100), 2);
        $source = $manualBudget !== null
            ? 'manual'
            : ($previousRevenue > 0 ? 'previous_week_revenue' : 'manual_required');

        if ($manualBudget === null && $previousRevenue <= 0) {
            $issues[] = 'No previous-week qualified revenue was found; a manual first-week or zero-revenue budget is required.';
        }

        $expenseQuery = DB::table('expenditures')
            ->where('assigned_user_id', $officerId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'operational');
        $expenses = (clone $expenseQuery)->whereIn('status', ['approved', 'payment_pending', 'payment_failed', 'paid'])->get(['id', 'amount']);
        $approvedExpenses = (float) $expenses->unique('id')->sum('amount');
        $pendingExpenses = (clone $expenseQuery)->where('status', 'pending')->count();
        if ($pendingExpenses > 0) {
            $issues[] = 'Operating expenses are still awaiting approval for this period.';
        }
        $reportApproved = data_get($report, 'status') === 'approved';
        $received = null;
        if ($reportApproved) {
            $reportedIds = collect($report['items'])->pluck('expenditure_id')->filter()->map(fn ($id) => (int) $id);
            if ($expenses->pluck('id')->diff($reportedIds)->isNotEmpty()) {
                $issues[] = 'Approved operating expenses are missing from the weekly report; return it for correction.';
            }
            foreach ($report['items'] as $item) {
                if (! empty($item['expenditure_id'])) {
                    $matching = $expenses->firstWhere('id', (int) $item['expenditure_id']);
                    if (! $matching || abs((float) $matching->amount - (float) $item['amount']) > .001) {
                        $issues[] = 'An expense linked to the approved report changed; administrative review is required.';
                    }
                }
            }
            $approvedExpenses = round(collect($report['items'])->sum('amount'), 2);
            $received = (float) $report['received_amount'];
        } elseif (data_get($policy->configuration, 'weekly_expense_report_required', false)) {
            $issues[] = 'The officer weekly spending report must be submitted and approved.';
        }
        $score = null;
        if ($allocated > 0 && $approvedExpenses > 0
            && data_get($policy->configuration, 'operating_budget_score_method') === 'inverse_utilization_capped') {
            $score = min(100, ($allocated / $approvedExpenses) * 100);
        } elseif ($allocated > 0 && $approvedExpenses <= 0 && $reportApproved
            && ! empty($report['activities_completed']) && ! empty($report['report_complete'])) {
            $score = 100.0;
        } elseif ($approvedExpenses <= 0) {
            $issues[] = 'Zero spending requires verification of completed activities and complete expense reporting.';
        }

        return [
            'previous_week_qualified_revenue' => round($previousRevenue, 2),
            'allocation_rate' => (float) $policy->budget_allocation_rate,
            'allocated_budget' => $allocated,
            'allocation_source' => $source,
            'previous_period_start' => $previousStart->toDateString(),
            'previous_period_end' => $previousEnd->toDateString(),
            'approved_expenses' => round($approvedExpenses, 2),
            'received_amount' => $received,
            'report_status' => data_get($report, 'status', 'not_submitted'),
            'report' => $report,
            'unspent_received' => $received === null ? null : round(max(0, $received - $approvedExpenses), 2),
            'spent_above_received' => $received === null ? null : round(max(0, $approvedExpenses - $received), 2),
            'expense_ids' => $expenses->pluck('id')->all(),
            'pending_expense_count' => $pendingExpenses,
            'utilization_percent' => $allocated > 0 ? ($approvedExpenses / $allocated) * 100 : null,
            'budget_overrun' => round(max($approvedExpenses - $allocated, 0), 2),
            'score' => $score,
        ];
    }

    private function metricResult(
        string $metricCode,
        ?float $score,
        StaffRemunerationPolicy $policy,
        mixed $mapping,
        string $unresolvedNote
    ): array {
        $mappingConfigured = is_array($mapping) && $mapping !== [];
        // Budget's score is already converted by its configured method.
        $mappingRequired = $metricCode === 'par';
        $isResolved = $score !== null && (! $mappingRequired || $mappingConfigured);
        $earnedRate = $isResolved ? $this->rateForScore($policy, $metricCode, $score) : 0.0;
        $maximumRate = $metricCode === 'par'
            ? (float) $policy->par_bonus_maximum_rate
            : 3.0;
        $notes = $score === null || ($mappingRequired && ! $mappingConfigured)
            ? $unresolvedNote
            : 'Score converted using the configured bonus bands.';

        return [
            'metric_code' => $metricCode,
            'score' => $score,
            'earned_rate' => $earnedRate,
            'maximum_rate' => $maximumRate,
            'is_resolved' => $isResolved,
            'notes' => $notes,
        ];
    }

    private function rateForScore(StaffRemunerationPolicy $policy, string $metricCode, float $score): float
    {
        $band = $policy->bonusBands
            ->where('metric_code', $metricCode)
            ->sortBy('sort_order')
            ->first(function ($band) use ($score) {
                $lowerMatches = $band->lower_score === null
                    || ($band->lower_inclusive ? $score >= (float) $band->lower_score : $score > (float) $band->lower_score);
                $upperMatches = $band->upper_score === null
                    || ($band->upper_inclusive ? $score <= (float) $band->upper_score : $score < (float) $band->upper_score);

                return $lowerMatches && $upperMatches;
            });

        return $band ? min((float) $band->maximum_rate, (float) $band->earned_rate) : 0.0;
    }

    private function activeClientIdsAt(int $officerId, Carbon $asOf, array &$issues): array
    {
        $loans = DB::table('personal_loans')
            ->where('assigned_to', $officerId)
            ->whereIn('status', [2, 3])
            ->whereDate('datecreated', '<=', $asOf->toDateString())
            ->where(function ($query) use ($asOf) {
                $query->whereNull('date_closed')->orWhere('date_closed', '>', $asOf);
            })
            ->get(['id', 'member_id']);

        if ($loans->isEmpty()) {
            return [];
        }

        $loanIds = $loans->pluck('id')->all();
        $schedules = DB::table('loan_schedules')->whereIn('loan_id', $loanIds)->get([
            'id', 'loan_id', 'payment', 'paid', 'interest', 'principal',
        ]);
        $scheduleIds = $schedules->pluck('id')->all();
        $paidBySchedule = $scheduleIds === []
            ? collect()
            : $this->confirmedRepaymentQuery($officerId)
                ->whereIn('r.schedule_id', $scheduleIds)
                ->where('r.date_created', '<=', $asOf)
                ->groupBy('r.schedule_id')
                ->selectRaw('r.schedule_id, SUM(r.amount) as total')
                ->pluck('total', 'schedule_id');

        $active = [];
        foreach ($loans as $loan) {
            $outstanding = $schedules
                ->where('loan_id', $loan->id)
                ->sum(function ($schedule) use ($paidBySchedule) {
                    return max(
                        (float) $schedule->payment - (float) ($paidBySchedule[$schedule->id] ?? 0),
                        0
                    );
                });

            if ($outstanding > 0) {
                $active[] = (int) $loan->member_id;
            }
        }

        return array_values(array_unique($active));
    }

    private function confirmedRepaymentQuery(int $officerId)
    {
        return DB::table('repayments as r')
            ->join('personal_loans as pl', 'pl.id', '=', 'r.loan_id')
            ->where('pl.assigned_to', $officerId)
            ->where('r.amount', '>', 0)
            ->whereNotIn('r.status', [-1, 2, 3])
            ->where(function ($query) {
                $query->where('r.status', 1)
                    ->orWhereIn('r.payment_status', ['Completed', 'Confirmed']);
            });
    }

    private function date(Carbon|string $value): Carbon
    {
        return $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
    }

    private function legacyDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        $value = trim((string) $value);
        foreach (['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y H:i:s', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat('!'.$format, $value);
            } catch (\Throwable) {
                // Try the next legacy date format.
            }
        }

        return null;
    }

    private function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function sumNullable(array $weeks, string $key): ?float
    {
        if ($weeks === [] || count(array_filter($weeks, fn (array $week): bool => $week[$key] === null)) > 0) {
            return null;
        }

        return round(array_sum(array_map(fn (array $week): float => (float) $week[$key], $weeks)), 2);
    }
}
