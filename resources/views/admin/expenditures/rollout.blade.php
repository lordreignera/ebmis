@extends('layouts.admin')

@section('title', 'Payment Rollout')

@section('content')
<div class="container-fluid expenditure-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Performance Payment Rollout</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                    <li class="breadcrumb-item active">Rollout</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.expenditures.partials.alerts')

    <form method="GET" action="{{ route('admin.expenditures.rollout') }}" class="card mb-4">
        <div class="card-header bg-light text-dark"><strong>Preview Formula</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Period Start</label>
                    <input type="date" name="period_start" value="{{ $defaults['period_start'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Period End</label>
                    <input type="date" name="period_end" value="{{ $defaults['period_end'] }}" class="form-control">
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
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-dark w-100"><i class="mdi mdi-refresh me-1"></i> Preview</button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">UGX per Assigned Loan</label>
                    <input type="number" step="0.01" min="0" name="per_assigned_loan" value="{{ $defaults['per_assigned_loan'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">UGX per Performing Loan</label>
                    <input type="number" step="0.01" min="0" name="per_performing_loan" value="{{ $defaults['per_performing_loan'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">UGX per Follow-up</label>
                    <input type="number" step="0.01" min="0" name="per_follow_up" value="{{ $defaults['per_follow_up'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">% of Collections</label>
                    <input type="number" step="0.01" min="0" max="100" name="collection_commission_percent" value="{{ $defaults['collection_commission_percent'] }}" class="form-control">
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.expenditures.rollout.generate') }}" class="card mb-4">
        @csrf
        <input type="hidden" name="period_start" value="{{ $defaults['period_start'] }}">
        <input type="hidden" name="period_end" value="{{ $defaults['period_end'] }}">
        <input type="hidden" name="branch_id" value="{{ $defaults['branch_id'] }}">
        <input type="hidden" name="per_assigned_loan" value="{{ $defaults['per_assigned_loan'] }}">
        <input type="hidden" name="per_performing_loan" value="{{ $defaults['per_performing_loan'] }}">
        <input type="hidden" name="per_follow_up" value="{{ $defaults['per_follow_up'] }}">
        <input type="hidden" name="collection_commission_percent" value="{{ $defaults['collection_commission_percent'] }}">
        <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
            <strong>Rollout Preview</strong>
            <span>Total payout: UGX {{ number_format($rows->sum('payout_amount'), 0) }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Rollout Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', 'Performance payout ' . $defaults['period_start'] . ' to ' . $defaults['period_end']) }}" required>
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
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 expenditure-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="text-end">Assigned</th>
                            <th class="text-end">Performing</th>
                            <th class="text-end">Overdue</th>
                            <th class="text-end">Follow-ups</th>
                            <th class="text-end">Collections</th>
                            <th class="text-end">Payout</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['user_name'] }}</strong>
                                    <small class="d-block text-muted">{{ $row['designation'] }}</small>
                                </td>
                                <td class="text-end">{{ number_format($row['assigned_loans_count']) }}</td>
                                <td class="text-end">{{ number_format($row['performing_loans_count']) }}</td>
                                <td class="text-end">{{ number_format($row['overdue_loans_count']) }}</td>
                                <td class="text-end">{{ number_format($row['followups_count']) }}</td>
                                <td class="text-end">UGX {{ number_format($row['collections_amount'], 0) }}</td>
                                <td class="text-end">
                                    <div class="input-group input-group-sm payout-input ms-auto">
                                        <span class="input-group-text">UGX</span>
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            name="individual_payout_amount[{{ $row['user_id'] }}]"
                                            value="{{ number_format((float) old('individual_payout_amount.' . $row['user_id'], $row['payout_amount']), 0) }}"
                                            class="form-control text-end js-money-input"
                                            placeholder="1,000"
                                        >
                                    </div>
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
                                        Create Payout
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No activity found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-dark" @disabled($rows->where('payout_amount', '>', 0)->isEmpty())>
                <i class="mdi mdi-content-save-outline me-1"></i> Generate Rollout
            </button>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-light text-dark"><strong>Recent Rollouts</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 expenditure-table">
                    <thead><tr><th>Rollout</th><th>Period</th><th>Status</th><th class="text-end">Amount</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rollouts as $rollout)
                            <tr>
                                <td>{{ $rollout->rollout_number }}<div>{{ $rollout->title }}</div></td>
                                <td>{{ $rollout->period_start->format('Y-m-d') }} to {{ $rollout->period_end->format('Y-m-d') }}</td>
                                <td>{{ ucfirst($rollout->status) }}</td>
                                <td class="text-end">UGX {{ number_format((float) $rollout->total_amount, 0) }}</td>
                                <td class="text-end"><a href="{{ route('admin.expenditures.rollout.show', $rollout) }}" class="btn btn-sm btn-outline-dark">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No rollout batches yet.</td></tr>
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
.expenditure-table thead th { background: #f3f4f6; color: #111827; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; }
.expenditure-table .payout-input { max-width: 150px; }
.expenditure-table .payout-input .input-group-text { background: #eef2f7; color: #111827; border-color: #cbd5e1; }
.expenditure-table .payout-input .form-control { color: #111827 !important; font-weight: 600; }
.btn-payout-action {
    background: #ffffff !important;
    border: 1px solid #111827 !important;
    color: #111827 !important;
    min-width: 96px;
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
