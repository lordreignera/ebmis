@extends('layouts.admin')

@section('title', 'Fee Payments')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Fee Payments</h4>
                
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Fee Payments</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Total Fees</p>
                            <h4 class="mb-0">{{ number_format($stats['total_fees']) }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="bx bx-money font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Paid Fees</p>
                            <h4 class="mb-0 text-success">{{ number_format($stats['paid_fees']) }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                                <span class="avatar-title">
                                    <i class="bx bx-check-circle font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Pending Fees</p>
                            <h4 class="mb-0 text-warning">{{ number_format($stats['pending_fees']) }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title">
                                    <i class="bx bx-time-five font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Total Amount</p>
                            <h4 class="mb-0">UGX {{ number_format($stats['total_amount']) }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="bx bx-wallet font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-0">Fee Payment Records</h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="{{ route('admin.fees.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-1"></i> Record Fee Payment
                            </a>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <form method="GET" action="{{ route('admin.fees.index') }}" class="row g-3">
                            <div class="col-md-2">
                                <select name="fee_type" class="form-select">
                                    <option value="">All Fee Types</option>
                                    @foreach($feeTypes as $feeType)
                                        <option value="{{ $feeType->id }}" {{ request('fee_type') == $feeType->id ? 'selected' : '' }}>
                                            {{ $feeType->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Paid</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="payment_type" class="form-select">
                                    <option value="">All Payment Types</option>
                                    <option value="1" {{ request('payment_type') === '1' ? 'selected' : '' }}>Mobile Money</option>
                                    <option value="2" {{ request('payment_type') === '2' ? 'selected' : '' }}>Cash</option>
                                    <option value="3" {{ request('payment_type') === '3' ? 'selected' : '' }}>Bank</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by member name or code..." 
                                           value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                    <a href="{{ route('admin.fees.index') }}" class="btn btn-outline-secondary">
                                        <i class="mdi mdi-refresh"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fees Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Payment Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fees as $fee)
                                    <tr>
                                        <td>{{ $fee->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <h6 class="mb-0">{{ $fee->member->fname }} {{ $fee->member->lname }}</h6>
                                                    <small class="text-muted">{{ $fee->member->code }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $fee->feeType->name }}</span>
                                        </td>
                                        <td>
                                            <strong>UGX {{ number_format($fee->amount) }}</strong>
                                        </td>
                                        <td>
                                            @switch($fee->payment_type)
                                                @case(1)
                                                    <span class="badge bg-primary">Mobile Money</span>
                                                    @break
                                                @case(2)
                                                    <span class="badge bg-success">Cash</span>
                                                    @break
                                                @case(3)
                                                    <span class="badge bg-warning">Bank</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">Unknown</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($fee->status == 1)
                                                <span class="badge bg-success">Paid</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $fee->datecreated ? $fee->datecreated->format('M d, Y g:i A') : 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewFeeDetails({{ $fee->id }})" title="View Details">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                @if($fee->status == 0)
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="markAsPaid({{ $fee->id }})" title="Mark as Paid">
                                                        <i class="mdi mdi-check"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="mdi mdi-credit-card-off mdi-48px text-muted"></i>
                                            <br>No fee payments found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($fees->hasPages())
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-center">
                                    {{ $fees->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fee Details Modal -->
<div class="modal fade" id="feeDetailsModal" tabindex="-1" role="dialog" aria-labelledby="feeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feeDetailsModalLabel">Fee Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="feeDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function viewFeeDetails(feeId) {
    $('#feeDetailsModal').modal('show');
    
    fetch(`{{ url('admin/fees') }}/${feeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('feeDetailsContent').innerHTML = data.html;
            } else {
                document.getElementById('feeDetailsContent').innerHTML = 
                    '<div class="alert alert-danger">Error loading fee details</div>';
            }
        })
        .catch(error => {
            document.getElementById('feeDetailsContent').innerHTML = 
                '<div class="alert alert-danger">Error loading fee details</div>';
        });
}

function markAsPaid(feeId) {
    if (confirm('Are you sure you want to mark this fee as paid?')) {
        fetch(`{{ url('admin/fees') }}/${feeId}/mark-paid`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error marking fee as paid');
            }
        })
        .catch(error => {
            alert('Error marking fee as paid');
        });
    }
}
</script>
@endsection