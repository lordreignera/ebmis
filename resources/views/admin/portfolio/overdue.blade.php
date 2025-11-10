@extends('layouts.admin')

@section('title', 'Overdue Loans Portfolio')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Overdue Loans Portfolio</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.loans.export', ['status' => 'overdue']) }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export
            </a>
            <button type="button" class="btn btn-warning" onclick="sendReminders()">
                <i class="mdi mdi-email"></i> Send Reminders
            </button>
            <button type="button" class="btn btn-danger" onclick="escalateSelected()">
                <i class="mdi mdi-alert"></i> Escalate
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Overdue Loans
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_overdue']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-alarm fa-2x text-gray-300"></i>
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
                                Overdue Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['overdue_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-currency-usd fa-2x text-gray-300"></i>
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
                                Average Overdue Days
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['average_overdue_days'] ?? 0, 1) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-calendar-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Collection Rate
                            </div>
                            @php
                                $totalLoans = \App\Models\Loan::where('status', 'disbursed')->count();
                                $overdueLoans = $stats['total_overdue'];
                                $collectionRate = $totalLoans > 0 ? (($totalLoans - $overdueLoans) / $totalLoans) * 100 : 100;
                            @endphp
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($collectionRate, 1) }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Categories -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <h6 class="card-title text-warning">1-30 Days Overdue</h6>
                    @php
                        $category1 = $loans->filter(function($loan) {
                            $daysOverdue = \Carbon\Carbon::parse($loan->due_date)->diffInDays(now());
                            return $daysOverdue >= 1 && $daysOverdue <= 30;
                        });
                    @endphp
                    <h4 class="mb-1">{{ $category1->count() }}</h4>
                    <small class="text-muted">UGX {{ number_format($category1->sum('outstanding_amount')) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <h6 class="card-title text-danger">31-90 Days Overdue</h6>
                    @php
                        $category2 = $loans->filter(function($loan) {
                            $daysOverdue = \Carbon\Carbon::parse($loan->due_date)->diffInDays(now());
                            return $daysOverdue >= 31 && $daysOverdue <= 90;
                        });
                    @endphp
                    <h4 class="mb-1">{{ $category2->count() }}</h4>
                    <small class="text-muted">UGX {{ number_format($category2->sum('outstanding_amount')) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-dark">
                <div class="card-body">
                    <h6 class="card-title text-dark">91+ Days Overdue</h6>
                    @php
                        $category3 = $loans->filter(function($loan) {
                            $daysOverdue = \Carbon\Carbon::parse($loan->due_date)->diffInDays(now());
                            return $daysOverdue > 90;
                        });
                    @endphp
                    <h4 class="mb-1">{{ $category3->count() }}</h4>
                    <small class="text-muted">UGX {{ number_format($category3->sum('outstanding_amount')) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <h6 class="card-title text-success">Recovery Actions</h6>
                    <div class="btn-group-vertical btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-primary btn-sm mb-1" onclick="sendBulkSMS()">
                            <i class="mdi mdi-message-text"></i> Bulk SMS
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm mb-1" onclick="generateCallList()">
                            <i class="mdi mdi-phone"></i> Call List
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="fieldVisitSchedule()">
                            <i class="mdi mdi-map-marker"></i> Field Visits
                        </button>
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
            <form method="GET" action="{{ route('admin.portfolio.overdue') }}" class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="Loan ID, Member...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="overdue_category">Overdue Period</label>
                        <select name="overdue_category" id="overdue_category" class="form-control">
                            <option value="">All Overdue</option>
                            <option value="1-30" {{ request('overdue_category') == '1-30' ? 'selected' : '' }}>1-30 Days</option>
                            <option value="31-90" {{ request('overdue_category') == '31-90' ? 'selected' : '' }}>31-90 Days</option>
                            <option value="90+" {{ request('overdue_category') == '90+' ? 'selected' : '' }}>90+ Days</option>
                        </select>
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="amount_range">Amount Range</label>
                        <select name="amount_range" id="amount_range" class="form-control">
                            <option value="">All Amounts</option>
                            <option value="0-100000" {{ request('amount_range') == '0-100000' ? 'selected' : '' }}>0 - 100K</option>
                            <option value="100000-500000" {{ request('amount_range') == '100000-500000' ? 'selected' : '' }}>100K - 500K</option>
                            <option value="500000+" {{ request('amount_range') == '500000+' ? 'selected' : '' }}>500K+</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <a href="{{ route('admin.portfolio.overdue') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Overdue Loans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-alarm"></i> Overdue Loans
                <span class="badge badge-danger ml-2">{{ $loans->total() }}</span>
            </h6>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="selectAll">
                <label class="custom-control-label" for="selectAll">Select All</label>
            </div>
        </div>
        <div class="card-body">
            @if($loans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th width="30px">
                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                </th>
                                <th>Loan Details</th>
                                <th>Member</th>
                                <th>Amount Details</th>
                                <th>Days Overdue</th>
                                <th>Last Payment</th>
                                <th>Recovery Action</th>
                                <th>Risk Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                            @php
                                $daysOverdue = \Carbon\Carbon::parse($loan->due_date)->diffInDays(now());
                                $riskLevel = $daysOverdue <= 30 ? 'low' : ($daysOverdue <= 90 ? 'medium' : 'high');
                                $riskColor = $daysOverdue <= 30 ? 'warning' : ($daysOverdue <= 90 ? 'danger' : 'dark');
                            @endphp
                            <tr class="risk-{{ $riskLevel }}">
                                <td>
                                    <input type="checkbox" name="selected_loans[]" value="{{ $loan->id }}" class="loan-checkbox form-check-input">
                                </td>
                                <td>
                                    <strong>{{ $loan->loan_id }}</strong><br>
                                    <span class="badge badge-info">{{ $loan->product->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ ucfirst($loan->loan_type) }} • {{ $loan->loan_period }}m</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="rounded-circle bg-{{ $riskColor }} text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($loan->member->fname, 0, 1) . substr($loan->member->lname, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $loan->member->fname }} {{ $loan->member->lname }}</div>
                                            <small class="text-muted">{{ $loan->member->pm_code }}</small><br>
                                            <small class="text-muted">
                                                <i class="mdi mdi-phone"></i> {{ $loan->member->contact }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <small class="text-muted">Principal:</small>
                                        <strong>UGX {{ number_format($loan->loan_amount) }}</strong>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted">Outstanding:</small>
                                        <span class="text-danger font-weight-bold">UGX {{ number_format($loan->outstanding_amount) }}</span>
                                    </div>
                                    <div>
                                        <small class="text-muted">Paid:</small>
                                        <span class="text-success">UGX {{ number_format($loan->paid_amount) }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="badge badge-{{ $riskColor }} badge-lg">
                                        {{ $daysOverdue }}
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            {{ $daysOverdue == 1 ? 'day' : 'days' }}
                                        </small>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            Due: {{ \Carbon\Carbon::parse($loan->due_date)->format('M d') }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $lastPayment = $loan->payments()->latest()->first();
                                    @endphp
                                    @if($lastPayment)
                                        <div>{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('M d, Y') }}</div>
                                        <small class="text-muted">UGX {{ number_format($lastPayment->amount) }}</small>
                                    @else
                                        <span class="text-muted">No payments</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $lastAction = $loan->recovery_actions()->latest()->first();
                                    @endphp
                                    @if($lastAction)
                                        <span class="badge badge-secondary">{{ ucfirst($lastAction->action_type) }}</span><br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($lastAction->created_at)->format('M d') }}</small>
                                    @else
                                        <span class="text-muted">No action</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $riskColor }}">
                                        {{ ucfirst($riskLevel) }} Risk
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="recordPayment({{ $loan->id }})">
                                            <i class="mdi mdi-cash"></i>
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#" onclick="sendSMS({{ $loan->id }})">
                                                    <i class="mdi mdi-message-text"></i> Send SMS
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="makeCall({{ $loan->id }})">
                                                    <i class="mdi mdi-phone"></i> Make Call
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="scheduleVisit({{ $loan->id }})">
                                                    <i class="mdi mdi-calendar-clock"></i> Schedule Visit
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-warning" href="#" onclick="escalateCase({{ $loan->id }})">
                                                    <i class="mdi mdi-alert"></i> Escalate
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
                    <i class="mdi mdi-check-circle" style="font-size: 48px; color: #28a745;"></i>
                    <h5 class="mt-3 text-success">No overdue loans found!</h5>
                    <p class="text-muted">All loans are up to date with their payments</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all functionality
    $('#selectAll, #selectAllTable').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.loan-checkbox').prop('checked', isChecked);
        $('#selectAll, #selectAllTable').prop('checked', isChecked);
    });

    // Individual checkbox change
    $('.loan-checkbox').on('change', function() {
        var totalCheckboxes = $('.loan-checkbox').length;
        var checkedCheckboxes = $('.loan-checkbox:checked').length;
        
        $('#selectAll, #selectAllTable').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Auto-submit form on filter change
    $('#overdue_category, #branch_id, #product_id, #amount_range').on('change', function() {
        $(this).closest('form').submit();
    });
});

function getSelectedLoans() {
    return $('.loan-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
}

function sendReminders() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select loans to send reminders');
        return;
    }
    
    if (confirm(`Send payment reminders to ${selectedLoans.length} borrower(s)?`)) {
        // Implement reminder functionality
        showAlert('info', 'Reminder feature coming soon');
    }
}

function escalateSelected() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select loans to escalate');
        return;
    }
    
    if (confirm(`Escalate ${selectedLoans.length} case(s) for collection?`)) {
        // Implement escalation functionality
        showAlert('info', 'Escalation feature coming soon');
    }
}

