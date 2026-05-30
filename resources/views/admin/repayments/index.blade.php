@extends('layouts.admin')

@section('title', 'Loan Repayments')

@push('styles')
<style>
    .repayment-page {
        color: #0f172a;
    }

    .repayment-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .kpi-strip {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }

    .kpi-card {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        padding: .9rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex: 0 0 auto;
    }

    .kpi-icon.total { background: #047857; }
    .kpi-icon.principal { background: #2563eb; }
    .kpi-icon.interest { background: #b45309; }
    .kpi-icon.penalty { background: #dc2626; }
    .kpi-icon.fees { background: #6d28d9; }
    .kpi-icon.count { background: #0f766e; }

    .kpi-label {
        color: #64748b;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
        margin-bottom: .2rem;
    }

    .kpi-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .kpi-note {
        color: #64748b;
        font-size: .75rem;
        margin-top: .15rem;
    }

    .filter-card,
    .repayment-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .filter-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .filter-actions {
        display: flex;
        gap: .5rem;
        align-items: end;
        flex-wrap: wrap;
    }

    .repayment-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding: .9rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .repayment-table-wrap {
        width: 100%;
        overflow-x: visible;
    }

    .repayment-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
    }

    .repayment-table th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        color: #475569;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .02em;
        padding: .75rem;
        text-transform: uppercase;
        white-space: normal;
    }

    .repayment-table td {
        border-color: #e5e7eb;
        color: #0f172a;
        padding: .75rem;
        vertical-align: middle;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .repayment-table tbody tr:hover {
        background: #f8fafc;
    }

    .repayment-table .date-col { width: 11%; }
    .repayment-table .loan-col { width: 22%; }
    .repayment-table .branch-col { width: 12%; }
    .repayment-table .amount-col { width: 15%; }
    .repayment-table .reference-col { width: 16%; }
    .repayment-table .staff-col { width: 16%; }
    .repayment-table .action-col { width: 8%; }

    .repayment-title {
        color: #0f172a;
        font-weight: 800;
        line-height: 1.25;
    }

    .repayment-subtext {
        color: #64748b;
        font-size: .78rem;
        line-height: 1.35;
    }

    .repayment-money {
        color: #047857;
        font-weight: 800;
        line-height: 1.25;
    }

    .method-pill,
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        width: fit-content;
        border-radius: 999px;
        padding: .3rem .55rem;
        font-size: .72rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .method-cash { background: #dcfce7; color: #166534; }
    .method-mobile { background: #fef3c7; color: #92400e; }
    .method-bank { background: #dbeafe; color: #1e40af; }
    .method-other { background: #e5e7eb; color: #374151; }

    .status-completed { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-failed { background: #fee2e2; color: #991b1b; }
    .status-unknown { background: #e5e7eb; color: #374151; }

    .pagination-wrap {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        padding: .9rem 1rem;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 1399.98px) {
        .kpi-strip {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .repayment-table .branch-col,
        .repayment-table td.branch-cell {
            display: none;
        }
    }

    @media (max-width: 767.98px) {
        .kpi-strip {
            grid-template-columns: 1fr;
        }

        .repayment-toolbar .btn,
        .filter-actions .btn {
            width: 100%;
        }

        .repayment-table-wrap {
            padding: .75rem;
        }

        .repayment-table,
        .repayment-table tbody,
        .repayment-table tr,
        .repayment-table td {
            display: block;
            width: 100%;
        }

        .repayment-table thead {
            display: none;
        }

        .repayment-table tr {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: .75rem;
            padding: .65rem .75rem;
        }

        .repayment-table td {
            border: 0;
            display: grid;
            grid-template-columns: 118px minmax(0, 1fr);
            gap: .75rem;
            padding: .35rem 0;
        }

        .repayment-table td::before {
            content: attr(data-label);
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }
    }
</style>
@endpush

@section('content')
@php
    $resetParams = array_filter(['type' => $loanType ?? request('type')]);
    $money = fn($value) => 'UGX ' . number_format((float) $value);
@endphp

<div class="container-fluid px-4 repayment-page">
    <div class="repayment-toolbar">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Loan Repayments</h1>
            <p class="text-muted mb-0">Filtered repayment records and collection KPIs</p>
        </div>
        <a href="{{ route('admin.repayments.create', $resetParams) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Record Repayment
        </a>
    </div>

    @if(session('info') || isset($info))
        <div class="alert alert-info">
            {{ session('info') ?? $info }}
        </div>
    @endif

    @if(isset($totals))
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h5 class="mb-0">Collection KPIs</h5>
            <span class="text-muted small">
                Period: {{ $kpiPeriodLabel ?? 'All matching repayments' }}
            </span>
        </div>
        <div class="kpi-strip">
            <div class="kpi-card">
                <span class="kpi-icon total"><i class="fas fa-money-bill-wave"></i></span>
                <div>
                    <div class="kpi-label">Total Collected</div>
                    <div class="kpi-value">{{ $money($totals['total_amount'] ?? 0) }}</div>
                    <div class="kpi-note">Successful repayments</div>
                </div>
            </div>
            <div class="kpi-card">
                <span class="kpi-icon principal"><i class="fas fa-coins"></i></span>
                <div>
                    <div class="kpi-label">Principal</div>
                    <div class="kpi-value">{{ $money($totals['total_principal'] ?? 0) }}</div>
                    <div class="kpi-note">Allocated from schedules</div>
                </div>
            </div>
            <div class="kpi-card">
                <span class="kpi-icon interest"><i class="fas fa-percentage"></i></span>
                <div>
                    <div class="kpi-label">Interest</div>
                    <div class="kpi-value">{{ $money($totals['total_interest'] ?? 0) }}</div>
                    <div class="kpi-note">Allocated before principal</div>
                </div>
            </div>
            <div class="kpi-card">
                <span class="kpi-icon penalty"><i class="fas fa-exclamation-triangle"></i></span>
                <div>
                    <div class="kpi-label">Penalties</div>
                    <div class="kpi-value">{{ $money($totals['total_penalty'] ?? 0) }}</div>
                    <div class="kpi-note">Late-fee portion paid</div>
                </div>
            </div>
            <div class="kpi-card">
                <span class="kpi-icon fees"><i class="fas fa-file-invoice-dollar"></i></span>
                <div>
                    <div class="kpi-label">Fees</div>
                    <div class="kpi-value">{{ $money($totals['total_fees'] ?? 0) }}</div>
                    <div class="kpi-note">Filtered paid fees</div>
                </div>
            </div>
            <div class="kpi-card">
                <span class="kpi-icon count"><i class="fas fa-list"></i></span>
                <div>
                    <div class="kpi-label">Transactions</div>
                    <div class="kpi-value">{{ number_format($totals['record_count'] ?? 0) }}</div>
                    <div class="kpi-note">Avg {{ $money($totals['average_payment'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="filter-card">
        <form method="GET" action="{{ route('admin.repayments.index') }}">
            @if($loanType ?? request('type'))
                <input type="hidden" name="type" value="{{ $loanType ?? request('type') }}">
            @endif
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Loan code, client, phone...">
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Branch</label>
                    <select class="form-select" name="branch_id">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="method">
                        <option value="">All methods</option>
                        <option value="1" {{ request('method') === '1' ? 'selected' : '' }}>Cash</option>
                        <option value="2" {{ request('method') === '2' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="3" {{ request('method') === '3' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">KPI Month</label>
                    <input type="month" class="form-control" name="kpi_month" value="{{ $kpiMonth ?? request('kpi_month') }}">
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button>
                    <a href="{{ route('admin.repayments.index', $resetParams) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt me-1"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="repayment-card">
        <div class="repayment-card-header">
            <div>
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave text-primary me-2"></i>Repayment Records
                </h5>
                <div class="text-muted small">
                    Showing {{ number_format($repayments->total()) }} matching records
                </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>

        <div class="repayment-table-wrap">
            <table class="table repayment-table">
                <thead>
                    <tr>
                        <th class="date-col">Date</th>
                        <th class="loan-col">Loan / Client</th>
                        <th class="branch-col">Branch</th>
                        <th class="amount-col">Amount / Method</th>
                        <th class="reference-col">Reference / Status</th>
                        <th class="staff-col">Processed By</th>
                        <th class="action-col">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repayments as $repayment)
                        @php
                            $repaymentDate = $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created) : null;
                            $method = match((int) $repayment->type) {
                                1 => ['class' => 'method-cash', 'label' => 'Cash', 'icon' => 'fa-money-bill'],
                                2 => ['class' => 'method-mobile', 'label' => 'Mobile Money', 'icon' => 'fa-mobile-alt'],
                                3 => ['class' => 'method-bank', 'label' => 'Bank Transfer', 'icon' => 'fa-university'],
                                default => ['class' => 'method-other', 'label' => 'Unknown', 'icon' => 'fa-question-circle'],
                            };
                            $status = match(true) {
                                (int) $repayment->status === 1 || $repayment->payment_status === 'Completed' => ['class' => 'status-completed', 'label' => 'Completed', 'icon' => 'fa-check-circle'],
                                $repayment->payment_status === 'Pending' => ['class' => 'status-pending', 'label' => 'Pending', 'icon' => 'fa-clock'],
                                $repayment->payment_status === 'Failed' => ['class' => 'status-failed', 'label' => 'Failed', 'icon' => 'fa-times-circle'],
                                default => ['class' => 'status-unknown', 'label' => 'Unknown', 'icon' => 'fa-question-circle'],
                            };
                            $borrowerName = trim(($repayment->loan->member->fname ?? '') . ' ' . ($repayment->loan->member->lname ?? ''));
                        @endphp
                        <tr>
                            <td data-label="Date">
                                <div class="repayment-title">{{ $repaymentDate ? $repaymentDate->format('M j, Y') : 'N/A' }}</div>
                                <div class="repayment-subtext">{{ $repaymentDate ? $repaymentDate->format('h:i A') : '' }}</div>
                            </td>
                            <td data-label="Loan / Client">
                                <a href="{{ route('admin.loans.show', $repayment->loan_id) }}" class="repayment-title text-decoration-none">
                                    {{ $repayment->loan->code ?? 'N/A' }}
                                </a>
                                <div class="repayment-subtext">
                                    {{ $borrowerName !== '' ? $borrowerName : 'Unknown client' }}
                                    @if($repayment->loan->member->code ?? false)
                                        / {{ $repayment->loan->member->code }}
                                    @endif
                                </div>
                            </td>
                            <td data-label="Branch" class="branch-cell">
                                {{ $repayment->loan->branch->name ?? 'No branch' }}
                            </td>
                            <td data-label="Amount / Method">
                                <div class="repayment-money mb-1">{{ $money($repayment->amount) }}</div>
                                <span class="method-pill {{ $method['class'] }}">
                                    <i class="fas {{ $method['icon'] }}"></i>{{ $method['label'] }}
                                </span>
                            </td>
                            <td data-label="Reference / Status">
                                <div class="repayment-title">{{ $repayment->transaction_reference ?? $repayment->txn_id ?? 'N/A' }}</div>
                                @if($repayment->details)
                                    <div class="repayment-subtext mb-1">{{ $repayment->details }}</div>
                                @endif
                                <span class="status-pill {{ $status['class'] }}">
                                    <i class="fas {{ $status['icon'] }}"></i>{{ $status['label'] }}
                                </span>
                            </td>
                            <td data-label="Processed By">
                                <div class="repayment-title">{{ $repayment->addedBy->name ?? 'System' }}</div>
                                <div class="repayment-subtext">{{ $repayment->addedBy->email ?? '' }}</div>
                            </td>
                            <td data-label="Receipt">
                                <a href="{{ route('admin.repayments.receipt', $repayment->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-receipt me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No repayments found</h5>
                                <p class="text-muted mb-0">Start by <a href="{{ route('admin.repayments.create', $resetParams) }}">recording a new repayment</a>.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($repayments->hasPages())
            <div class="pagination-wrap">
                <div class="text-muted">
                    Showing {{ $repayments->firstItem() ?? 0 }} to {{ $repayments->lastItem() ?? 0 }} of {{ $repayments->total() }} entries
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if ($repayments->onFirstPage())
                        <span class="btn btn-sm btn-outline-secondary disabled">
                            <i class="fas fa-chevron-left"></i> Previous
                        </span>
                    @else
                        <a href="{{ $repayments->previousPageUrl() }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    @endif

                    @php
                        $currentPage = $repayments->currentPage();
                        $lastPage = $repayments->lastPage();
                        $start = max(1, $currentPage - 2);
                        $end = min($lastPage, $currentPage + 2);

                        if ($currentPage <= 3) {
                            $end = min(5, $lastPage);
                        }
                        if ($currentPage >= $lastPage - 2) {
                            $start = max(1, $lastPage - 4);
                        }
                    @endphp

                    @if($start > 1)
                        <a href="{{ $repayments->url(1) }}" class="btn btn-sm btn-outline-primary">1</a>
                        @if($start > 2)
                            <span class="btn btn-sm btn-outline-secondary disabled">...</span>
                        @endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $currentPage)
                            <span class="btn btn-sm btn-primary">{{ $page }}</span>
                        @else
                            <a href="{{ $repayments->url($page) }}" class="btn btn-sm btn-outline-primary">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $lastPage)
                        @if($end < $lastPage - 1)
                            <span class="btn btn-sm btn-outline-secondary disabled">...</span>
                        @endif
                        <a href="{{ $repayments->url($lastPage) }}" class="btn btn-sm btn-outline-primary">{{ $lastPage }}</a>
                    @endif

                    @if ($repayments->hasMorePages())
                        <a href="{{ $repayments->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="btn btn-sm btn-outline-secondary disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
