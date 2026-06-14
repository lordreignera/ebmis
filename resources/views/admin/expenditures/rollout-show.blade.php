@extends('layouts.admin')

@section('title', $rollout->rollout_number)

@section('content')
<div class="container-fluid expenditure-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">{{ $rollout->rollout_number }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.rollout') }}">Rollout</a></li>
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
                <div class="card-header bg-light text-dark d-flex justify-content-between">
                    <strong>{{ $rollout->title }}</strong>
                    <span>{{ ucfirst($rollout->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><small class="text-muted">Period</small><div>{{ $rollout->period_start->format('Y-m-d') }} to {{ $rollout->period_end->format('Y-m-d') }}</div></div>
                        <div class="col-md-4"><small class="text-muted">Branch</small><div>{{ $rollout->branch->name ?? 'All branches' }}</div></div>
                        <div class="col-md-4"><small class="text-muted">Total</small><div class="fw-bold">UGX {{ number_format((float) $rollout->total_amount, 0) }}</div></div>
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
                                        </td>
                                        <td class="text-end">{{ number_format($item->assigned_loans_count) }}</td>
                                        <td class="text-end">{{ number_format($item->performing_loans_count) }}</td>
                                        <td class="text-end">{{ number_format($item->overdue_loans_count) }}</td>
                                        <td class="text-end">{{ number_format($item->followups_count) }}</td>
                                        <td class="text-end">UGX {{ number_format((float) $item->collections_amount, 0) }}</td>
                                        <td class="text-end fw-bold">UGX {{ number_format((float) $item->payout_amount, 0) }}</td>
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
                            <button class="btn btn-outline-dark w-100"><i class="mdi mdi-check me-1"></i> Approve Rollout</button>
                        </form>
                    @endif

                    @if($rollout->status !== 'paid')
                        <form method="POST" action="{{ route('admin.expenditures.rollout.pay', $rollout) }}">
                            @csrf
                            <label class="form-label">Payment Account</label>
                            <select name="payment_account_id" class="form-select mb-2" required>
                                <option value="">Select cash/bank account</option>
                                @foreach($paymentAccounts as $account)
                                    <option value="{{ $account->Id }}" @selected((string) old('payment_account_id', $rollout->payment_account_id) === (string) $account->Id)>{{ $account->full_name }}</option>
                                @endforeach
                            </select>
                            <label class="form-label">Payment Method</label>
                            <input type="text" name="payment_method" class="form-control mb-3" value="{{ old('payment_method', 'Rollout') }}">
                            <button class="btn btn-dark w-100"><i class="mdi mdi-cash-check me-1"></i> Pay Rollout & Post</button>
                        </form>
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
</style>
@endpush
