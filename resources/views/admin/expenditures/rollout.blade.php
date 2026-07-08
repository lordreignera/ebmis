@extends('layouts.admin')

@section('title', 'Staff Payment Rollout')

@section('content')
<div class="container-fluid expenditure-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Staff Payment Rollout</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                    <li class="breadcrumb-item active">Staff Payment Rollout</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.expenditures.partials.alerts')

    @php
        $performanceSummary = [
            'officers' => $rows->count(),
            'principal' => $rows->sum('principal_collected'),
            'qualified' => $rows->sum('qualified_revenue'),
            'base_wage' => $rows->sum('minimum_wage'),
            'compensation' => $rows->sum('stewardship_compensation'),
            'total_pay' => $rows->sum('payout_amount'),
        ];
    @endphp

    <form method="GET" action="{{ route('admin.expenditures.rollout') }}" class="card mb-4">
        <div class="card-header bg-light text-dark"><strong>Weekly Staff Payment Filter</strong></div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Period Start</label>
                    <input type="date" name="period_start" value="{{ $defaults['period_start'] }}" class="form-control" data-weekly-start>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Period End</label>
                    <input type="date" name="period_end" value="{{ $defaults['period_end'] }}" class="form-control" data-weekly-end>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $defaults['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-dark w-100"><i class="mdi mdi-refresh me-1"></i> Preview</button>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <div class="policy-tile">
                        <span>Minimum weekly wage</span>
                        <strong>UGX {{ number_format((float) $policy['minimum_wage'], 0) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="policy-tile">
                        <span>Officer overhead</span>
                        <strong>UGX {{ number_format((float) $policy['officer_overhead'], 0) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="policy-tile">
                        <span>Excellence rate</span>
                        <strong>{{ number_format((float) $policy['levels']['stewardship_excellence']['rate_percent'], 0) }}%</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="policy-tile">
                        <span>Watch rate</span>
                        <strong>{{ number_format((float) $policy['levels']['stewardship_watch']['rate_percent'], 0) }}%</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="policy-tile">
                        <span>Weekly calculations</span>
                        <strong>{{ number_format($periodWeeks) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.expenditures.rollout.generate') }}" class="card mb-4">
        @csrf
        <input type="hidden" name="period_start" value="{{ $defaults['period_start'] }}">
        <input type="hidden" name="period_end" value="{{ $defaults['period_end'] }}">
        <input type="hidden" name="branch_id" value="{{ $defaults['branch_id'] }}">
        <div class="card-header bg-light text-dark d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>Officer Performance Review</strong>
            <span>Total payout: UGX {{ number_format($rows->sum('payout_amount'), 0) }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="performance-tile">
                        <span>Officers</span>
                        <strong>{{ number_format($performanceSummary['officers']) }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="performance-tile">
                        <span>Principal Collected</span>
                        <strong>UGX {{ number_format($performanceSummary['principal'], 0) }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="performance-tile">
                        <span>Qualified Revenue</span>
                        <strong>UGX {{ number_format($performanceSummary['qualified'], 0) }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="performance-tile">
                        <span>Base Wage</span>
                        <strong>UGX {{ number_format($performanceSummary['base_wage'], 0) }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="performance-tile">
                        <span>Stewardship Comp.</span>
                        <strong>UGX {{ number_format($performanceSummary['compensation'], 0) }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="performance-tile">
                        <span>Total Staff Pay</span>
                        <strong>UGX {{ number_format($performanceSummary['total_pay'], 0) }}</strong>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Rollout Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', 'Staff payment rollout ' . $defaults['period_start'] . ' to ' . $defaults['period_end']) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expense Account</label>
                    <select name="expense_account_id" class="form-select" required>
                        <option value="">Select account</option>
                        @foreach($expenseAccounts as $account)
                            <option value="{{ $account->Id }}" @selected((string) old('expense_account_id') === (string) $account->Id)>{{ $account->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Account</label>
                    <select name="payment_account_id" class="form-select">
                        <option value="">Choose when paying</option>
                        @foreach($paymentAccounts as $account)
                            <option value="{{ $account->Id }}" @selected((string) old('payment_account_id') === (string) $account->Id)>{{ $account->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Investment Account</label>
                    <select name="investment_id" class="form-select">
                        <option value="">Choose when paying</option>
                        @foreach($investments as $investment)
                            <option value="{{ $investment->id }}" @selected((string) old('investment_id') === (string) $investment->id)>
                                {{ $investment->name }} - UGX {{ number_format((float) $investment->amount, 0) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional approval note">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 expenditure-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th class="text-end">Qualified Revenue</th>
                            <th class="text-end">Principal</th>
                            <th class="text-end">Score</th>
                            <th>Level</th>
                            <th class="text-end">Net Revenue</th>
                            <th class="text-end">Staff Pay</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['user_name'] }}</strong>
                                    <small class="d-block text-muted">{{ $row['designation'] ?: 'Staff' }}</small>
                                    @if(($row['weeks_count'] ?? 1) > 1)
                                        <small class="d-block text-muted">{{ number_format($row['weeks_count']) }} weekly calculations</small>
                                    @endif
                                    <small class="d-block text-muted">{{ number_format($row['assigned_loans_count']) }} assigned, {{ number_format($row['followups_count']) }} follow-ups</small>
                                </td>
                                <td class="text-end">
                                    <strong>UGX {{ number_format($row['qualified_revenue'], 0) }}</strong>
                                    <small class="d-block text-muted">I {{ number_format($row['interest_collected'], 0) }} | Late {{ number_format($row['late_fees_collected'], 0) }} | Fees {{ number_format($row['fees_collected'], 0) }}</small>
                                </td>
                                <td class="text-end">UGX {{ number_format($row['principal_collected'], 0) }}</td>
                                <td class="text-end">
                                    <strong>{{ number_format($row['stewardship_score'], 1) }}%</strong>
                                    <small class="d-block text-muted">C {{ number_format($row['collection_score'], 0) }} | PAR {{ number_format($row['par_score'], 0) }} | Doc {{ number_format($row['documentation_score'], 0) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $row['stewardship_level'] }}</span>
                                    <small class="d-block text-muted">{{ number_format($row['compensation_rate'], 0) }}% comp.</small>
                                </td>
                                <td class="text-end">UGX {{ number_format($row['net_stewardship_revenue'], 0) }}</td>
                                <td class="text-end">
                                    <div class="input-group input-group-sm payout-input ms-auto">
                                        <span class="input-group-text">UGX</span>
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            name="individual_payout_amount[{{ $row['user_id'] }}]"
                                            value="{{ number_format((float) old('individual_payout_amount.' . $row['user_id'], $row['payout_amount']), 0) }}"
                                            class="form-control text-end js-money-input"
                                            placeholder="75,000"
                                        >
                                    </div>
                                    <small class="d-block text-muted">Base wage {{ number_format($row['minimum_wage'], 0) }} + comp {{ number_format($row['stewardship_compensation'], 0) }}</small>
                                </td>
                                <td class="text-end">
                                    <button
                                        type="submit"
                                        formaction="{{ route('admin.expenditures.rollout.individual') }}"
                                        formnovalidate
                                        name="user_id"
                                        value="{{ $row['user_id'] }}"
                                        class="btn btn-sm btn-payout-action"
                                    >
                                        Create Individual Payout
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No staff activity found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-dark" @disabled($rows->where('payout_amount', '>', 0)->isEmpty())>
                <i class="mdi mdi-content-save-outline me-1"></i> Generate Reviewed Staff Payment Rollout
            </button>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-light text-dark"><strong>Recent Staff Payment Rollouts</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 expenditure-table">
                    <thead><tr><th>Rollout</th><th>Period</th><th>Status</th><th class="text-end">Amount</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rollouts as $rollout)
                            <tr>
                                <td>{{ $rollout->rollout_number }}<div>{{ $rollout->title }}</div></td>
                                <td>{{ $rollout->period_start->format('Y-m-d') }} to {{ $rollout->period_end->format('Y-m-d') }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $rollout->status)) }}</td>
                                <td class="text-end">UGX {{ number_format((float) $rollout->total_amount, 0) }}</td>
                                <td class="text-end"><a href="{{ route('admin.expenditures.rollout.show', $rollout) }}" class="btn btn-sm btn-outline-dark">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No staff payment rollouts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.expenditure-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }
.policy-tile { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #ffffff; min-height: 76px; }
.policy-tile span { display: block; color: #6b7280; font-size: 12px; margin-bottom: 6px; }
.policy-tile strong { color: #111827; font-size: 16px; }
.performance-tile { border: 1px solid #dbeafe; border-radius: 8px; padding: 12px; background: #f8fbff; min-height: 78px; }
.performance-tile span { display: block; color: #4b5563; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; }
.performance-tile strong { color: #111827; font-size: 14px; line-height: 1.25; overflow-wrap: anywhere; }
.expenditure-table thead th { background: #f3f4f6; color: #111827; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; }
.expenditure-table td { vertical-align: middle; }
.expenditure-table .payout-input { max-width: 150px; }
.expenditure-table .payout-input .input-group-text { background: #eef2f7; color: #111827; border-color: #cbd5e1; }
.expenditure-table .payout-input .form-control { color: #111827 !important; font-weight: 600; }
.btn-payout-action {
    background: #ffffff !important;
    border: 1px solid #111827 !important;
    color: #111827 !important;
    min-width: 104px;
}
.btn-payout-action:hover,
.btn-payout-action:focus {
    background: #f3f4f6 !important;
    border-color: #111827 !important;
    color: #111827 !important;
    box-shadow: 0 0 0 0.16rem rgba(17, 24, 39, 0.12) !important;
}
.btn-payout-action:active {
    background: #e5e7eb !important;
    color: #111827 !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weeklyStart = document.querySelector('[data-weekly-start]');
    const weeklyEnd = document.querySelector('[data-weekly-end]');
    const formatDate = function (date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    };

    if (weeklyStart && weeklyEnd) {
        weeklyStart.addEventListener('change', function () {
            if (!weeklyStart.value) return;

            const startDate = new Date(weeklyStart.value + 'T00:00:00');
            startDate.setDate(startDate.getDate() + 6);
            weeklyEnd.value = formatDate(startDate);
        });
    }

    const formatMoney = function (value) {
        const normalized = String(value || '').replace(/,/g, '').replace(/[^\d.]/g, '');
        if (!normalized) return '0';

        const parts = normalized.split('.');
        const whole = parts[0] ? Number(parts[0]).toLocaleString('en-US') : '0';
        const decimals = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '';

        return whole + decimals;
    };

    document.querySelectorAll('.js-money-input').forEach(function (input) {
        input.value = formatMoney(input.value);
        input.addEventListener('input', function () {
            input.value = formatMoney(input.value);
        });
        input.addEventListener('focus', function () {
            input.select();
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('.js-money-input').forEach(function (input) {
                input.value = input.value.replace(/,/g, '');
            });
        });
    });
});
</script>
@endpush
