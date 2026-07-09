@extends('layouts.admin')

@section('title', 'Cash Securities')

@section('content')
<div class="container-fluid cash-security-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Cash Securities</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Cash Securities</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.cash-securities.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="mdi mdi-file-export-outline me-1"></i> Export CSV
            </a>
            <a href="{{ route('admin.cash-securities.create') }}" class="btn btn-dark">
                <i class="mdi mdi-plus me-1"></i> Add Security
            </a>
        </div>
    </div>

    @include('admin.cash-securities.partials.alerts')

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric-card"><span>Records</span><strong>{{ number_format($stats['total_count']) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Held</span><strong>UGX {{ number_format((float) $stats['held_amount'], 0) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Pending</span><strong>UGX {{ number_format((float) $stats['pending_amount'], 0) }}</strong></div></div>
        <div class="col-md-3"><div class="metric-card"><span>Returned</span><strong>UGX {{ number_format((float) $stats['returned_amount'], 0) }}</strong></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.cash-securities.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Member, account, ref, loan">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid / Held</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="returned" @selected(request('status') === 'returned')>Returned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment</label>
                    <select name="payment_type" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('payment_type') === '1')>Mobile Money</option>
                        <option value="2" @selected(request('payment_type') === '2')>Cash</option>
                        <option value="3" @selected(request('payment_type') === '3')>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button class="btn btn-dark"><i class="mdi mdi-filter-outline"></i></button>
                    <a href="{{ route('admin.cash-securities.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-close"></i></a>
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
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($securities as $security)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.cash-securities.show', $security) }}" class="fw-semibold text-dark">
                                        CS-{{ str_pad((string) $security->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                    <small class="d-block text-muted">{{ optional($security->datecreated ?? $security->created_at)->format('Y-m-d H:i') }}</small>
                                    @if($security->transaction_reference || $security->pay_ref)
                                        <small class="d-block text-muted">{{ $security->transaction_reference ?: $security->pay_ref }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $security->member->full_name ?? 'Unknown member' }}</div>
                                    <small class="text-muted">{{ $security->member->code ?? '' }} {{ $security->member->cash_security_account_number ? ' / ' . $security->member->cash_security_account_number : '' }}</small>
                                </td>
                                <td>{{ $security->loan->code ?? 'Not linked' }}</td>
                                <td>{{ $security->payment_type_name }}</td>
                                <td class="text-end fw-semibold">UGX {{ number_format((float) $security->amount, 0) }}</td>
                                <td>{!! $security->status_badge !!}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.cash-securities.show', $security) }}" class="btn btn-outline-dark">View</a>
                                        <a href="{{ route('admin.cash-securities.edit', $security) }}" class="btn btn-outline-primary">Edit</a>
                                        @if((int) $security->status === \App\Models\CashSecurity::STATUS_PAID)
                                            <a href="{{ route('admin.cash-securities.receipt', $security) }}" class="btn btn-outline-success" target="_blank">Receipt</a>
                                        @endif
                                    </div>
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
.cash-security-page .card,
.metric-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05);
}
.metric-card { background: #fff; padding: 18px; }
.metric-card span { color: #6b7280; display: block; font-size: 13px; }
.metric-card strong { color: #111827; font-size: 20px; }
@media (max-width: 575.98px) {
    .cash-security-page .btn-group { display: grid; gap: 0.35rem; width: 100%; }
    .cash-security-page .btn-group .btn { border-radius: 6px !important; }
}
</style>
@endpush
