@extends('layouts.admin')

@section('title', 'Running Loans Portfolio')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Running Loans Portfolio</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.loans.export', ['status' => 'running']) }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export
            </a>
            <a href="{{ route('admin.loans.create') }}" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> New Loan
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Running Loans
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_loans']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-file-document-outline fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Disbursed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['total_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-cash-multiple fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Outstanding Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['outstanding_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-clock-alert-outline fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Payments Received
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['paid_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-check-circle-outline fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-filter-variant"></i> Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.portfolio.running') }}" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="Loan ID, Member name...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               value="{{ request('start_date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="branch_id">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-control">
                            <option value="">All Branches</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="product_id">Product</label>
                        <select name="product_id" id="product_id" class="form-control">
                            <option value="">All Products</option>
                            @foreach(\App\Models\Product::loanProducts()->get() as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <a href="{{ route('admin.portfolio.running') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-format-list-bulleted"></i> Running Loans
                <span class="badge badge-primary ml-2">{{ $loans->total() }}</span>
            </h6>
        </div>
        <div class="card-body">
            @if($loans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Loan ID</th>
                                <th>Member</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Outstanding</th>
                                <th>Paid</th>
                                <th>Progress</th>
                                <th>Next Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                            <tr>
                                <td>
                                    <strong>{{ $loan->loan_id }}</strong><br>
                                    <small class="text-muted">{{ $loan->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($loan->member->fname, 0, 1) . substr($loan->member->lname, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $loan->member->fname }} {{ $loan->member->lname }}</div>
                                            <small class="text-muted">{{ $loan->member->pm_code }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $loan->product->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ ucfirst($loan->loan_type) }}</small>
                                </td>
                                <td>
                                    <strong>UGX {{ number_format($loan->loan_amount) }}</strong><br>
                                    <small class="text-muted">{{ $loan->loan_period }} months</small>
                                </td>
                                <td>
                                    <span class="text-danger font-weight-bold">UGX {{ number_format($loan->outstanding_amount) }}</span>
                                </td>
                                <td>
                                    <span class="text-success">UGX {{ number_format($loan->paid_amount) }}</span>
                                </td>
                                <td>
                                    @php
                                        $progress = $loan->loan_amount > 0 ? ($loan->paid_amount / $loan->loan_amount) * 100 : 0;
                                    @endphp
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $progress }}%" 
                                             aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ number_format($progress, 1) }}%</small>
                                </td>
                                <td>
                                    @if($loan->next_payment_date)
                                        <span class="text-dark">{{ \Carbon\Carbon::parse($loan->next_payment_date)->format('M d, Y') }}</span><br>
                                        @php
                                            $daysUntil = \Carbon\Carbon::parse($loan->next_payment_date)->diffInDays(now(), false);
                                        @endphp
                                        @if($daysUntil > 0)
                                            <span class="badge badge-danger">{{ $daysUntil }} days overdue</span>
                                        @elseif($daysUntil == 0)
                                            <span class="badge badge-warning">Due today</span>
                                        @else
                                            <span class="badge badge-success">{{ abs($daysUntil) }} days left</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No payment date</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $isOverdue = $loan->due_date && \Carbon\Carbon::parse($loan->due_date)->isPast() && $loan->outstanding_amount > 0;
                                    @endphp
                                    @if($isOverdue)
                                        <span class="badge badge-danger">Overdue</span>
                                    @else
                                        <span class="badge badge-success">Current</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.loans.payment', $loan->id) }}" class="btn btn-sm btn-outline-success">
                                            <i class="mdi mdi-cash"></i>
                                        </a>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.loans.edit', $loan->id) }}">
                                                    <i class="mdi mdi-pencil"></i> Edit
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.loans.schedule', $loan->id) }}">
                                                    <i class="mdi mdi-calendar"></i> Schedule
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.loans.statements', $loan->id) }}">
                                                    <i class="mdi mdi-file-document"></i> Statement
                                                </a>
                                            </div>
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
                        <p class="text-muted">
                            Showing {{ $loans->firstItem() }} to {{ $loans->lastItem() }} of {{ $loans->total() }} results
                        </p>
                    </div>
                    <div>
                        {{ $loans->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="mdi mdi-file-document-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No running loans found</h5>
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'branch_id', 'product_id']))
                        <p class="text-muted">Try adjusting your filters or <a href="{{ route('admin.portfolio.running') }}">clear all filters</a></p>
                    @else
                        <p class="text-muted">There are no active loans in the system</p>
                        <a href="{{ route('admin.loans.create') }}" class="btn btn-primary mt-3">
                            <i class="mdi mdi-plus"></i> Create First Loan
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#branch_id, #product_id').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush