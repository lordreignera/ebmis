@extends('layouts.admin')

@section('title', $rollout->rollout_number . ' Staff Payment Rollout')

@section('content')
<div class="container-fluid expenditure-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">{{ $rollout->rollout_number }} Staff Payment Rollout</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.rollout') }}">Staff Payment Rollout</a></li>
                    <li class="breadcrumb-item active">{{ $rollout->rollout_number }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.expenditures.rollout') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.expenditures.partials.alerts')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light text-dark d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $rollout->title }}</strong>
                    <span>{{ ucfirst(str_replace('_', ' ', $rollout->status)) }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><small class="text-muted">Period</small><div>{{ $rollout->period_start->format('Y-m-d') }} to {{ $rollout->period_end->format('Y-m-d') }}</div></div>
                        <div class="col-md-4"><small class="text-muted">Branch</small><div>{{ $rollout->branch->name ?? 'All branches' }}</div></div>
                        <div class="col-md-4"><small class="text-muted">Total</small><div class="fw-bold">UGX {{ number_format((float) $rollout->total_amount, 0) }}</div></div>
                        <div class="col-md-4 mt-3"><small class="text-muted">Investment Account</small><div>{{ $rollout->investment->name ?? 'Not selected' }}</div></div>
                        <div class="col-md-4 mt-3"><small class="text-muted">Payment Account</small><div>{{ $rollout->paymentAccount->full_name ?? 'Not selected' }}</div></div>
                        <div class="col-md-4 mt-3"><small class="text-muted">Weekly Wage</small><div>UGX {{ number_format((float) data_get($policy, 'minimum_wage', 75000), 0) }}</div></div>
                        <div class="col-md-4 mt-3"><small class="text-muted">Weekly Calculations</small><div>{{ number_format((int) data_get($policy, 'weekly_periods_count', 1)) }}</div></div>
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
                                    <th class="text-end">Pay</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rollout->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->user->name ?? 'User #' . $item->user_id }}</strong>
                                            @if($item->expenditure_id)
                                                <small class="d-block text-muted">Expense #{{ $item->expenditure_id }}</small>
                                            @endif
                                            @if($item->notes)
                                                <small class="d-block text-muted">{{ $item->notes }}</small>
                                            @endif
                                            <small class="d-block text-muted">{{ number_format($item->assigned_loans_count) }} assigned, {{ number_format($item->followups_count) }} follow-ups</small>
                                        </td>
                                        <td class="text-end">
                                            <strong>UGX {{ number_format((float) $item->qualified_revenue, 0) }}</strong>
                                            <small class="d-block text-muted">I {{ number_format((float) $item->interest_collected, 0) }} | Late {{ number_format((float) $item->late_fees_collected, 0) }} | Fees {{ number_format((float) $item->fees_collected, 0) }}</small>
                                        </td>
                                        <td class="text-end">UGX {{ number_format((float) $item->principal_collected, 0) }}</td>
                                        <td class="text-end">
                                            <strong>{{ number_format((float) $item->stewardship_score, 1) }}%</strong>
                                            <small class="d-block text-muted">C {{ number_format((float) $item->collection_score, 0) }} | PAR {{ number_format((float) $item->par_score, 0) }} | Doc {{ number_format((float) $item->documentation_score, 0) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $item->stewardship_level ?: 'Not scored' }}</span>
                                            <small class="d-block text-muted">{{ number_format((float) $item->compensation_rate, 0) }}% comp.</small>
                                        </td>
                                        <td class="text-end">
                                            <strong>UGX {{ number_format((float) $item->payout_amount, 0) }}</strong>
                                            <small class="d-block text-muted">Base wage {{ number_format((float) $item->minimum_wage, 0) }} + comp {{ number_format((float) $item->stewardship_compensation, 0) }}</small>
                                        </td>
                                        <td>
                                            @if($item->payment_blocked)
                                                <span class="badge bg-danger">Blocked</span>
                                                <small class="d-block text-muted">{{ $item->block_reason }}</small>
                                            @else
                                                {{ $item->expenditure ? ucfirst(str_replace('_', ' ', $item->expenditure->status)) : 'Not created' }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($item->expenditure)
                                                <a href="{{ route('admin.expenditures.show', $item->expenditure) }}" class="btn btn-sm btn-outline-dark">Open</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light text-dark"><strong>Actions</strong></div>
                <div class="card-body">
                    @if($rollout->status === 'draft')
                        <form method="POST" action="{{ route('admin.expenditures.rollout.approve', $rollout) }}" class="mb-3">
                            @csrf
                            <button class="btn btn-outline-dark w-100"><i class="mdi mdi-check me-1"></i> Approve Staff Payment Rollout</button>
                        </form>
                    @endif

                    @if(in_array($rollout->status, ['draft', 'approved'], true))
                        <form method="POST" action="{{ route('admin.expenditures.rollout.pay', $rollout) }}">
                            @csrf
                            <input type="hidden" name="payment_channel" value="mobile_money">
                            <label class="form-label">Payment Channel</label>
                            <input type="text" class="form-control bg-light mb-2" value="Mobile Money" readonly>
                            <label class="form-label">Investment Account</label>
                            <select name="investment_id" class="form-select mb-2" required>
                                <option value="">Select investment funding account</option>
                                @foreach($investments as $investment)
                                    <option value="{{ $investment->id }}" @selected((string) old('investment_id', $rollout->investment_id) === (string) $investment->id)>
                                        {{ $investment->name }} - UGX {{ number_format((float) $investment->amount, 0) }}
                                    </option>
                                @endforeach
                            </select>
                            <label class="form-label">GL Payment Account</label>
                            <select name="payment_account_id" class="form-select mb-2" required>
                                <option value="">Select settlement account</option>
                                @foreach($paymentAccounts as $account)
                                    <option value="{{ $account->Id }}" @selected((string) old('payment_account_id', $rollout->payment_account_id) === (string) $account->Id)>{{ $account->full_name }}</option>
                                @endforeach
                            </select>
                            <label class="form-label">Payment Method</label>
                            <input type="text" name="payment_method" class="form-control mb-3" value="{{ old('payment_method', 'Staff Mobile Money Rollout') }}">
                            <div class="alert alert-warning small">
                                This sends mobile money to each staff member's saved phone number. Open each generated payout to check pending statuses.
                            </div>
                            <button class="btn btn-dark w-100"><i class="mdi mdi-cellphone-arrow-down me-1"></i> Send Staff Mobile Money</button>
                        </form>
                    @elseif($rollout->status === 'payment_pending')
                        <div class="alert alert-info small mb-0">
                            Mobile-money payouts are pending. Open each generated payout below and use Check Mobile Money Status before retrying.
                        </div>
                    @elseif($rollout->status === 'payment_failed')
                        <div class="alert alert-danger small mb-0">
                            Some staff mobile-money payouts failed. Open the failed payout records below and retry from each expenditure page.
                        </div>
                    @elseif($rollout->status !== 'paid')
                        <div class="alert alert-light border small mb-0">No unsent staff payouts are available for this rollout.</div>
                    @else
                        <div class="alert alert-light border mb-0">Paid {{ optional($rollout->paid_at)->format('Y-m-d H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.expenditure-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }
.expenditure-table thead th { background: #f3f4f6; color: #111827; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; }
.expenditure-table td { vertical-align: middle; }
</style>
@endpush
