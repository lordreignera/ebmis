@extends('layouts.admin')

@section('title', 'Cash Securities Report')

@section('content')
@php
    $canManageCashSecurities = auth()->user()?->isSuperAdmin() || auth()->user()?->can('manage-cash-securities');
@endphp

<div class="container-fluid cash-security-report-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Cash Securities Report</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Cash Securities Report</li>
                </ol>
            </nav>
        </div>
        @if($canManageCashSecurities)
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.cash-securities.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="mdi mdi-file-export-outline me-1"></i> Export CSV
                </a>
                <a href="{{ route('admin.cash-securities.index') }}" class="btn btn-dark">
                    <i class="mdi mdi-shield-check-outline me-1"></i> Manage Securities
                </a>
            </div>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric-card"><span>Records</span><strong>{{ number_format($stats['total_securities']) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Held</span><strong>UGX {{ number_format((float) $stats['held_amount'], 0) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Pending</span><strong>UGX {{ number_format((float) $stats['pending_amount'], 0) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Returned</span><strong>UGX {{ number_format((float) $stats['returned_amount'], 0) }}</strong></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.cash-securities') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid / Held</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="returned" @selected(request('status') === 'returned')>Returned</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-dark"><i class="mdi mdi-filter-outline me-1"></i> Filter</button>
                    <a href="{{ route('admin.reports.cash-securities') }}" class="btn btn-outline-secondary"><i class="mdi mdi-close me-1"></i> Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Security</th>
                            <th>Member</th>
                            <th>Loan</th>
                            <th>Payment</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($securities as $security)
                            <tr>
                                <td>
                                    <span class="fw-semibold">CS-{{ str_pad((string) $security->id, 6, '0', STR_PAD_LEFT) }}</span>
                                    <small class="d-block text-muted">{{ optional($security->datecreated ?? $security->created_at)->format('Y-m-d H:i') }}</small>
                                    @if($security->transaction_reference || $security->pay_ref)
                                        <small class="d-block text-muted">{{ $security->transaction_reference ?: $security->pay_ref }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $security->member->full_name ?? 'Unknown member' }}</div>
                                    <small class="text-muted">{{ $security->member->code ?? '' }}</small>
                                </td>
                                <td>{{ $security->loan->code ?? 'Not linked' }}</td>
                                <td>{{ $security->payment_type_name }}</td>
                                <td class="text-end fw-semibold">UGX {{ number_format((float) $security->amount, 0) }}</td>
                                <td>{!! $security->status_badge !!}</td>
                                <td>
                                    @if((int) $security->returned === 1)
                                        {{ optional($security->returned_at)->format('Y-m-d H:i') }}
                                    @else
                                        No
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">No cash securities found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">{{ $securities->links() }}</div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cash-security-report-page .card,
.metric-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05);
}
.metric-card { background: #fff; padding: 18px; }
.metric-card span { color: #6b7280; display: block; font-size: 13px; }
.metric-card strong { color: #111827; font-size: 20px; }
</style>
@endpush
