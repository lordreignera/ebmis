@extends('layouts.admin')

@section('title', 'Expenditures')

@section('content')
@php($canManageStaffPaymentRollout = auth()->user()->canManageStaffPaymentRollout())
<div class="container-fluid expenditure-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Expenditures</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Expenditures</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @if($canManageStaffPaymentRollout)
            <a href="{{ route('admin.expenditures.rollout') }}" class="btn btn-outline-dark">
                <i class="mdi mdi-account-cash-outline me-1"></i> Staff Payment Rollout
            </a>
            @endif
            <a href="{{ route('admin.expenditures.create') }}" class="btn btn-dark">
                <i class="mdi mdi-plus me-1"></i> New Expenditure
            </a>
        </div>
    </div>

    @include('admin.expenditures.partials.alerts')

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="metric-card">
                <span>Pending</span>
                <strong>UGX {{ number_format($stats['pending'], 0) }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <span>Approved</span>
                <strong>UGX {{ number_format($stats['approved'], 0) }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <span>Paid</span>
                <strong>UGX {{ number_format($stats['paid'], 0) }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <span>Records</span>
                <strong>{{ number_format($stats['count']) }}</strong>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.expenditures.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Number, title, description">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['pending', 'approved', 'payment_pending', 'payment_failed', 'paid', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        <option value="operational" @selected(request('type') === 'operational')>Operational</option>
                        <option value="performance_payout" @selected(request('type') === 'performance_payout')>Staff payment</option>
                        <option value="allowance" @selected(request('type') === 'allowance')>Allowance</option>
                        <option value="other" @selected(request('type') === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-dark"><i class="mdi mdi-filter-outline me-1"></i> Filter</button>
                    <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 expenditure-table">
                    <thead>
                        <tr>
                            <th>Expense</th>
                            <th>Account</th>
                            <th>Responsibility</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenditures as $expense)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.expenditures.show', $expense) }}" class="fw-semibold text-dark">{{ $expense->expense_number }}</a>
                                    <div>{{ $expense->title }}</div>
                                    <small class="text-muted">{{ $expense->type === 'performance_payout' ? 'Staff payment' : str_replace('_', ' ', ucfirst($expense->type)) }}</small>
                                </td>
                                <td>
                                    <div>{{ $expense->expenseAccount->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $expense->paymentAccount->name ?? 'Payment not selected' }}</small>
                                </td>
                                <td>
                                    <div>{{ $expense->assignedUser->name ?? 'General expense' }}</div>
                                    <small class="text-muted">{{ $expense->branch->name ?? 'All branches' }}</small>
                                </td>
                                <td>
                                    {{ optional($expense->expense_date)->format('Y-m-d') }}
                                    @if($expense->paid_at)
                                        <small class="d-block text-muted">Paid {{ $expense->paid_at->format('Y-m-d') }}</small>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">UGX {{ number_format((float) $expense->amount, 0) }}</td>
                                <td><span class="status-pill status-{{ $expense->status }}">{{ ucfirst(str_replace('_', ' ', $expense->status)) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.expenditures.show', $expense) }}" class="btn btn-sm btn-outline-dark">
                                        <i class="mdi mdi-eye-outline"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No expenditures recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $expenditures->links() }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.expenditure-page .card,
.metric-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05);
}
.metric-card {
    background: #fff;
    padding: 18px;
}
.metric-card span {
    display: block;
    color: #6b7280;
    font-size: 13px;
}
.metric-card strong {
    color: #111827;
    font-size: 20px;
}
.expenditure-table thead th {
    background: #f3f4f6;
    color: #111827;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    text-transform: uppercase;
}
.status-pill {
    border: 1px solid #d1d5db;
    border-radius: 999px;
    color: #111827;
    display: inline-block;
    padding: 4px 10px;
    background: #fff;
    font-size: 12px;
}
.status-paid { background: #ecfdf5; border-color: #a7f3d0; }
.status-rejected { background: #fef2f2; border-color: #fecaca; }
.status-approved { background: #f8fafc; border-color: #cbd5e1; }
.status-payment_pending { background: #eff6ff; border-color: #bfdbfe; }
.status-payment_failed { background: #fff1f2; border-color: #fecdd3; }
</style>
@endpush
