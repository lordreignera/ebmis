@extends('layouts.admin')

@section('title', 'My Remuneration Performance')

@section('content')
<div class="container-fluid officer-remuneration-page">
    @include('admin.expenditures.partials.alerts')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h4 class="mb-1">My remuneration performance</h4><p class="text-muted mb-0">Your performance is calculated from your assigned portfolio and confirmed collections.</p></div>
        <span class="badge bg-{{ $policy->status === 'active' ? 'success' : 'warning text-dark' }}">{{ ucfirst($policy->status) }} policy · {{ $policy->version }}</span>
    </div>

    <div class="alert alert-info border-0"><strong>Performance view only.</strong> This page does not create a payout. Monthly totals are an informational sum of complete Monday–Sunday weeks whose Monday falls inside the selected month; weekly remuneration remains the calculation source.</div>

    <div class="card mb-4"><div class="card-body"><form method="GET" action="{{ route('admin.remuneration.performance') }}" class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label">View</label><select class="form-select" name="view" onchange="this.form.submit()"><option value="week" @selected($mode === 'week')>Weekly performance</option><option value="month" @selected($mode === 'month')>Monthly performance</option></select></div>
        @if($mode === 'week')<div class="col-md-3"><label class="form-label">Week date</label><input type="date" class="form-control" name="week_start" value="{{ $periodStart->toDateString() }}"></div>@else<div class="col-md-3"><label class="form-label">Month</label><input type="month" class="form-control" name="month" value="{{ $periodStart->format('Y-m') }}"></div>@endif
        <div class="col-md-2"><button class="btn btn-dark w-100" type="submit"><i class="mdi mdi-filter-outline me-1"></i>Refresh</button></div>
    </form></div></div>

    @php
        $summary = $monthly ?: [
            'qualified_revenue' => $weeks[0]['qualified_revenue']['qualified_revenue'] ?? 0,
            'base_amount' => $weeks[0]['base_amount'] ?? null,
            'bonus_amount' => $weeks[0]['bonus_amount'] ?? null,
            'gross_remuneration' => $weeks[0]['gross_remuneration'] ?? null,
            'net_remuneration' => $weeks[0]['net_remuneration'] ?? null,
            'ready_for_payment' => $weeks[0]['ready_for_payment'] ?? false,
            'issues' => $weeks[0]['issues'] ?? [],
        ];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric-card"><span>Qualified revenue</span><strong>UGX {{ number_format($summary['qualified_revenue'], 2) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Base remuneration</span><strong>{{ $summary['base_amount'] === null ? 'Unresolved' : 'UGX ' . number_format($summary['base_amount'], 2) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Four bonuses</span><strong>{{ $summary['bonus_amount'] === null ? 'Unresolved' : 'UGX ' . number_format($summary['bonus_amount'], 2) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Net preview</span><strong>{{ $summary['net_remuneration'] === null ? 'Unresolved' : 'UGX ' . number_format($summary['net_remuneration'], 2) }}</strong></div></div>
    </div>

    @if($summary['issues'])<div class="alert alert-warning"><strong>Items still being resolved:</strong><ul class="mb-0 mt-2">@foreach($summary['issues'] as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>@endif

    <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ $mode === 'month' ? 'Weekly performance inside ' . $periodStart->format('F Y') : 'Week of ' . $periodStart->format('d M Y') }}</h5><span class="badge {{ $summary['ready_for_payment'] ? 'bg-success' : 'bg-secondary' }}">{{ $summary['ready_for_payment'] ? 'Ready for approval' : 'Review only' }}</span></div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Period</th><th>Qualified revenue</th><th>Current-loan score</th><th>Base</th><th>PAR</th><th>Follow-ups</th><th>Clients</th><th>Budget overrun</th><th>Status</th></tr></thead><tbody>
        @forelse($weeks as $week)
            <tr><td>{{ $week['period_start'] }}<small class="d-block text-muted">to {{ $week['period_end'] }}</small></td><td>UGX {{ number_format($week['qualified_revenue']['qualified_revenue'], 2) }}</td><td>{{ $week['current_loan_score'] === null ? 'Unresolved' : number_format($week['current_loan_score'], 2) . '%' }}</td><td>{{ $week['base_amount'] === null ? 'Unresolved' : 'UGX ' . number_format($week['base_amount'], 2) }}<small class="d-block text-muted">{{ $week['base_rate'] === null ? '—' : number_format($week['base_rate'], 2) . '%' }}</small></td><td>{{ $week['portfolio']['par_percent'] === null ? 'Unresolved' : number_format($week['portfolio']['par_percent'], 2) . '%' }}</td><td>{{ $week['follow_ups']['completed_count'] }} / {{ $week['follow_ups']['due_count'] === null ? '—' : $week['follow_ups']['due_count'] }}</td><td>{{ $week['growth']['opening_active_clients'] }} → {{ $week['growth']['closing_active_clients'] }}</td><td>UGX {{ number_format($week['budget']['budget_overrun'], 2) }}</td><td><span class="badge {{ $week['ready_for_payment'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $week['ready_for_payment'] ? 'Resolved' : 'Pending inputs' }}</span></td></tr>
        @empty
            <tr><td colspan="9" class="text-center py-5 text-muted">No weekly performance periods are available for this selection.</td></tr>
        @endforelse
    </tbody></table></div></div>

    @if($mode === 'week' && isset($weeks[0]))
        <div class="card mt-4"><div class="card-header"><h5 class="mb-0">Four independent bonuses</h5></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Metric</th><th>Score</th><th>Earned rate</th><th>Explanation</th></tr></thead><tbody>@foreach($weeks[0]['metrics'] as $metric)<tr><td>{{ ucwords(str_replace('_', ' ', $metric['metric_code'])) }}</td><td>{{ $metric['score'] === null ? 'Unresolved' : number_format($metric['score'], 2) . '%' }}</td><td>{{ number_format($metric['earned_rate'], 2) }}% / 3%</td><td class="text-muted">{{ $metric['notes'] ?: 'Calculated from the approved policy band.' }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
    @if($mode === 'week')
        @if(isset($weeks[0]))
            @php($weeklyResult = $weeks[0])
            <div class="card mt-4"><div class="card-header"><h5 class="mb-0">My weekly remuneration statement</h5></div><div class="card-body">
                <p class="small text-muted">Calculation policy: {{ $weeklyResult['policy_version'] }}. Each bonus is calculated separately against qualified revenue.</p>
                <dl class="row mb-0">
                    @foreach($weeklyResult['metrics'] as $metric)
                        <dt class="col-7">{{ ucwords(str_replace('_', ' ', $metric['metric_code'])) }} bonus</dt><dd class="col-5 text-end">UGX {{ number_format($metric['bonus_amount'], 2) }}</dd>
                    @endforeach
                    @foreach(['gross_remuneration' => 'Gross remuneration', 'gross_overrun_deduction' => 'Current overrun deducted from gross', 'outstanding_deficit' => 'Opening deficit', 'deficit_recovery_amount' => 'Older deficit recovered', 'closing_deficit' => 'Deficit carried forward', 'net_remuneration' => 'Net remuneration'] as $key => $label)
                        <dt class="col-7">{{ $label }}</dt><dd class="col-5 text-end">{{ !isset($weeklyResult[$key]) ? 'Unresolved' : 'UGX '.number_format($weeklyResult[$key], 2) }}</dd>
                    @endforeach
                </dl>
            </div></div>
        @endif
        @include('admin.staff-remuneration.partials.spending-report')
        @if(isset($weeks[0])) @include('admin.staff-remuneration.partials.fund-statement', ['preview' => $weeks[0]]) @endif
    @endif
</div>
@endsection

@push('styles')
<style>
.officer-remuneration-page .metric-card { border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; padding: 1rem; height: 100%; }
.officer-remuneration-page .metric-card span { display: block; color: #64748b; font-size: .78rem; margin-bottom: .4rem; }
.officer-remuneration-page .metric-card strong { font-size: 1.15rem; }
</style>
@endpush
