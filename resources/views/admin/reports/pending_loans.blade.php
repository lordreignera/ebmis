@extends('layouts.admin')

@section('title', 'Pending Loan Applications Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Pending Loan Applications Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Pending Loans</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-primary text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-clock-outline widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Total Pending">Total Pending</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['total_pending']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-info text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-currency-usd widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Pending Amount">Pending Amount</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['total_amount']) }} UGX</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-warning text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-calendar-today widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Today's Applications">Today's Applications</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['today_applications']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-success text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-calendar-week widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="This Week">This Week</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['this_week']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter & Search</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.pending-loans') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search by loan ID, member name...">
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach(\App\Models\Branch::all() as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id">
                                <option value="">All Products</option>
                                @foreach(\App\Models\Product::loanProducts()->get() as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="mdi mdi-magnify"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Loan Applications ({{ isset($data['loans']) ? count($data['loans']) : 0 }} records)</h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Loan ID</th>
                                    <th>Member</th>
                                    <th>Product</th>
                                    <th>Branch</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Applied Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($data['loans']) && count($data['loans']) > 0)
                                @foreach($data['loans'] as $loan)
                                <tr>
                                    <td>
                                        <strong>{{ $loan->loan_id }}</strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $loan->member->first_name }} {{ $loan->member->last_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $loan->member->member_id }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                    <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                                    <td>
                                        <strong>{{ number_format($loan->loan_amount) }} UGX</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ ucfirst($loan->status) }}</span>
                                    </td>
                                    <td>{{ $loan->created_at->format('d M Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('loans.edit', $loan->id) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if($loan->status == 'pending')
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="approveLoan({{ $loan->id }})" title="Approve">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="rejectLoan({{ $loan->id }})" title="Reject">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No pending loan applications found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        @if(isset($data['loans']) && count($data['loans']) > 0)
                        <div>
                            <small class="text-muted">
                                Showing {{ count($data['loans']) }} pending loans
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this loan application?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprove">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejection_reason" class="form-label">Rejection Reason</label>
                    <textarea class="form-control" id="rejection_reason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentLoanId = null;

function approveLoan(loanId) {
    currentLoanId = loanId;
    $('#approveModal').modal('show');
}

function rejectLoan(loanId) {
    currentLoanId = loanId;
    $('#rejectModal').modal('show');
}

$('#confirmApprove').click(function() {
    if (currentLoanId) {
        // Here you would make an AJAX call to approve the loan
        $.post(`/admin/loans/${currentLoanId}/approve`, {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            location.reload();
        });
    }
});

$('#confirmReject').click(function() {
    const reason = $('#rejection_reason').val();
    if (currentLoanId && reason) {
        // Here you would make an AJAX call to reject the loan
        $.post(`/admin/loans/${currentLoanId}/reject`, {
            _token: '{{ csrf_token() }}',
            reason: reason
        }).done(function() {
            location.reload();
        });
    }
});

function exportToExcel() {
    window.open('{{ route("admin.reports.pending-loans") }}?{{ request()->getQueryString() }}&export=excel', '_blank');
}

function exportToPDF() {
    window.open('{{ route("admin.reports.pending-loans") }}?{{ request()->getQueryString() }}&export=pdf', '_blank');
}
</script>
@endpush