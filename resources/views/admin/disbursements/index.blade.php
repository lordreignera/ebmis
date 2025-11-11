@extends('layouts.admin')

@section('title', 'Disbursements Management')

@section('content')
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
                        <select name="method" class="form-control">
                            <option value="">All Methods</option>
                            <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank" {{ request('method') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="mobile_money" {{ request('method') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
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
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Disbursements List</h4>
                    <span class="text-muted">{{ $disbursements->total() }} total disbursements</span>
                </div>
                
                @if($disbursements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
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
                                        <span class="font-weight-bold">UGX {{ number_format($disbursement->amount) }}</span>
                                        @if($disbursement->fees > 0)
                                            <br><small class="text-muted">Fees: UGX {{ number_format($disbursement->fees) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($disbursement->payment_type)
                                        @case(1)
                                            <span class="badge badge-success">
                                                <i class="mdi mdi-cellphone"></i> Mobile Money
                                            </span>
                                            @break
                                        @case(2)
                                            <span class="badge badge-info">
                                                <i class="mdi mdi-bank"></i> Bank Transfer/Cheque
                                            </span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">-</span>
                                    @endswitch
                                </td>
                                <td>
                                    @switch($disbursement->status)
                                        @case(0)
                                            <span class="badge badge-warning">
                                                <i class="mdi mdi-clock-outline"></i> Pending
                                            </span>
                                            @break
                                        @case(1)
                                            <span class="badge badge-info">
                                                <i class="mdi mdi-check"></i> Approved
                                            </span>
                                            @break
                                        @case(2)
                                            <span class="badge badge-success">
                                                <i class="mdi mdi-check-circle"></i> Disbursed
                                            </span>
                                            @break
                                        @case(3)
                                            <span class="badge badge-danger">
                                                <i class="mdi mdi-close-circle"></i> Failed
                                            </span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">Unknown</span>
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
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted">
                            Showing {{ $disbursements->firstItem() }} to {{ $disbursements->lastItem() }} of {{ $disbursements->total() }} results
                        </span>
                    </div>
                    <div>
                        {{ $disbursements->appends(request()->query())->links() }}
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
    $('select[name="status"], select[name="method"]').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
@endsection