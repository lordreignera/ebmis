@extends('layouts.admin')

@section('title', ucfirst($loanType ?? 'All') . ' Loans' . (isset($repayPeriod) && $repayPeriod !== 'all' ? ' (' . ucfirst($repayPeriod) . ')' : ''))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-cash-multiple"></i> 
                        {{ ucfirst($loanType ?? 'All') }} Loan Portfolio
                        @if(isset($repayPeriod) && $repayPeriod !== 'all')
                            <span class="badge bg-info">{{ ucfirst($repayPeriod) }}</span>
                        @endif
                    </h3>
                    <a href="{{ route('admin.loans.create') }}@if(isset($loanType) && isset($repayPeriod))?type={{ $loanType }}&period={{ $repayPeriod }}@endif" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create New {{ ucfirst($loanType ?? '') }} Loan
                    </a>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['total'] ?? 0 }}</h4>
                                            <small>Total Loans</small>
                                        </div>
                                        <i class="mdi mdi-cash-multiple mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['pending'] ?? 0 }}</h4>
                                            <small>Pending</small>
                                        </div>
                                        <i class="mdi mdi-clock mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['approved'] ?? 0 }}</h4>
                                            <small>Approved</small>
                                        </div>
                                        <i class="mdi mdi-check-circle mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['disbursed'] ?? 0 }}</h4>
                                            <small>Disbursed</small>
                                        </div>
                                        <i class="mdi mdi-cash-usd mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['completed'] ?? 0 }}</h4>
                                            <small>Completed</small>
                                        </div>
                                        <i class="mdi mdi-check-all mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">UGX {{ number_format($stats['total_value'] ?? 0, 0) }}</h4>
                                            <small>Total Value</small>
                                        </div>
                                        <i class="mdi mdi-currency-usd mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Tabs -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <ul class="nav nav-pills nav-fill">
                                        <li class="nav-item">
                                            <a class="nav-link {{ !request('filter') ? 'active' : '' }}" 
                                               href="{{ route('admin.loans.index') }}">
                                                <i class="mdi mdi-view-list"></i> All Loans
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request('filter') === 'pending' ? 'active' : '' }}" 
                                               href="{{ route('admin.loans.index') }}?filter=pending">
                                                <i class="mdi mdi-clock"></i> Pending Approvals
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request('filter') === 'approved' ? 'active' : '' }}" 
                                               href="{{ route('admin.loans.index') }}?filter=approved">
                                                <i class="mdi mdi-check-circle"></i> Approved Loans
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request('filter') === 'disbursed' ? 'active' : '' }}" 
                                               href="{{ route('admin.loans.index') }}?filter=disbursed">
                                                <i class="mdi mdi-cash-usd"></i> Disbursed Loans
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request('filter') === 'due' ? 'active' : '' }}" 
                                               href="{{ route('admin.loans.index') }}?filter=due">
                                                <i class="mdi mdi-alert-circle"></i> Due Loans
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request('filter') === 'overdue' ? 'active' : '' }}" 
                                               href="{{ route('admin.loans.index') }}?filter=overdue">
                                                <i class="mdi mdi-alert"></i> Overdue Loans
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" action="{{ route('admin.loans.index') }}" class="row g-3">
                                        <div class="col-md-2">
                                            <select name="product_id" class="form-select">
                                                <option value="">All Products</option>
                                                @foreach(\App\Models\Product::loanProducts()->active()->get() as $product)
                                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="branch_id" class="form-select">
                                                <option value="">All Branches</option>
                                                @foreach(\App\Models\Branch::active()->get() as $branch)
                                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="status" class="form-select">
                                                <option value="">All Status</option>
                                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Approved</option>
                                                <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Disbursed</option>
                                                <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="date" name="date_from" class="form-control" 
                                                   placeholder="Date From" value="{{ request('date_from') }}">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="date" name="date_to" class="form-control" 
                                                   placeholder="Date To" value="{{ request('date_to') }}">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" 
                                                       placeholder="Search loans..." value="{{ request('search') }}">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="mdi mdi-magnify"></i>
                                                </button>
                                                <a href="{{ route('admin.loans.index') }}" class="btn btn-outline-secondary">
                                                    <i class="mdi mdi-refresh"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loans Table -->
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-search">
                                <input type="text" placeholder="Search loans..." id="quickSearch">
                            </div>
                            <div class="table-actions">
                                <div class="table-show-entries">
                                    Show 
                                    <select onchange="window.location.href='{{ url()->current() }}?per_page='+this.value">
                                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    entries
                                </div>
                                <a href="{{ route('admin.loans.create') }}@if(isset($loanType) && isset($repayPeriod))?type={{ $loanType }}&period={{ $repayPeriod }}@endif" class="export-btn">
                                    <i class="mdi mdi-plus"></i> New Loan
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table table-hover">
                            <thead>
                                <tr>
                                    <th>Loan Code</th>
                                    <th>Member</th>
                                    <th>Product</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loans as $loan)
                                    <tr>
                                        <td>
                                            <span class="account-number">{{ $loan->code ?? 'LN-' . str_pad($loan->id, 6, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($loan->member->pp_file)
                                                    <img src="{{ Storage::url($loan->member->pp_file) }}" 
                                                         class="rounded-circle me-2" width="32" height="32" alt="Photo">
                                                @else
                                                    <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 32px; height: 32px;">
                                                        <span class="text-white fw-bold">{{ substr($loan->member->fname, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-medium">{{ $loan->member->fname }} {{ $loan->member->lname }}</div>
                                                    <small class="text-muted">{{ $loan->member->contact }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                        <td><span class="fw-semibold">UGX {{ number_format($loan->principal, 2) }}</span></td>
                                        <td>{{ $loan->interest }}%</td>
                                        <td>{{ $loan->period }} {{ $loan->period_type ?? 'days' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($loan->status) {
                                                    0 => 'status-pending',
                                                    1 => 'status-approved',
                                                    2 => 'status-disbursed',
                                                    3 => 'status-verified',
                                                    default => 'status-not-verified'
                                                };
                                                $statusText = match($loan->status) {
                                                    0 => 'Pending',
                                                    1 => 'Approved',
                                                    2 => 'Disbursed',
                                                    3 => 'Completed',
                                                    default => 'Unknown'
                                                };
                                            @endphp
                                            <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                            
                                            @if($loan->member->status !== 'approved')
                                                <br><small class="text-danger">
                                                    <i class="mdi mdi-alert-circle"></i> Member not approved
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_string($loan->created_at))
                                                {{ \Carbon\Carbon::parse($loan->created_at)->format('M d, Y') }}
                                            @else
                                                {{ $loan->created_at->format('M d, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.loans.show', $loan) }}" 
                                                   class="btn-modern btn-view" title="View Details">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                @if($loan->status == 0)
                                                    <a href="{{ route('admin.loans.edit', $loan) }}" 
                                                       class="btn-modern btn-warning" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn-modern btn-process" 
                                                            onclick="approveLoan({{ $loan->id }})" title="Approve">
                                                        <i class="mdi mdi-check"></i>
                                                    </button>
                                                    <button type="button" class="btn-modern btn-delete" 
                                                            onclick="rejectLoan({{ $loan->id }})" title="Reject">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                @endif
                                                @if($loan->status == 1 && $loan->member->isApproved())
                                                    @if(auth()->user()->hasRole('Super Administrator') || auth()->user()->hasRole('superadmin'))
                                                        <a href="{{ route('admin.disbursements.create', ['loan_id' => $loan->id]) }}" 
                                                           class="btn-modern btn-process" title="Disburse">
                                                            <i class="mdi mdi-cash-usd"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="mdi mdi-cash-remove mdi-48px text-muted"></i>
                                            <br>No loans found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                        @if($loans->hasPages())
                        <div class="modern-pagination">
                            <div class="pagination-info">
                                @if($loans->total() > 0)
                                    Showing {{ $loans->firstItem() ?? 1 }} to {{ $loans->lastItem() ?? $loans->count() }} of {{ $loans->total() }} entries
                                @else
                                    No entries found
                                @endif
                            </div>
                            <div class="pagination-controls">
                                @if ($loans->onFirstPage())
                                    <span class="pagination-btn" disabled>Previous</span>
                                @else
                                    <a class="pagination-btn" href="{{ $loans->previousPageUrl() }}">Previous</a>
                                @endif

                                <div class="pagination-numbers">
                                    @php
                                        $currentPage = $loans->currentPage();
                                        $lastPage = $loans->lastPage();
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
                                        <a href="{{ $loans->url(1) }}" class="pagination-btn">1</a>
                                        @if($start > 2)
                                            <span class="pagination-btn" disabled>...</span>
                                        @endif
                                    @endif

                                    @for ($page = $start; $page <= $end; $page++)
                                        @if ($page == $currentPage)
                                            <span class="pagination-btn active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $loans->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    @if($end < $lastPage)
                                        @if($end < $lastPage - 1)
                                            <span class="pagination-btn" disabled>...</span>
                                        @endif
                                        <a href="{{ $loans->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                    @endif
                                </div>

                                @if ($loans->hasMorePages())
                                    <a class="pagination-btn" href="{{ $loans->nextPageUrl() }}">Next</a>
                                @else
                                    <span class="pagination-btn" disabled>Next</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3"
                                  placeholder="Enter approval notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"
                                  placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approveLoan(loanId) {
    document.getElementById('approvalForm').action = `/admin/loans/${loanId}/approve`;
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function rejectLoan(loanId) {
    document.getElementById('rejectionForm').action = `/admin/loans/${loanId}/reject`;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}
</script>
@endpush
@endsection