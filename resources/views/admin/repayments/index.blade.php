@extends('layouts.admin')

@section('title', 'Loan Repayments')

@push('css')
<link href="{{ asset('css/enhanced-tables.css') }}" rel="stylesheet">
<style>
    .filters-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }
    
    .stats-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    
    .stats-card .card-body {
        padding: 1.5rem;
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .stats-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
    }
    
    .stats-label {
        color: #718096;
        font-size: 0.9rem;
        font-weight: 500;
        margin: 0;
    }
    
    .bg-success { background: linear-gradient(135deg, #48bb78, #38a169); }
    .bg-info { background: linear-gradient(135deg, #4299e1, #3182ce); }
    .bg-warning { background: linear-gradient(135deg, #ed8936, #dd6b20); }
    .bg-danger { background: linear-gradient(135deg, #f56565, #e53e3e); }
    .bg-primary { background: linear-gradient(135deg, #667eea, #764ba2); }
    
    .payment-method-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .method-cash { background: #e6fffa; color: #047857; }
    .method-mobile { background: #fef3e2; color: #92400e; }
    .method-bank { background: #e0f2fe; color: #0c4a6e; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Loan Repayments</h1>
            <p class="text-muted mb-0">Track and manage loan repayment records</p>
        </div>
        <a href="{{ route('admin.repayments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Record New Repayment
        </a>
    </div>

    <!-- Statistics Cards -->
    @if(isset($totals))
    <div class="row mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon bg-success mx-auto">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h4 class="stats-value">UGX {{ number_format($totals['total_amount']) }}</h4>
                    <p class="stats-label">Total Collected</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon bg-info mx-auto">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h4 class="stats-value">UGX {{ number_format($totals['total_principal']) }}</h4>
                    <p class="stats-label">Principal</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon bg-warning mx-auto">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <h4 class="stats-value">UGX {{ number_format($totals['total_interest']) }}</h4>
                    <p class="stats-label">Interest</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon bg-danger mx-auto">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="stats-value">UGX {{ number_format($totals['total_penalty']) }}</h4>
                    <p class="stats-label">Penalties</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon bg-primary mx-auto">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h4 class="stats-value">UGX {{ number_format($totals['total_fees']) }}</h4>
                    <p class="stats-label">Fees</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon bg-success mx-auto">
                        <i class="fas fa-list"></i>
                    </div>
                    <h4 class="stats-value">{{ $repayments->total() }}</h4>
                    <p class="stats-label">Total Records</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="card filters-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.repayments.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-white">Search</label>
                        <input type="text" class="form-control" name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Search loans, members...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-white">Start Date</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-white">End Date</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-white">Payment Method</label>
                        <select class="form-control" name="method">
                            <option value="">All Methods</option>
                            <option value="1" {{ request('method') == '1' ? 'selected' : '' }}>Cash</option>
                            <option value="2" {{ request('method') == '2' ? 'selected' : '' }}>Mobile Money</option>
                            <option value="3" {{ request('method') == '3' ? 'selected' : '' }}>Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-light me-2">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('admin.repayments.index') }}" class="btn btn-outline-light">
                            <i class="fas fa-sync-alt me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Repayments Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-gray-800">
                    <i class="fas fa-money-bill-wave text-primary me-2"></i>
                    Repayment Records
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table enhanced-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Loan Code</th>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Processed By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($repayments as $repayment)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('M j, Y') : 'N/A' }}</div>
                                <small class="text-muted">{{ $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('h:i A') : '' }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.loans.show', $repayment->loan_id) }}" class="text-decoration-none">
                                    <strong>{{ $repayment->loan->code ?? 'N/A' }}</strong>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-soft-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $repayment->loan->member->fname ?? '' }} {{ $repayment->loan->member->lname ?? '' }}</div>
                                        <small class="text-muted">{{ $repayment->loan->member->code ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-success">UGX {{ number_format($repayment->amount) }}</span>
                            </td>
                            <td>
                                @php
                                    $methodClass = match($repayment->type) {
                                        1 => 'method-cash',
                                        2 => 'method-mobile', 
                                        3 => 'method-bank',
                                        default => 'method-cash'
                                    };
                                    $methodName = match($repayment->type) {
                                        1 => 'Cash',
                                        2 => 'Mobile Money',
                                        3 => 'Bank Transfer',
                                        default => 'Unknown'
                                    };
                                @endphp
                                <span class="badge payment-method-badge {{ $methodClass }}">
                                    {{ $methodName }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $repayment->transaction_reference ?? $repayment->txn_id ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs bg-soft-info rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="fas fa-user-tie text-info" style="font-size: 0.75rem;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.875rem;">{{ $repayment->addedBy->name ?? 'System' }}</div>
                                        <small class="text-muted" style="font-size: 0.75rem;">{{ $repayment->addedBy->email ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($repayment->status == 1 || $repayment->payment_status == 'Completed')
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Completed
                                    </span>
                                @elseif($repayment->payment_status == 'Pending')
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                @elseif($repayment->payment_status == 'Failed')
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i>Failed
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Unknown</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.repayments.receipt', $repayment->id) }}" 
                                   class="btn btn-primary btn-sm" 
                                   target="_blank"
                                   title="View Receipt">
                                    <i class="fas fa-receipt me-1"></i>Receipt
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No repayments found</h5>
                                    <p class="text-muted">Start by <a href="{{ route('admin.repayments.create') }}">recording a new repayment</a></p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($repayments->hasPages())
        <div class="card-footer bg-white py-3">
            <!-- Modern Pagination -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $repayments->firstItem() ?? 0 }} to {{ $repayments->lastItem() ?? 0 }} of {{ $repayments->total() }} entries
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if ($repayments->onFirstPage())
                        <span class="btn btn-sm btn-outline-secondary disabled">
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </span>
                    @else
                        <a href="{{ $repayments->previousPageUrl() }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </a>
                    @endif

                    <div class="d-flex gap-1">
                        @php
                            $currentPage = $repayments->currentPage();
                            $lastPage = $repayments->lastPage();
                            $start = max(1, $currentPage - 2);
                            $end = min($lastPage, $currentPage + 2);
                            
                            // Adjust if at the beginning or end
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
                    </div>

                    @if ($repayments->hasMorePages())
                        <a href="{{ $repayments->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="btn btn-sm btn-outline-secondary disabled">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function printReceipt(repaymentId) {
    window.open(`/admin/repayments/${repaymentId}/receipt`, '_blank', 'width=800,height=600');
}
</script>
@endpush
@endsection