function recordPayment(loanId) {
    window.location.href = `{{ route('admin.loans.payment', '') }}/${loanId}`;
}

function sendSMS(loanId) {
    // Implement SMS functionality
    showAlert('info', 'SMS feature coming soon');
}

function makeCall(loanId) {
    // Implement call logging functionality
    showAlert('info', 'Call logging feature coming soon');
}

function scheduleVisit(loanId) {
    // Implement visit scheduling functionality
    showAlert('info', 'Visit scheduling feature coming soon');
}

function escalateCase(loanId) {
    if (confirm('Are you sure you want to escalate this case?')) {
        // Implement escalation functionality
        showAlert('info', 'Case escalation feature coming soon');
    }
}

function sendBulkSMS() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select loans first');
        return;
    }
    showAlert('info', 'Bulk SMS feature coming soon');
}

function generateCallList() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select loans first');
        return;
    }
    showAlert('info', 'Call list generation feature coming soon');
}

function fieldVisitSchedule() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select loans first');
        return;
    }
    showAlert('info', 'Field visit scheduling feature coming soon');
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

<style>
.risk-high {
    background-color: rgba(220, 53, 69, 0.05);
}

.risk-medium {
    background-color: rgba(255, 193, 7, 0.05);
}

.risk-low {
    background-color: rgba(255, 193, 7, 0.02);
}

.badge-lg {
    font-size: 1.1em;
    padding: 0.5em 0.75em;
}
</style>
@endpush