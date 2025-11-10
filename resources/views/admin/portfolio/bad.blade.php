@extends('layouts.admin')

@section('title', 'Bad Loans Portfolio')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bad Loans Portfolio</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.loans.export', ['status' => 'bad']) }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export
            </a>
            <button type="button" class="btn btn-danger" onclick="writeOffSelected()">
                <i class="mdi mdi-delete"></i> Write Off
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
                                Total Bad Loans
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_bad']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-alert-circle fa-2x text-gray-300"></i>
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
                                Bad Debt Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['bad_debt_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-currency-usd-off fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                Written Off Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['written_off_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-delete-circle fa-2x text-gray-300"></i>
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
                                Recovery Rate
                            </div>
                            @php
                                $totalBadDebt = $stats['bad_debt_amount'];
                                $recoveredAmount = \App\Models\Loan::whereIn('status', ['default', 'written_off'])->sum('paid_amount');
                                $recoveryRate = $totalBadDebt > 0 ? ($recoveredAmount / $totalBadDebt) * 100 : 0;
                            @endphp
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($recoveryRate, 1) }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bad Loans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-alert-circle"></i> Bad Loans
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
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th>Recovery Actions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                            <tr>
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
                                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($loan->member->fname, 0, 1) . substr($loan->member->lname, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $loan->member->fname }} {{ $loan->member->lname }}</div>
                                            <small class="text-muted">{{ $loan->member->pm_code }}</small><br>
                                            <small class="text-muted">{{ $loan->member->contact }}</small>
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
                                        <small class="text-muted">Recovered:</small>
                                        <span class="text-success">UGX {{ number_format($loan->paid_amount) }}</span>
                                    </div>
                                </td>
                                <td>
                                    @switch($loan->status)
                                        @case('default')
                                            <span class="badge badge-danger">Default</span>
                                            @break
                                        @case('written_off')
                                            <span class="badge badge-dark">Written Off</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($loan->status) }}</span>
                                    @endswitch
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            Since: {{ \Carbon\Carbon::parse($loan->updated_at)->format('M d, Y') }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $lastPayment = $loan->payments()->latest()->first();
                                        $lastAction = $loan->recovery_actions()->latest()->first();
                                    @endphp
                                    @if($lastPayment)
                                        <div class="mb-1">
                                            <small class="text-muted">Last Payment:</small>
                                            <div>{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('M d, Y') }}</div>
                                            <small class="text-success">UGX {{ number_format($lastPayment->amount) }}</small>
                                        </div>
                                    @endif
                                    @if($lastAction)
                                        <div>
                                            <small class="text-muted">Last Action:</small>
                                            <div>{{ ucfirst($lastAction->action_type) }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($lastAction->created_at)->diffForHumans() }}</small>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-1" onclick="sendDemandNotice({{ $loan->id }})">
                                            <i class="mdi mdi-email"></i> Demand Notice
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm mb-1" onclick="legalAction({{ $loan->id }})">
                                            <i class="mdi mdi-gavel"></i> Legal Action
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="debtRestructure({{ $loan->id }})">
                                            <i class="mdi mdi-file-edit"></i> Restructure
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if($loan->status == 'default')
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="writeOffLoan({{ $loan->id }})">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        @endif
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.loans.payment-history', $loan->id) }}">
                                                    <i class="mdi mdi-history"></i> Payment History
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.members.show', $loan->member->id) }}">
                                                    <i class="mdi mdi-account"></i> View Member
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-warning" href="#" onclick="partialRecovery({{ $loan->id }})">
                                                    <i class="mdi mdi-cash"></i> Partial Recovery
                                                </a>
                                                <a class="dropdown-item text-info" href="#" onclick="settlementOffer({{ $loan->id }})">
                                                    <i class="mdi mdi-handshake"></i> Settlement Offer
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
                    <h5 class="mt-3 text-success">No bad loans found!</h5>
                    <p class="text-muted">Great news! All loans are in good standing</p>
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

    $('.loan-checkbox').on('change', function() {
        var totalCheckboxes = $('.loan-checkbox').length;
        var checkedCheckboxes = $('.loan-checkbox:checked').length;
        
        $('#selectAll, #selectAllTable').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
});

function getSelectedLoans() {
    return $('.loan-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
}

function writeOffSelected() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select loans to write off');
        return;
    }
    
    if (confirm(`Are you sure you want to write off ${selectedLoans.length} loan(s)? This action cannot be undone.`)) {
        var reason = prompt('Please provide a reason for write-off:');
        if (reason) {
            // Implement write-off functionality
            showAlert('info', 'Write-off feature coming soon');
        }
    }
}

function writeOffLoan(loanId) {
    if (confirm('Are you sure you want to write off this loan? This action cannot be undone.')) {
        var reason = prompt('Please provide a reason for write-off:');
        if (reason) {
            // Implement single loan write-off
            showAlert('info', 'Write-off feature coming soon');
        }
    }
}

function sendDemandNotice(loanId) {
    if (confirm('Send a formal demand notice to this borrower?')) {
        showAlert('info', 'Demand notice feature coming soon');
    }
}

function legalAction(loanId) {
    if (confirm('Initiate legal action for this loan?')) {
        showAlert('info', 'Legal action tracking feature coming soon');
    }
}

function debtRestructure(loanId) {
    if (confirm('Restructure this debt with new terms?')) {
        showAlert('info', 'Debt restructuring feature coming soon');
    }
}

function partialRecovery(loanId) {
    var amount = prompt('Enter partial recovery amount (UGX):');
    if (amount && !isNaN(amount) && amount > 0) {
        showAlert('info', 'Partial recovery recording feature coming soon');
    }
}

function settlementOffer(loanId) {
    var amount = prompt('Enter settlement offer amount (UGX):');
    if (amount && !isNaN(amount) && amount > 0) {
        showAlert('info', 'Settlement offer feature coming soon');
    }
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