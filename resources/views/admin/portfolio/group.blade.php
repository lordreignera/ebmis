@extends('layouts.admin')

@section('title', 'Group Loans Portfolio')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Group Loans Portfolio</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.loans.export', ['type' => 'group']) }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export
            </a>
            <a href="{{ route('admin.group-loans.create') }}" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> New Group Loan
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Group Loans
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_group']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-account-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Amount Disbursed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['group_amount']) }}</div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['group_outstanding']) }}</div>
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
                                Average Group Loan Size
                            </div>
                            @php
                                $avgLoanSize = $stats['total_group'] > 0 ? $stats['group_amount'] / $stats['total_group'] : 0;
                            @endphp
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($avgLoanSize) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Performance Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Group Loan Performance Trends</h6>
                </div>
                <div class="card-body">
                    <canvas id="groupPerformanceChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Group Status Distribution</h6>
                </div>
                <div class="card-body">
                    @php
                        $activeGroups = \App\Models\Group::whereHas('loans', function($q) { $q->where('status', 'disbursed'); })->count();
                        $newGroups = \App\Models\Group::whereDoesntHave('loans')->count();
                        $completedGroups = \App\Models\Group::whereHas('loans', function($q) { $q->where('status', 'paid'); })->count();
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Active Groups</span>
                            <strong class="text-success">{{ $activeGroups }}</strong>
                        </div>
                        <div class="progress mt-1" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $activeGroups > 0 ? 70 : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>New Groups</span>
                            <strong class="text-info">{{ $newGroups }}</strong>
                        </div>
                        <div class="progress mt-1" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: {{ $newGroups > 0 ? 50 : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Completed</span>
                            <strong class="text-primary">{{ $completedGroups }}</strong>
                        </div>
                        <div class="progress mt-1" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: {{ $completedGroups > 0 ? 30 : 0 }}%"></div>
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
            <form method="GET" action="{{ route('admin.portfolio.group') }}" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="Loan ID, Group name...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="disbursed" {{ request('status') == 'disbursed' ? 'selected' : '' }}>Disbursed</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        </select>
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
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <a href="{{ route('admin.portfolio.group') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Group Loans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-account-group"></i> Group Loans
                <span class="badge badge-success ml-2">{{ $loans->total() }}</span>
            </h6>
        </div>
        <div class="card-body">
            @if($loans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Loan ID</th>
                                <th>Group Details</th>
                                <th>Members</th>
                                <th>Amount</th>
                                <th>Outstanding</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Next Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                            @php
                                $group = $loan->group ?? $loan->member->group;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $loan->loan_id }}</strong><br>
                                    <small class="text-muted">{{ $loan->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($group->name ?? 'N/A', 0, 2)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $group->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $group->group_code ?? 'N/A' }}</small><br>
                                            <small class="text-muted">
                                                <i class="mdi mdi-map-marker"></i> {{ $group->meeting_location ?? 'N/A' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($group)
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2">
                                                <span class="badge badge-primary">{{ $group->members->count() }} members</span>
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            @foreach($group->members->take(3) as $member)
                                                <small class="d-block text-muted">{{ $member->fname }} {{ $member->lname }}</small>
                                            @endforeach
                                            @if($group->members->count() > 3)
                                                <small class="text-muted">+{{ $group->members->count() - 3 }} more</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No group info</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>UGX {{ number_format($loan->loan_amount) }}</strong><br>
                                    <small class="text-muted">{{ $loan->loan_period }} months</small><br>
                                    <small class="text-muted">{{ $loan->interest_rate }}% interest</small>
                                </td>
                                <td>
                                    <span class="text-danger font-weight-bold">UGX {{ number_format($loan->outstanding_amount) }}</span><br>
                                    <small class="text-success">Paid: UGX {{ number_format($loan->paid_amount) }}</small>
                                </td>
                                <td>
                                    @php
                                        $progress = $loan->loan_amount > 0 ? ($loan->paid_amount / $loan->loan_amount) * 100 : 0;
                                    @endphp
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $progress }}%" 
                                             aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ number_format($progress, 1) }}% complete</small>
                                </td>
                                <td>
                                    @switch($loan->status)
                                        @case('pending')
                                            <span class="badge badge-warning">Pending</span>
                                            @break
                                        @case('approved')
                                            <span class="badge badge-info">Approved</span>
                                            @break
                                        @case('disbursed')
                                            @php
                                                $isOverdue = $loan->due_date && \Carbon\Carbon::parse($loan->due_date)->isPast() && $loan->outstanding_amount > 0;
                                            @endphp
                                            @if($isOverdue)
                                                <span class="badge badge-danger">Overdue</span>
                                            @else
                                                <span class="badge badge-success">Current</span>
                                            @endif
                                            @break
                                        @case('paid')
                                            <span class="badge badge-success">Paid</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($loan->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($loan->next_payment_date && $loan->status == 'disbursed')
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
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if($group)
                                            <a href="{{ route('admin.groups.show', $group->id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="mdi mdi-account-group"></i>
                                            </a>
                                        @endif
                                        @if($loan->status == 'disbursed')
                                            <a href="{{ route('admin.loans.payment', $loan->id) }}" class="btn btn-sm btn-outline-success">
                                                <i class="mdi mdi-cash"></i>
                                            </a>
                                        @endif
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.loans.edit', $loan->id) }}">
                                                    <i class="mdi mdi-pencil"></i> Edit Loan
                                                </a>
                                                @if($group)
                                                    <a class="dropdown-item" href="{{ route('admin.groups.show', $group->id) }}">
                                                        <i class="mdi mdi-account-group"></i> View Group
                                                    </a>
                                                @endif
                                                <a class="dropdown-item" href="{{ route('admin.loans.statement', $loan->id) }}">
                                                    <i class="mdi mdi-file-document"></i> Statement
                                                </a>
                                                @if($loan->status == 'disbursed')
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="{{ route('admin.loans.schedule', $loan->id) }}">
                                                        <i class="mdi mdi-calendar"></i> Payment Schedule
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="groupMeeting({{ $loan->id }})">
                                                        <i class="mdi mdi-calendar-account"></i> Group Meeting
                                                    </a>
                                                @endif
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
                    <i class="mdi mdi-account-group-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No group loans found</h5>
                    @if(request()->anyFilled(['search', 'status', 'start_date', 'end_date', 'branch_id']))
                        <p class="text-muted">Try adjusting your filters or <a href="{{ route('admin.portfolio.group') }}">clear all filters</a></p>
                    @else
                        <p class="text-muted">Start by creating group loans for your registered groups</p>
                        <a href="{{ route('admin.group-loans.create') }}" class="btn btn-success mt-3">
                            <i class="mdi mdi-plus"></i> Create First Group Loan
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#status, #branch_id').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Initialize performance chart
    initializePerformanceChart();
});

function initializePerformanceChart() {
    const ctx = document.getElementById('groupPerformanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Group Loans Disbursed',
                data: [5, 8, 12, 15, 18, 22, 25, 30, 28, 32, 35, 38],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }, {
                label: 'Loans Completed',
                data: [2, 4, 6, 8, 10, 12, 15, 18, 20, 22, 25, 28],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function groupMeeting(loanId) {
    showAlert('info', 'Group meeting scheduling feature coming soon');
}

function showAlert(type, message) {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').first().alert('close');
    }, 5000);
}
</script>
@endpush