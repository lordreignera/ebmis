@extends('layouts.admin')

@section('title', 'Remuneration Policy ' . $policy->version)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="mb-1">{{ $policy->title }}</h4><p class="text-muted mb-0">Version {{ $policy->version }}</p></div>
        <div>
            <a href="{{ route('admin.expenditures.remuneration.policies.index') }}" class="btn btn-outline-secondary me-2">Back</a>
            @if($policy->status === 'draft')
                <a href="{{ route('admin.expenditures.remuneration.policies.edit', $policy) }}" class="btn btn-dark">Edit policy and bonus bands</a>
            @else
                <a href="{{ route('admin.expenditures.remuneration.policies.create', ['from' => $policy->id]) }}" class="btn btn-dark">Create editable version</a>
            @endif
        </div>
    </div>

    @include('admin.expenditures.partials.alerts')
    <div class="alert alert-{{ $policy->status === 'active' ? 'success' : 'warning' }}">
        Status: <strong>{{ ucfirst($policy->status) }}</strong>.
        @if($policy->status === 'draft')
            Administrators and Super Administrators can edit this policy and its bonus bands.
        @else
            This version is locked. Create an editable version to change the rules without rewriting historical calculations.
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Base, PAR and budget</h5></div><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-7">Base threshold</dt><dd class="col-5 text-end">{{ $policy->base_current_loan_threshold }}%</dd>
                <dt class="col-7">At or above threshold</dt><dd class="col-5 text-end">{{ $policy->base_rate_above_threshold }}%</dd>
                <dt class="col-7">Below threshold</dt><dd class="col-5 text-end">{{ $policy->base_rate_at_or_below_threshold }}%</dd>
                <dt class="col-7">Budget allocation</dt><dd class="col-5 text-end">{{ $policy->budget_allocation_rate }}%</dd>
                <dt class="col-7">PAR overdue threshold</dt><dd class="col-5 text-end">{{ $policy->par_overdue_days }} calendar days</dd>
                <dt class="col-7">PAR maximum bonus</dt><dd class="col-5 text-end">{{ $policy->par_bonus_maximum_rate }}%</dd>
                <dt class="col-7">Effective</dt><dd class="col-5 text-end">{{ optional($policy->effective_from)->format('Y-m-d') ?: 'Not set' }} — {{ optional($policy->effective_to)->format('Y-m-d') ?: 'Open' }}</dd>
            </dl>
        </div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Audit</h5></div><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-7">Created by</dt><dd class="col-5 text-end">{{ $policy->createdBy->name ?? 'System' }}</dd>
                <dt class="col-7">Approved by</dt><dd class="col-5 text-end">{{ $policy->approvedBy->name ?? 'Not approved' }}</dd>
                <dt class="col-7">Approved at</dt><dd class="col-5 text-end">{{ optional($policy->approved_at)->format('Y-m-d H:i') ?: '—' }}</dd>
                <dt class="col-7">Other bonus maximum</dt><dd class="col-5 text-end">3% each</dd>
                <dt class="col-7">Weekly proration</dt><dd class="col-5 text-end">Disabled</dd>
            </dl>
        </div></div></div>
    </div>

    <div class="card mt-4"><div class="card-header"><h5 class="mb-0">Bonus bands</h5></div><div class="table-responsive">
        <table class="table mb-0"><thead><tr><th>Metric</th><th>Lower</th><th>Upper</th><th>Earned rate</th><th>Notes</th></tr></thead><tbody>
        @foreach($policy->bonusBands->sortBy(['metric_code', 'sort_order']) as $band)
            <tr><td>{{ ucwords(str_replace('_', ' ', $band->metric_code)) }}</td><td>{{ $band->lower_score === null ? 'No lower bound' : ($band->lower_inclusive ? '≥ ' : '> ') . $band->lower_score . '%' }}</td><td>{{ $band->upper_score === null ? 'No upper bound' : ($band->upper_inclusive ? '≤ ' : '< ') . $band->upper_score . '%' }}</td><td>{{ $band->earned_rate }}% / {{ $band->maximum_rate }}%</td><td>{{ $band->notes ?: '—' }}</td></tr>
        @endforeach
        </tbody></table>
    </div></div>

    <div class="card mt-4"><div class="card-header"><h5 class="mb-0">Unresolved/configurable scoring inputs</h5></div><div class="card-body"><pre class="mb-0 small">{{ json_encode($policy->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div></div>
</div>
@endsection
