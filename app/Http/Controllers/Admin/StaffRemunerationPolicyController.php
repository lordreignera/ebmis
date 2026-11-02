<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRemunerationPolicy;
use App\Models\SystemAccount;
use App\Services\StaffRemuneration\WeeklyRemunerationWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffRemunerationPolicyController extends Controller
{
    public function index()
    {
        $policies = StaffRemunerationPolicy::with(['createdBy', 'approvedBy'])
            ->where('policy_code', StaffRemunerationPolicy::CODE)
            ->latest('id')
            ->paginate(20);

        return view('admin.staff-remuneration.policies.index', compact('policies'));
    }

    public function create(Request $request)
    {
        $source = $request->filled('from')
            ? StaffRemunerationPolicy::findOrFail((int) $request->input('from'))
            : null;

        return view('admin.staff-remuneration.policies.form', [
            'policy' => null,
            'sourcePolicy' => $source,
            'bands' => $this->bandFormData($source),
            'configuration' => $source?->configuration ?? [],
            'metrics' => StaffRemunerationPolicy::METRICS,
            'allocationAccounts' => SystemAccount::active()->whereIn('category', ['Income', 'Equity', 'Liability'])->where('is_cash_bank', false)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $policy = DB::transaction(function () use ($validated, $request) {
            $policy = StaffRemunerationPolicy::create([
                ...$this->policyAttributes($validated),
                'policy_code' => StaffRemunerationPolicy::CODE,
                'status' => 'draft',
                'created_by' => $request->user()?->id,
            ]);

            $this->saveBands($policy, $validated['bands'], (float) $validated['par_bonus_maximum_rate']);

            return $policy;
        });

        return redirect()
            ->route('admin.expenditures.remuneration.policies.show', $policy)
            ->with('success', 'Draft remuneration policy version created. It is not active or payable.');
    }

    public function show(StaffRemunerationPolicy $policy)
    {
        $this->ensurePolicy($policy);
        $policy->load(['createdBy', 'approvedBy']);

        return view('admin.staff-remuneration.policies.show', compact('policy'));
    }

    public function edit(StaffRemunerationPolicy $policy)
    {
        $this->ensurePolicy($policy);

        if ($policy->status !== 'draft') {
            return redirect()
                ->route('admin.expenditures.remuneration.policies.show', $policy)
                ->with('error', 'Only draft policies can be edited. Create a new version for an approved or active policy.');
        }

        return view('admin.staff-remuneration.policies.form', [
            'policy' => $policy,
            'sourcePolicy' => null,
            'bands' => $this->bandFormData($policy),
            'configuration' => $policy->configuration ?? [],
            'metrics' => StaffRemunerationPolicy::METRICS,
            'allocationAccounts' => SystemAccount::active()->whereIn('category', ['Income', 'Equity', 'Liability'])->where('is_cash_bank', false)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StaffRemunerationPolicy $policy)
    {
        $this->ensurePolicy($policy);

        if ($policy->status !== 'draft') {
            return back()->with('error', 'Only draft policies can be edited. Create a new version instead.');
        }

        if ($policy->calculations()->exists()) {
            return back()->with('error', 'This draft is already referenced by a calculation. Create a new version to preserve its audit history.');
        }

        $validated = $this->validated($request, $policy);

        DB::transaction(function () use ($policy, $validated) {
            $policy = $this->lockedPolicy($policy, 'draft', true);
            $policy->update($this->policyAttributes($validated));
            $this->saveBands($policy, $validated['bands'], (float) $validated['par_bonus_maximum_rate']);
        });

        return redirect()
            ->route('admin.expenditures.remuneration.policies.show', $policy)
            ->with('success', 'Draft remuneration policy updated.');
    }

    public function approve(StaffRemunerationPolicy $policy)
    {
        $this->ensurePolicy($policy);

        if ($policy->status !== 'draft') {
            return back()->with('error', 'Only draft policies can be approved.');
        }

        DB::transaction(function () use ($policy): void {
            $policy = $this->lockedPolicy($policy, 'draft');
            if (data_get($policy->configuration, 'fund_allocation.enabled')) {
                app(WeeklyRemunerationWorkflowService::class)->assertAllocationAccounts(data_get($policy->configuration, 'fund_allocation.accounts', []));
            }
            $policy->update(['status' => 'approved', 'approved_by' => request()->user()?->id, 'approved_at' => now()]);
        });

        return back()->with('success', 'Policy approved. It must still be activated before it can govern a payout.');
    }

    public function activate(StaffRemunerationPolicy $policy)
    {
        $this->ensurePolicy($policy);

        if ($policy->status !== 'approved') {
            return back()->with('error', 'Only approved policies can be activated.');
        }

        DB::transaction(function () use ($policy) {
            $policy = $this->lockedPolicy($policy, 'approved');
            if (data_get($policy->configuration, 'fund_allocation.enabled')) {
                app(WeeklyRemunerationWorkflowService::class)->assertAllocationAccounts(data_get($policy->configuration, 'fund_allocation.accounts', []));
            }
            StaffRemunerationPolicy::where('policy_code', StaffRemunerationPolicy::CODE)
                ->where('status', 'active')
                ->get()->each(fn ($previous) => $previous->update(['status' => 'retired', 'effective_to' => now()->toDateString()]));

            $policy->update([
                'status' => 'active',
                'effective_from' => $policy->effective_from ?: now()->toDateString(),
                'effective_to' => null,
            ]);
        });

        return back()->with('success', 'Policy activated. Previous active versions were retired without deleting history.');
    }

    public function destroy(StaffRemunerationPolicy $policy)
    {
        $this->ensurePolicy($policy);

        if ($policy->status === 'active') {
            return back()->with('error', 'The active policy cannot be retired until a replacement is activated.');
        }

        DB::transaction(function () use ($policy): void {
            $policy = $this->lockedPolicy($policy, $policy->status);
            if ($policy->status === 'active') {
                throw ValidationException::withMessages(['policy' => 'Activate a replacement before retiring the active policy.']);
            }
            $policy->update(['status' => 'retired', 'effective_to' => $policy->effective_to ?: now()->toDateString()]);
        });

        return redirect()
            ->route('admin.expenditures.remuneration.policies.index')
            ->with('success', 'Policy retired. No policy records were deleted.');
    }

    /** Serialize version transitions so concurrent activation cannot leave two active versions. */
    private function lockedPolicy(StaffRemunerationPolicy $policy, string $expectedStatus, bool $unused = false): StaffRemunerationPolicy
    {
        $locked = StaffRemunerationPolicy::where('policy_code', StaffRemunerationPolicy::CODE)->orderBy('id')->lockForUpdate()->get()->firstWhere('id', $policy->id);
        abort_unless($locked, 404);
        if ($locked->status !== $expectedStatus || ($unused && $locked->calculations()->exists())) {
            throw ValidationException::withMessages(['policy' => 'This policy changed or is already referenced. Refresh the page or create a new version.']);
        }

        return $locked;
    }

    private function validated(Request $request, ?StaffRemunerationPolicy $policy = null): array
    {
        if (trim((string) $request->input('weekly_growth_target', '')) === '') {
            $request->merge(['weekly_growth_target' => null]);
        }

        $validated = $request->validate([
            'version' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('staff_remuneration_policies', 'version')
                    ->where(fn ($query) => $query->where('policy_code', StaffRemunerationPolicy::CODE))
                    ->ignore($policy?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'base_current_loan_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'base_rate_above_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'base_rate_at_or_below_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'budget_allocation_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'operating_budget_score_method' => ['nullable', 'in:inverse_utilization_capped'],
            'par_overdue_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'par_bonus_maximum_rate' => ['required', 'numeric', 'min:0', 'max:3'],
            'rounding_scale' => ['required', 'integer', 'min:0', 'max:4'],
            'timezone' => ['required', 'timezone', 'max:64'],
            'weekly_growth_target' => ['nullable', 'integer', 'min:1'],
            'par_bands' => ['required', 'array', 'size:5'],
            'par_bands.*.upper_risk_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'par_bands.*.score' => ['required', 'numeric', 'min:0', 'max:100'],
            'fund_revolving_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'fund_growth_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'fund_bonus_budget_rate' => ['required', 'numeric', 'min:12', 'max:100'],
            'fund_accounts' => ['nullable', 'array'],
            'fund_accounts.*' => ['nullable', 'integer', 'exists:system_accounts,Id', 'distinct'],
            'bands' => ['required', 'array'],
            'bands.par' => ['required', 'array', 'size:5'],
            'bands.follow_up' => ['required', 'array', 'size:5'],
            'bands.portfolio_growth' => ['required', 'array', 'size:5'],
            'bands.budget_performance' => ['required', 'array', 'size:5'],
            'bands.*.*.lower_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bands.*.*.upper_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bands.*.*.lower_inclusive' => ['nullable', 'boolean'],
            'bands.*.*.upper_inclusive' => ['nullable', 'boolean'],
            'bands.*.*.earned_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->validateBandRanges($validated['bands'], (float) $validated['par_bonus_maximum_rate']);
        $previous = -1;
        foreach (array_values($validated['par_bands']) as $index => $band) {
            $upper = $band['upper_risk_percent'] ?? null;
            if (($index < 4 && ($upper === null || (float) $upper <= $previous)) || ($index === 4 && $upper !== null)) {
                throw ValidationException::withMessages(['par_bands' => 'PAR risk boundaries must increase, with the final upper boundary left empty for all higher risk values.']);
            }
            $previous = (float) $upper;
        }
        $allocationTotal = (float) $validated['budget_allocation_rate'] + (float) $validated['base_rate_above_threshold']
            + (float) $validated['fund_revolving_rate'] + (float) $validated['fund_growth_rate'] + (float) $validated['fund_bonus_budget_rate'];
        if (abs($allocationTotal - 100) > .00001) {
            throw ValidationException::withMessages(['fund_revolving_rate' => 'Management budget + standard base + revolving + growth + bonus budget must total 100%.']);
        }

        return $validated;
    }

    private function policyAttributes(array $validated): array
    {
        $configuration = [
            'payment_cycle' => 'weekly_calendar_period',
            'working_day_or_holiday_proration' => false,
            'par_threshold_basis' => 'calendar_days_after_oldest_unpaid_instalment',
            'follow_up_rule' => 'distinct_recorded_followups_on_active_assigned_loans_divided_by_active_assigned_loans',
            'bonus_metrics' => array_keys(StaffRemunerationPolicy::METRICS),
            'par_score_mapping' => array_map(fn ($band) => [
                'upper_risk_percent' => isset($band['upper_risk_percent']) ? (float) $band['upper_risk_percent'] : null,
                'score' => (float) $band['score'],
            ], array_values($validated['par_bands'])),
            'weekly_growth_target' => isset($validated['weekly_growth_target'])
                ? (float) $validated['weekly_growth_target']
                : null,
            'operating_budget_score_method' => $validated['operating_budget_score_method'] ?? null,
            'budget_overrun_offset' => 'gross_remuneration',
            'weekly_expense_report_required' => true,
            'uncovered_deficit_recovery' => 'subsequent_payouts',
            'first_week_budget_source' => 'manual_expense',
            'fund_allocation' => [
                'enabled' => true,
                'revolving_rate' => (float) $validated['fund_revolving_rate'],
                'growth_rate' => (float) $validated['fund_growth_rate'],
                'bonus_budget_rate' => (float) $validated['fund_bonus_budget_rate'],
                'accounts' => array_filter($validated['fund_accounts'] ?? [], fn ($id) => $id !== null),
            ],
        ];

        return [
            'version' => $validated['version'],
            'title' => $validated['title'],
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_to' => $validated['effective_to'] ?? null,
            'base_current_loan_threshold' => $validated['base_current_loan_threshold'],
            'base_rate_above_threshold' => $validated['base_rate_above_threshold'],
            'base_rate_at_or_below_threshold' => $validated['base_rate_at_or_below_threshold'],
            'budget_allocation_rate' => $validated['budget_allocation_rate'],
            'par_overdue_days' => $validated['par_overdue_days'],
            'par_bonus_maximum_rate' => $validated['par_bonus_maximum_rate'],
            'rounding_scale' => $validated['rounding_scale'],
            'timezone' => $validated['timezone'],
            'configuration' => $configuration,
        ];
    }

    private function saveBands(StaffRemunerationPolicy $policy, array $bands, float $parMaximumRate): void
    {
        $configuration = $policy->configuration ?? [];
        $configuration['bonus_bands'] = [];

        foreach (array_keys(StaffRemunerationPolicy::METRICS) as $metricCode) {
            foreach (array_values($bands[$metricCode]) as $sortOrder => $band) {
                $configuration['bonus_bands'][] = [
                    'metric_code' => $metricCode,
                    'lower_score' => $band['lower_score'] === '' ? null : ($band['lower_score'] ?? null),
                    'lower_inclusive' => (bool) ($band['lower_inclusive'] ?? false),
                    'upper_score' => $band['upper_score'] === '' ? null : ($band['upper_score'] ?? null),
                    'upper_inclusive' => (bool) ($band['upper_inclusive'] ?? false),
                    'earned_rate' => $band['earned_rate'],
                    'maximum_rate' => $metricCode === 'par' ? $parMaximumRate : 3,
                    'sort_order' => $sortOrder,
                    'notes' => $band['notes'] ?? null,
                ];
            }
        }

        $policy->update(['configuration' => $configuration]);
    }

    private function validateBandRanges(array $bands, float $parMaximumRate): void
    {
        $errors = [];

        foreach (array_keys(StaffRemunerationPolicy::METRICS) as $metricCode) {
            $rows = array_values($bands[$metricCode] ?? []);
            $previous = null;

            foreach ($rows as $index => $row) {
                $lower = ($row['lower_score'] ?? null) === '' ? null : ($row['lower_score'] ?? null);
                $upper = ($row['upper_score'] ?? null) === '' ? null : ($row['upper_score'] ?? null);
                $lower = $lower === null ? null : (float) $lower;
                $upper = $upper === null ? null : (float) $upper;
                $earnedRate = (float) ($row['earned_rate'] ?? 0);
                $maximumRate = $metricCode === 'par' ? $parMaximumRate : 3.0;

                if ($earnedRate > $maximumRate) {
                    $errors["bands.{$metricCode}.{$index}.earned_rate"] = "The earned rate cannot exceed {$maximumRate}% for this metric.";
                }

                if ($lower !== null && $upper !== null && $lower >= $upper) {
                    $errors["bands.{$metricCode}.{$index}.upper_score"] = 'The upper score must be greater than the lower score.';
                }

                if ($previous !== null) {
                    if ($previous['upper'] === null || $lower === null) {
                        $errors["bands.{$metricCode}.{$index}.lower_score"] = 'Bonus bands must form one continuous range.';
                    } elseif (abs($previous['upper'] - $lower) > 0.000001) {
                        $errors["bands.{$metricCode}.{$index}.lower_score"] = 'This band must begin where the previous band ends.';
                    } elseif ((bool) $previous['upper_inclusive'] === (bool) ($row['lower_inclusive'] ?? false)) {
                        $errors["bands.{$metricCode}.{$index}.lower_inclusive"] = 'Adjacent bands must include exactly one side of their shared boundary.';
                    }
                }

                $previous = [
                    'upper' => $upper,
                    'upper_inclusive' => (bool) ($row['upper_inclusive'] ?? false),
                ];
            }

            if (($rows[0]['lower_score'] ?? null) !== '' && ($rows[0]['lower_score'] ?? null) !== null) {
                $errors["bands.{$metricCode}.0.lower_score"] = 'The first band must have no lower bound.';
            }

            $lastIndex = count($rows) - 1;
            $lastUpper = $rows[$lastIndex]['upper_score'] ?? null;
            if ($lastUpper === '' || $lastUpper === null || abs((float) $lastUpper - 100) > 0.000001 || ! (bool) ($rows[$lastIndex]['upper_inclusive'] ?? false)) {
                $errors["bands.{$metricCode}.{$lastIndex}.upper_score"] = 'The final band must end at 100 and include 100.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function bandFormData(?StaffRemunerationPolicy $policy = null): array
    {
        $defaults = StaffRemunerationPolicy::DEFAULT_BONUS_BANDS;

        $data = [];
        foreach (array_keys(StaffRemunerationPolicy::METRICS) as $metricCode) {
            $existing = $policy?->bonusBands
                ?->where('metric_code', $metricCode)
                ->sortBy('sort_order')
                ->values() ?? collect();

            $data[$metricCode] = $existing->isNotEmpty()
                ? $existing->map(fn ($band) => [
                    'lower_score' => $band->lower_score,
                    'lower_inclusive' => $band->lower_inclusive,
                    'upper_score' => $band->upper_score,
                    'upper_inclusive' => $band->upper_inclusive,
                    'earned_rate' => $band->earned_rate,
                    'notes' => $band->notes,
                ])->all()
                : collect($defaults)->map(fn ($band) => [
                    'lower_score' => $band[0],
                    'lower_inclusive' => $band[1],
                    'upper_score' => $band[2],
                    'upper_inclusive' => $band[3],
                    'earned_rate' => $band[4],
                    'notes' => null,
                ])->all();
        }

        return $data;
    }

    private function ensurePolicy(StaffRemunerationPolicy $policy): void
    {
        abort_unless($policy->policy_code === StaffRemunerationPolicy::CODE, 404);
    }
}
