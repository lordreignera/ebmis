@extends('layouts.admin')

@section('title', 'Disbursements Management')

@push('styles')
<style>
    .disbursements-page .table-container {
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .disbursements-page .table-header {
        align-items: center;
    }

    .disbursements-page .table-title {
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: .15rem;
    }

    .disbursements-page .table-subtitle {
        color: #64748b;
        font-size: .82rem;
    }

    .disbursements-page .modern-table td {
        white-space: normal;
    }

    .disbursement-money {
        color: #047857;
        font-weight: 800;
    }

    .status-failed {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .status-approved {
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }

    .status-unknown {
        background: #e5e7eb;
        border: 1px solid #d1d5db;
        color: #374151;
    }

    .method-mobile {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .method-bank {
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }

    .method-unknown {
        background: #e5e7eb;
        border: 1px solid #d1d5db;
        color: #374151;
    }

    @media (max-width: 767.98px) {
        .disbursements-page .pagination-controls {
            flex-wrap: wrap;
        }

        .disbursements-page .pagination-numbers {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="disbursements-page">
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Disbursements Management</h4>
            </div>
            <div>
                <a href="{{ route('admin.disbursements.create') }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-plus"></i> New Disbursement
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Total Disbursements</h4>
                        <h2 class="text-primary mb-2">{{ number_format($stats['total_disbursements'] ?? 0) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-cash-multiple icon-lg text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Amount Disbursed</h4>
                        <h2 class="text-success mb-2">{{ number_format($stats['total_amount'] ?? 0) }}</h2>
                        <small class="text-muted">UGX</small>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-bank icon-lg text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Today's Disbursements</h4>
                        <h2 class="text-info mb-2">{{ number_format($stats['today_disbursements'] ?? 0) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-calendar-today icon-lg text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Pending Disbursements</h4>
                        <h2 class="text-warning mb-2">{{ number_format($stats['pending_disbursements'] ?? 0) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-clock-outline icon-lg text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Alert for Approved Loans -->
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="alert alert-info d-flex align-items-center" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196f3; border-radius: 12px;">
            <i class="mdi mdi-information-outline me-3" style="font-size: 2rem; color: #1976d2;"></i>
            <div class="flex-grow-1">
                <h5 class="mb-1" style="color: #1565c0; font-weight: 600;">Looking for Approved Loans Ready to Disburse?</h5>
                <p class="mb-0" style="color: #424242;">This page shows <strong>disbursement records</strong> (completed transactions). To see <strong>approved loans awaiting disbursement</strong>, click the button below.</p>
            </div>
            <a href="{{ route('admin.loans.disbursements.pending') }}" class="btn btn-primary" style="white-space: nowrap;">
                <i class="mdi mdi-cash-check me-1"></i> View Loans Ready to Disburse
            </a>
        </div>
    </div>
</div>

<!-- Quick Action Tabs -->
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ !request('status') ? 'active' : '' }}" 
                           href="{{ route('admin.disbursements.index') }}">
                            All Disbursements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'pending' ? 'active' : '' }}" 
                           href="{{ route('admin.disbursements.index') }}?status=pending">
                            Pending ({{ $stats['pending_disbursements'] ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'completed' ? 'active' : '' }}" 
                           href="{{ route('admin.disbursements.index') }}?status=completed">
                            Completed ({{ $stats['completed_disbursements'] ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'failed' ? 'active' : '' }}" 
                           href="{{ route('admin.disbursements.index') }}?status=failed">
                            Failed ({{ $stats['failed_disbursements'] ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('type') === 'group' ? 'active' : '' }}" 
                           href="{{ route('admin.disbursements.index') }}?type=group">
                            Group Disbursements
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filter Disbursements</h4>
                <form method="GET" action="{{ route('admin.disbursements.index') }}" class="row">
                    <div class="col-md-3">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by loan ID, member name..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="payment_type" class="form-control">
                            <option value="">All Methods</option>
                            <option value="1" {{ request('payment_type') == '1' ? 'selected' : '' }}>Mobile Money</option>
                            <option value="3" {{ request('payment_type') == '3' ? 'selected' : '' }}>Mobile Money (Legacy)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="start_date" 
                               class="form-control" 
                               placeholder="Start Date"
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="end_date" 
                               class="form-control" 
                               placeholder="End Date"
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Disbursements Table -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="table-container">
            <div class="table-header">
                <div>
                    <div class="table-title">Disbursements List</div>
                    <div class="table-subtitle">{{ number_format($disbursements->total()) }} total disbursements</div>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.loans.disbursements.pending') }}" class="export-btn">
                        <i class="mdi mdi-cash-check"></i>
                        Ready to Disburse
                    </a>
                </div>
            </div>
                
                @if($disbursements->count() > 0)
                <div class="table-responsive">
                    <table class="modern-table table-hover">
                        <thead>
                            <tr>
                                <th>Disbursement ID</th>
                                <th>Loan Details</th>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($disbursements as $disbursement)
                            <tr>
                                <td>
                                    <span class="font-weight-bold">{{ $disbursement->disbursement_id ?? 'DISB-' . str_pad($disbursement->id, 6, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    @if($disbursement->loan)
                                        <div>
                                            <span class="font-weight-bold">{{ $disbursement->loan->code }}</span>
                                            <br><small class="text-muted">{{ $disbursement->loan->product->name ?? 'Standard Loan' }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">Loan not found</span>
                                    @endif
                                </td>
                                <td>
                                    @if($disbursement->loan)
                                        @if($disbursement->loan_type == 1 && $disbursement->loan->member)
                                            {{-- Personal Loan --}}
                                            <div>
                                                <span>{{ $disbursement->loan->member->fname }} {{ $disbursement->loan->member->lname }}</span>
                                                <br><small class="text-muted">{{ $disbursement->loan->member->code }}</small>
                                            </div>
                                        @elseif($disbursement->loan_type == 2 && $disbursement->loan->group)
                                            {{-- Group Loan --}}
                                            <div>
                                                <span>{{ $disbursement->loan->group->name }}</span>
                                                <br><small class="text-muted">Group Loan</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Member not found</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Member not found</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                            <span class="disbursement-money">UGX {{ number_format($disbursement->amount) }}</span>
                                        @if($disbursement->fees > 0)
                                            <br><small class="text-muted">Fees: UGX {{ number_format($disbursement->fees) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($disbursement->payment_type)
                                        @case(1)
                                            <span class="status-badge method-mobile">
                                                <i class="mdi mdi-cellphone"></i> Mobile Money
                                            </span>
                                            @break
                                        @case(2)
                                            <span class="status-badge method-bank">
                                                <i class="mdi mdi-bank"></i> Bank/Cheque (Historical)
                                            </span>
                                            @break
                                        @case(3)
                                            <span class="status-badge method-mobile">
                                                <i class="mdi mdi-cellphone"></i> Mobile Money (Legacy)
                                            </span>
                                            @break
                                        @default
                                            <span class="status-badge method-unknown">-</span>
                                    @endswitch
                                </td>
                                <td>
                                    @switch($disbursement->status)
                                        @case(0)
                                            <span class="status-badge status-pending">
                                                <i class="mdi mdi-clock-outline"></i> Pending
                                            </span>
                                            @break
                                        @case(1)
                                            <span class="status-badge status-approved">
                                                <i class="mdi mdi-check"></i> Approved
                                            </span>
                                            @break
                                        @case(2)
                                            <span class="status-badge status-disbursed">
                                                <i class="mdi mdi-check-circle"></i> Disbursed
                                            </span>
                                            @break
                                        @case(3)
                                            <span class="status-badge status-failed">
                                                <i class="mdi mdi-close-circle"></i> Failed
                                            </span>
                                            @break
                                        @default
                                            <span class="status-badge status-unknown">Unknown</span>
                                    @endswitch
                                </td>
                                <td>
                                    <span>{{ $disbursement->created_at->format('M d, Y') }}</span>
                                    <br><small class="text-muted">{{ $disbursement->created_at->format('g:i A') }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.disbursements.show', $disbursement->id) }}">
                                                <i class="mdi mdi-eye"></i> View Details
                                            </a>
                                            @if($disbursement->status == 0)
                                                <form action="{{ route('admin.disbursements.complete', $disbursement->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success" 
                                                            onclick="return confirm('Mark this disbursement as completed?')">
                                                        <i class="mdi mdi-check"></i> Mark Complete
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.disbursements.cancel', $disbursement->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger" 
                                                            onclick="return confirm('Cancel this disbursement?')">
                                                        <i class="mdi mdi-close"></i> Cancel
                                                    </button>
                                                </form>
                                            @endif
                                            @if($disbursement->status == 3)
                                                <form action="{{ route('admin.disbursements.retry', $disbursement->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning" 
                                                            onclick="return confirm('Retry this disbursement?')">
                                                        <i class="mdi mdi-refresh"></i> Retry
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="modern-pagination">
                    <div class="pagination-info">
                        Showing {{ $disbursements->firstItem() ?? 0 }} to {{ $disbursements->lastItem() ?? 0 }} of {{ $disbursements->total() }} entries
                    </div>
                    <div class="pagination-controls">
                        @php
                            $pagedDisbursements = $disbursements->appends(request()->query());
                        @endphp

                        @if($pagedDisbursements->hasPages())
                            @if ($pagedDisbursements->onFirstPage())
                                <span class="pagination-btn" disabled>
                                    <i class="mdi mdi-chevron-left"></i>
                                    Previous
                                </span>
                            @else
                                <a href="{{ $pagedDisbursements->previousPageUrl() }}" class="pagination-btn">
                                    <i class="mdi mdi-chevron-left"></i>
                                    Previous
                                </a>
                            @endif

                            <div class="pagination-numbers">
                                @php
                                    $currentPage = $pagedDisbursements->currentPage();
                                    $lastPage = $pagedDisbursements->lastPage();
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
                                    <a href="{{ $pagedDisbursements->url(1) }}" class="pagination-btn">1</a>
                                    @if($start > 2)
                                        <span class="pagination-btn" disabled>...</span>
                                    @endif
                                @endif

                                @for ($page = $start; $page <= $end; $page++)
                                    @if ($page === $currentPage)
                                        <span class="pagination-btn active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $pagedDisbursements->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                    @endif
                                @endfor

                                @if($end < $lastPage)
                                    @if($end < $lastPage - 1)
                                        <span class="pagination-btn" disabled>...</span>
                                    @endif
                                    <a href="{{ $pagedDisbursements->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                @endif
                            </div>

                            @if ($pagedDisbursements->hasMorePages())
                                <a href="{{ $pagedDisbursements->nextPageUrl() }}" class="pagination-btn">
                                    Next
                                    <i class="mdi mdi-chevron-right"></i>
                                </a>
                            @else
                                <span class="pagination-btn" disabled>
                                    Next
                                    <i class="mdi mdi-chevron-right"></i>
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-cash-multiple" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No disbursements found</h5>
                    <p class="text-muted">Start by creating your first disbursement</p>
                    <a href="{{ route('admin.disbursements.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> New Disbursement
                    </a>
                </div>
                @endif
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('select[name="status"], select[name="payment_type"]').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
@endsection
