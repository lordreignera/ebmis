@extends('layouts.admin')

@section('title', $policy ? 'Edit Remuneration Policy' : 'New Remuneration Policy')

@section('content')
@php
    $value = function ($key, $default = '') use ($policy) {
        $setting = old($key, data_get($policy, $key, $default));
        return $setting instanceof \DateTimeInterface ? $setting->format('Y-m-d') : $setting;
    };
    $parBands = old('par_bands', data_get($configuration, 'par_score_mapping') ?: \App\Models\StaffRemunerationPolicy::DEFAULT_PAR_SCORES);
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="mb-1">{{ $policy ? 'Edit draft policy' : 'New remuneration policy version' }}</h4><p class="text-muted mb-0">Base rates default to 12% at or above 90% and 5% below 90%.</p></div>
        <a href="{{ route('admin.expenditures.remuneration.policies.index') }}" class="btn btn-outline-secondary">Back to policies</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $policy ? route('admin.expenditures.remuneration.policies.update', $policy) : route('admin.expenditures.remuneration.policies.store') }}">
        @csrf
        @if($policy) @method('PUT') @endif
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Policy identity and base pay</h5></div><div class="card-body"><div class="row g-3">
            <div class="col-md-3"><label class="form-label">Version</label><input class="form-control" name="version" value="{{ $value('version', $sourcePolicy?->version ? $sourcePolicy->version . '-copy' : now()->format('YmdHis') . '-draft') }}" required><small class="text-muted">Unique version identifier.</small></div>
            <div class="col-md-9"><label class="form-label">Policy title</label><input class="form-control" name="title" value="{{ $value('title', $sourcePolicy?->title ?: 'E-BIMS Weekly Staff Remuneration Policy') }}" required></div>
            <div class="col-md-3"><label class="form-label">Base threshold (%)</label><input class="form-control" type="number" step="0.001" name="base_current_loan_threshold" value="{{ $value('base_current_loan_threshold', $sourcePolicy?->base_current_loan_threshold ?: 90) }}" required></div>
            <div class="col-md-3"><label class="form-label">Rate at or above threshold (%)</label><input class="form-control" type="number" step="0.001" name="base_rate_above_threshold" value="{{ $value('base_rate_above_threshold', $sourcePolicy?->base_rate_above_threshold ?: 12) }}" required></div>
            <div class="col-md-3"><label class="form-label">Rate below threshold (%)</label><input class="form-control" type="number" step="0.001" name="base_rate_at_or_below_threshold" value="{{ $value('base_rate_at_or_below_threshold', $sourcePolicy?->base_rate_at_or_below_threshold ?: 5) }}" required></div>
            <div class="col-md-3"><label class="form-label">Budget allocation (%)</label><input class="form-control" type="number" step="0.001" name="budget_allocation_rate" value="{{ $value('budget_allocation_rate', $sourcePolicy?->budget_allocation_rate ?: 10) }}" required></div>
            <div class="col-md-3"><label class="form-label">Effective from</label><input class="form-control" type="date" name="effective_from" value="{{ $value('effective_from', optional($sourcePolicy?->effective_from)->format('Y-m-d')) }}"></div>
            <div class="col-md-3"><label class="form-label">Effective to</label><input class="form-control" type="date" name="effective_to" value="{{ $value('effective_to', optional($sourcePolicy?->effective_to)->format('Y-m-d')) }}"></div>
            <div class="col-md-3"><label class="form-label">Rounding scale</label><input class="form-control" type="number" name="rounding_scale" value="{{ $value('rounding_scale', $sourcePolicy?->rounding_scale ?: 2) }}" required></div>
            <div class="col-md-3"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="{{ $value('timezone', $sourcePolicy?->timezone ?: 'Africa/Kampala') }}" required></div>
        </div></div></div>

        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">PAR and four bonus bands</h5><small class="text-muted">These fields are editable by an Administrator or Super Administrator. Each metric has an independent maximum. Changes are saved to this policy version only.</small></div><div class="card-body">
            <div class="row g-3 mb-4"><div class="col-md-4"><label class="form-label">PAR overdue days</label><input class="form-control" type="number" min="1" name="par_overdue_days" value="{{ $value('par_overdue_days', $sourcePolicy?->par_overdue_days ?: 120) }}" required><small class="text-muted">Calendar days after the oldest unpaid instalment becomes overdue; applies to daily, weekly and monthly loans.</small></div><div class="col-md-4"><label class="form-label">PAR maximum bonus rate (%)</label><input class="form-control" type="number" min="0" max="3" step="0.001" name="par_bonus_maximum_rate" value="{{ $value('par_bonus_maximum_rate', $sourcePolicy?->par_bonus_maximum_rate ?: 3) }}" required><small class="text-muted">Default: 3% of qualified revenue.</small></div></div>
            @foreach($metrics as $metricCode => $metricLabel)
                <h6 class="mt-4">{{ $metricLabel }}</h6>
                <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Lower score</th><th>Lower inclusive</th><th>Upper score</th><th>Upper inclusive</th><th>Earned rate (%)</th><th>Notes</th></tr></thead><tbody>
                @foreach($bands[$metricCode] as $index => $band)
                    <tr><td><input class="form-control form-control-sm" type="number" step="0.001" name="bands[{{ $metricCode }}][{{ $index }}][lower_score]" value="{{ old("bands.$metricCode.$index.lower_score", $band['lower_score']) }}" placeholder="None"></td><td class="text-center"><input type="hidden" name="bands[{{ $metricCode }}][{{ $index }}][lower_inclusive]" value="0"><input type="checkbox" name="bands[{{ $metricCode }}][{{ $index }}][lower_inclusive]" value="1" @checked(old("bands.$metricCode.$index.lower_inclusive", $band['lower_inclusive']))></td><td><input class="form-control form-control-sm" type="number" step="0.001" name="bands[{{ $metricCode }}][{{ $index }}][upper_score]" value="{{ old("bands.$metricCode.$index.upper_score", $band['upper_score']) }}" placeholder="None"></td><td class="text-center"><input type="hidden" name="bands[{{ $metricCode }}][{{ $index }}][upper_inclusive]" value="0"><input type="checkbox" name="bands[{{ $metricCode }}][{{ $index }}][upper_inclusive]" value="1" @checked(old("bands.$metricCode.$index.upper_inclusive", $band['upper_inclusive']))></td><td><input class="form-control form-control-sm" type="number" step="0.001" min="0" max="{{ $metricCode === 'par' ? old('par_bonus_maximum_rate', data_get($policy, 'par_bonus_maximum_rate', data_get($sourcePolicy, 'par_bonus_maximum_rate', 3))) : 3 }}" name="bands[{{ $metricCode }}][{{ $index }}][earned_rate]" value="{{ old("bands.$metricCode.$index.earned_rate", $band['earned_rate']) }}" required></td><td><input class="form-control form-control-sm" name="bands[{{ $metricCode }}][{{ $index }}][notes]" value="{{ old("bands.$metricCode.$index.notes", $band['notes']) }}"></td></tr>
                @endforeach
                </tbody></table></div>
            @endforeach
        </div></div>

        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Approved scoring inputs</h5><small class="text-muted">PAR and budget performance require an approved conversion. Follow-up performance uses the fixed completed-recorded follow-up ratio.</small></div><div class="card-body"><div class="row g-3">
            <div class="col-lg-6"><label class="form-label">PAR risk-to-performance conversion</label><p class="small text-muted">Risk boundaries are inclusive upper limits, in percent. The last row covers risk above the previous boundary.</p><table class="table table-sm"><thead><tr><th>Upper risk (%)</th><th>Performance score</th></tr></thead><tbody>@foreach($parBands as $index => $band)<tr><td><input class="form-control" type="number" step="0.001" min="0" max="100" name="par_bands[{{ $index }}][upper_risk_percent]" value="{{ $band['upper_risk_percent'] ?? '' }}" @readonly($index === 4) placeholder="All higher risk"></td><td><input class="form-control" type="number" min="0" max="100" step="0.001" name="par_bands[{{ $index }}][score]" value="{{ $band['score'] ?? '' }}" required></td></tr>@endforeach</tbody></table></div>
            <div class="col-lg-6"><div class="alert alert-info mb-0"><strong>Follow-up score</strong><br>Distinct assigned loans with a recorded follow-up in the period ÷ active assigned loans × 100.</div></div>
            <div class="col-lg-6"><label class="form-label">Weekly growth target (net distinct active clients)</label><input class="form-control" type="number" step="1" min="1" name="weekly_growth_target" value="{{ old('weekly_growth_target', data_get($configuration, 'weekly_growth_target', 1)) }}"><small class="text-muted">Default 1: one net new active client scores 100%. Repeat loans and replacement of departing clients do not create net growth.</small></div>
            <div class="col-lg-6"><label class="form-label">Budget performance method</label><select class="form-select" name="operating_budget_score_method"><option value="">Awaiting policy approval</option><option value="inverse_utilization_capped" @selected(old('operating_budget_score_method', data_get($configuration, 'operating_budget_score_method')) === 'inverse_utilization_capped')>Budget / approved spending, capped at 100%</option></select><small class="text-muted">Zero-budget and zero-spending periods require review.</small></div>
        </div></div></div>

        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Revenue allocations and ledger accounts</h5></div><div class="card-body">
            <p class="text-muted">Management + standard base + Revolving Fund + Growth baseline + bonus budget must total 100%. Four bonuses earn at most 12%; the approved allocation budget remains 14%. Current overruns are deducted from gross remuneration. Uncovered deficits carry forward.</p>
            <div class="row g-3 mb-4">@foreach(['revolving' => ['Revolving Fund (%)', 45], 'growth' => ['Growth baseline (%)', 19], 'bonus_budget' => ['Bonus allocation budget (%)', 14]] as $key => [$label, $default])<div class="col-md-4"><label class="form-label">{{ $label }}</label><input class="form-control" type="number" step="0.001" min="0" max="100" name="fund_{{ $key }}_rate" value="{{ old('fund_'.$key.'_rate', data_get($configuration, 'fund_allocation.'.$key.'_rate', $default)) }}" required></div>@endforeach</div>
            <div class="row g-3">@foreach(['source' => 'Distributable revenue / earnings source', 'management' => 'Management budget holding account', 'revolving' => 'Revolving Fund holding account', 'growth' => 'Growth Revolving Fund holding account', 'operations_reserve' => 'Internal Operations Reserve holding account', 'unallocated' => 'Unallocated bonus holding account'] as $key => $label)<div class="col-lg-6"><label class="form-label">{{ $label }}</label><select class="form-select" name="fund_accounts[{{ $key }}]"><option value="">Select an account before posting</option>@foreach($allocationAccounts as $account)<option value="{{ $account->Id }}" @selected((string) old('fund_accounts.'.$key, data_get($configuration, 'fund_allocation.accounts.'.$key)) === (string) $account->Id)>{{ $account->full_name }} ({{ $account->category }})</option>@endforeach</select></div>@endforeach</div>
            <p class="small text-muted mt-3 mb-0">Use six distinct non-cash accounts. The source must be Income or Equity; holding accounts must be Liability or Equity. Approval does not send cash or authorize reserve withdrawals.</p>
        </div></div>
        <div class="d-flex justify-content-end gap-2 mb-5"><a href="{{ route('admin.expenditures.remuneration.policies.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-dark">{{ $policy ? 'Save draft' : 'Create draft' }}</button></div>
    </form>
</div>
@endsection
