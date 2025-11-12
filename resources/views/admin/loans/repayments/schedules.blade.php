@extends('layouts.admin')

@section('title', 'Repayment Schedules - ' . $loan->loan_code)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.active') }}">Active Loans</a></li>
                        <li class="breadcrumb-item active">Repayment Schedules</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    Repayment Schedules - {{ $loan->loan_code }}
                    @if($loan->days_overdue > 0)
                        <span class="badge bg-danger ms-2">{{ $loan->days_overdue }} days overdue</span>
                    @endif
                </h4>
            </div>
        </div>
    </div>

    <!-- Loan Summary -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Loan Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Borrower Information</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $loan->borrower_name }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $loan->phone_number }}</p>
                                <p class="mb-1"><strong>Branch:</strong> {{ $loan->branch_name ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Product:</strong> {{ $loan->product_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Loan Terms</h6>
                                <p class="mb-1"><strong>Principal:</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
                                <p class="mb-1"><strong>Interest Rate:</strong> {{ $loan->interest_rate }}%</p>
                                <p class="mb-1"><strong>Term:</strong> {{ $loan->loan_term }} {{ $loan->period_type }}</p>
                                <p class="mb-0"><strong>Start Date:</strong> {{ $loan->disbursement_date ? date('M d, Y', strtotime($loan->disbursement_date)) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Payment Status</h6>
                                <p class="mb-1"><strong>Total Payable:</strong> UGX {{ number_format($loan->total_payable, 0) }}</p>
                                <p class="mb-1"><strong>Amount Paid:</strong> <span class="text-success">UGX {{ number_format($loan->amount_paid, 0) }}</span></p>
                                <p class="mb-1"><strong>Outstanding:</strong> <span class="text-primary">UGX {{ number_format($loan->outstanding_balance, 0) }}</span></p>
                                <p class="mb-0"><strong>Progress:</strong> 
                                    <span class="badge bg-{{ $loan->payment_percentage >= 75 ? 'success' : ($loan->payment_percentage >= 50 ? 'warning' : 'danger') }}">
                                        {{ number_format($loan->payment_percentage, 1) }}%
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div>
                                <h6 class="text-muted">Next Payment</h6>
                                @if($nextDue)
                                    <p class="mb-1"><strong>Due Date:</strong> 
                                        <span class="{{ $nextDue->is_overdue ? 'text-danger' : 'text-success' }}">
                                            {{ date('M d, Y', strtotime($nextDue->due_date)) }}
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Amount:</strong> UGX {{ number_format($nextDue->due_amount, 0) }}</p>
                                    @if($nextDue->is_overdue)
                                        <p class="mb-0"><strong>Penalty:</strong> <span class="text-danger">UGX {{ number_format($nextDue->penalty_amount, 0) }}</span></p>
                                    @endif
                                @else
                                    <p class="mb-0 text-muted">No pending payments</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Alert -->
    @if($overdueCount > 0)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="mdi mdi-alert-circle-outline fs-4"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="alert-heading mb-1">Payment Overdue!</h6>
                        <p class="mb-0">
                            This loan has <strong>{{ $overdueCount }} overdue payment(s)</strong> 
                            totaling <strong>UGX {{ number_format($overdueAmount, 0) }}</strong>.
                            @if($loan->days_overdue > 7)
                                Consider immediate collection or restructuring.
                            @endif
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        @if($nextDue)
                            <button type="button" class="btn btn-light btn-sm" 
                                    onclick="openRepayModal({{ $nextDue->id }}, '{{ $nextDue->due_date }}', {{ $nextDue->due_amount + $nextDue->penalty_amount }})">
                                <i class="mdi mdi-cash-fast me-1"></i> Pay Now
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($nextDue)
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success w-100" 
                                        onclick="openRepayModal({{ $nextDue->id }}, '{{ $nextDue->due_date }}', {{ $nextDue->due_amount + $nextDue->penalty_amount }})">
                                    <i class="mdi mdi-cash-fast me-1"></i>
                                    Record Payment
                                    <br><small>UGX {{ number_format($nextDue->due_amount + $nextDue->penalty_amount, 0) }}</small>
                                </button>
                            </div>
                        @endif
                        
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" onclick="$('#partialPaymentModal').modal('show')">
                                <i class="mdi mdi-cash-multiple me-1"></i>
                                Partial Payment
                                <br><small>Any amount</small>
                            </button>
                        </div>
                        
                        @if($loan->days_overdue > 7)
                            <div class="col-md-3">
                                <a href="{{ route('admin.loans.restructure', $loan->id) }}" class="btn btn-warning w-100">
                                    <i class="mdi mdi-account-convert me-1"></i>
                                    Restructure Loan
                                    <br><small>Modify terms</small>
                                </a>
                            </div>
                        @endif
                        
                        <div class="col-md-3">
                            <div class="dropdown w-100">
                                <button class="btn btn-light dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-printer me-1"></i>
                                    Print Reports
                                    <br><small>Statements & notices</small>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.loans.statements.print', $loan->id) }}" target="_blank">
                                        <i class="mdi mdi-file-document-outline me-2"></i>Payment Statement
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.loans.schedules.print', $loan->id) }}" target="_blank">
                                        <i class="mdi mdi-calendar-text me-2"></i>Repayment Schedule
                                    </a></li>
                                    @if($overdueCount > 0)
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.loans.notices.print', $loan->id) }}" target="_blank">
                                            <i class="mdi mdi-alert-circle-outline me-2"></i>Overdue Notice
                                        </a></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Repayment Schedules -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Repayment Schedules ({{ count($schedules) }} installments)</h5>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="scheduleFilter" id="all" value="all" checked>
                                <label class="btn btn-outline-primary btn-sm" for="all">All</label>

                                <input type="radio" class="btn-check" name="scheduleFilter" id="pending" value="pending">
                                <label class="btn btn-outline-warning btn-sm" for="pending">Pending</label>

                                <input type="radio" class="btn-check" name="scheduleFilter" id="overdue" value="overdue">
                                <label class="btn btn-outline-danger btn-sm" for="overdue">Overdue</label>

                                <input type="radio" class="btn-check" name="scheduleFilter" id="paid" value="paid">
                                <label class="btn btn-outline-success btn-sm" for="paid">Paid</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover" id="schedulesTable" style="font-size: 0.875rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 3%;">#</th>
                                    <th style="width: 7%;">Installment Date</th>
                                    <th style="width: 6%;">Principal</th>
                                    <th style="width: 6%;">Original Interest</th>
                                    <th style="width: 6%;">Principal cal Intrest</th>
                                    <th style="width: 6%;">Principal Bal</th>
                                    <th style="width: 6%;">Principal for Intrest payable</th>
                                    <th style="width: 6%;">Intrest payable</th>
                                    <th style="width: 5%;">Periods in Arrears</th>
                                    <th style="width: 6%;">Late Fees</th>
                                    <th style="width: 6%;">Total Payment</th>
                                    <th style="width: 6%;">Principal Paid</th>
                                    <th style="width: 6%;">Interest Paid</th>
                                    <th style="width: 6%;">Late fees Paid</th>
                                    <th style="width: 6%;">Total Amount Paid</th>
                                    <th style="width: 6%;">Total Balance</th>
                                    <th style="width: 5%;">Status</th>
                                    <th style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $index => $schedule)
                                    @php
                                        // Use values already calculated in controller (EXACT bimsadmin logic)
                                        $statusClass = '';
                                        $statusFilter = 'pending';
                                        
                                        if ($schedule->status == 1) {
                                            $statusClass = 'table-success';
                                            $statusFilter = 'paid';
                                            $statusBadge = '<span class="badge bg-success">Paid</span>';
                                        } elseif ($schedule->pending_count > 0) {
                                            $statusClass = 'table-warning';
                                            $statusFilter = 'pending';
                                            $statusBadge = '<span class="badge bg-warning">Pending (' . $schedule->pending_count . ')</span>';
                                        } elseif ($schedule->periods_in_arrears > 0) {
                                            $statusClass = 'table-danger';
                                            $statusFilter = 'overdue';
                                            $statusBadge = '<span class="badge bg-danger">Not Paid</span>';
                                        } else {
                                            $statusBadge = '<span class="badge bg-secondary">Not Paid</span>';
                                        }
                                    @endphp
                                    
                                    <tr class="{{ $statusClass }} schedule-row" data-filter="{{ $statusFilter }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ date('d-m-Y', strtotime($schedule->payment_date)) }}</td>
                                        <td class="text-end">{{ number_format($schedule->principal, 0) }}</td>
                                        <td class="text-end">{{ number_format($schedule->interest, 0) }}</td>
                                        <td class="text-end">{{ number_format($schedule->pricipalcalIntrest, 0) }}</td>
                                        <td class="text-end">{{ number_format($schedule->principal_balance, 0) }}</td>
                                        <td class="text-end">{{ number_format($schedule->globalprincipal, 0) }}</td>
                                        <td class="text-end">{{ number_format($schedule->intrestamtpayable, 0) }}</td>
                                        <td class="text-center">{{ $schedule->periods_in_arrears }}</td>
                                        <td class="text-end">
                                            @if($schedule->penalty > 0)
                                                <span class="text-danger">{{ number_format($schedule->penalty, 0) }}</span>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-end"><strong>{{ number_format($schedule->total_payment, 0) }}</strong></td>
                                        <td class="text-end">
                                            @if($schedule->principal_paid > 0)
                                                <span class="text-success">{{ number_format($schedule->principal_paid, 0) }}</span>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($schedule->interest_paid > 0)
                                                <span class="text-success">{{ number_format($schedule->interest_paid, 0) }}</span>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($schedule->penalty_paid > 0)
                                                <span class="text-success">{{ number_format($schedule->penalty_paid, 0) }}</span>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($schedule->paid > 0)
                                                <strong class="text-success">{{ number_format($schedule->paid, 0) }}</strong>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($schedule->total_balance > 0)
                                                <strong class="text-danger">{{ number_format($schedule->total_balance, 0) }}</strong>
                                            @else
                                                <span class="text-success">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{!! $statusBadge !!}</td>
                                        <td class="text-center">
                                            @if($schedule->status == 1)
                                                {{-- Paid - Show Receipt Button --}}
                                                @php
                                                    $repayment = \App\Models\Repayment::where('schedule_id', $schedule->id)
                                                        ->where('status', 1)
                                                        ->orderBy('id', 'desc')
                                                        ->first();
                                                @endphp
                                                @if($repayment)
                                                    <a href="{{ route('admin.repayments.receipt', $repayment->id) }}" 
                                                       class="btn btn-info btn-sm px-2 py-1" 
                                                       target="_blank"
                                                       title="View Receipt">
                                                        <i class="bi bi-receipt"></i> Receipt
                                                    </a>
                                                @else
                                                    <span class="text-muted">Paid</span>
                                                @endif
                                            @elseif($schedule->status == 0 && $schedule->pending_count == 0)
                                                {{-- Not Paid - Show Repay Button --}}
                                                <button type="button" class="btn btn-success btn-sm px-2 py-1" 
                                                        onclick="openRepayModal({{ $schedule->id }}, '{{ date('M d, Y', strtotime($schedule->payment_date)) }}', {{ $schedule->total_balance }})"
                                                        title="Repay">
                                                    Repay
                                                </button>
                                            @elseif($schedule->pending_count > 0)
                                                {{-- Pending - Show Check Progress and Retry Buttons --}}
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-info btn-sm px-2 py-1" 
                                                            onclick="checkScheduleProgress({{ $schedule->id }})"
                                                            title="Check Payment Status">
                                                        <i class="fas fa-sync"></i> Check
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm px-2 py-1" 
                                                            onclick="openRepayModal({{ $schedule->id }}, '{{ date('M d, Y', strtotime($schedule->payment_date)) }}', {{ $schedule->total_balance }})"
                                                            title="Retry Payment">
                                                        <i class="fas fa-redo"></i> Retry
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th></th>
                                    <th class="text-end"><strong>Totals</strong></th>
                                    <th class="text-end">
                                        <strong>{{ number_format($schedules->sum('principal'), 0) }}</strong>
                                    </th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-end">
                                        <strong>{{ number_format($schedules->sum('intrestamtpayable'), 0) }}</strong>
                                    </th>
                                    <th class="text-center">
                                        <strong>{{ number_format($schedules->sum('periods_in_arrears'), 0) }}</strong>
                                    </th>
                                    <th class="text-end">
                                        <strong>{{ number_format($schedules->sum('penalty'), 0) }}</strong>
                                    </th>
                                    <th></th>
                                    <th class="text-end text-success">
                                        <strong>{{ number_format($schedules->sum('principal_paid'), 0) }}</strong>
                                    </th>
                                    <th class="text-end text-success">
                                        <strong>{{ number_format($schedules->sum('interest_paid'), 0) }}</strong>
                                    </th>
                                    <th class="text-end text-success">
                                        <strong>{{ number_format($schedules->sum('penalty_paid'), 0) }}</strong>
                                    </th>
                                    <th class="text-end text-success">
                                        <strong>{{ number_format($schedules->sum('paid'), 0) }}</strong>
                                    </th>
                                    <th class="text-end text-danger">
                                        <strong>{{ number_format($schedules->sum('total_balance'), 0) }}</strong>
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple Repayment Modal (BimsAdmin Style) -->
<div class="modal fade" id="repaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white">
            <div class="modal-header bg-white border-0">
                <h5 class="modal-title text-dark">Add Repayment Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="repaymentForm">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                <input type="hidden" name="member_id" value="{{ $loan->member_id }}">
                <input type="hidden" id="schedule_id" name="s_id">
                
                <div class="modal-body bg-white">
                    <div class="mb-3">
                        <label class="form-label text-dark">Payment Amount</label>
                        <input type="text" class="form-control bg-white" id="payment_amount" name="amount" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark">Payment Type</label>
                        <select class="form-select bg-white" id="payment_type" name="type" required onchange="toggleMedium()">
                            <option value="">Select Payment Type</option>
                            <option value="3">Direct Bank Transfer</option>
                            <option value="2">Mobile Money</option>
                            <option value="1">Cash</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="medium_div" style="display: none;">
                        <label class="form-label text-dark">Mobile Money Network</label>
                        <input type="text" class="form-control bg-white" id="detected_network" readonly>
                        <input type="hidden" id="medium" name="medium">
                        <small class="text-muted">Auto-detected from member's phone number</small>
                    </div>
                    
                    <div class="mb-3" id="phone_div" style="display: none;">
                        <label class="form-label text-dark">Phone Number</label>
                        <input type="text" class="form-control bg-white" id="member_phone" readonly value="{{ $loan->member->contact ?? '' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark">Repayment Details</label>
                        <textarea class="form-control bg-white" name="details" rows="3" placeholder="Type Here..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark">Transaction Generated By</label>
                        <div class="text-muted">{{ Auth::user()->fname ?? 'Admin' }} {{ Auth::user()->lname ?? '' }}, {{ date('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
                
                <div class="modal-footer bg-white border-0">
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Partial Payment Modal -->
<div class="modal fade" id="partialPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Partial Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="partialPaymentForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <small>Partial payments will be applied to the oldest outstanding installment first.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="1" required>
                        <div class="form-text">Outstanding Balance: UGX {{ number_format($loan->outstanding_balance, 0) }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Partial Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Detect network from phone number
function detectNetwork(phone) {
    phone = phone.replace(/\D/g, ''); // Remove non-digits
    
    // Remove country code if present (256)
    if (phone.startsWith('256')) {
        phone = '0' + phone.substring(3);
    }
    
    // Ensure it starts with 0
    if (!phone.startsWith('0')) {
        phone = '0' + phone;
    }
    
    // Get the prefix (first 4 digits: 0772, 0776, etc.)
    const prefix = phone.substring(0, 4);
    
    // MTN prefixes: 077X, 078X
    const mtnPrefixes = ['0776', '0777', '0778', '0774', '0775'];
    // Airtel prefixes: 070X, 075X
    const airtelPrefixes = ['0700', '0701', '0702', '0750', '0751', '0752', '0753', '0754', '0755', '0756', '0757'];
    
    if (mtnPrefixes.includes(prefix)) {
        return { name: 'MTN Money', code: 2 };
    } else if (airtelPrefixes.includes(prefix)) {
        return { name: 'Airtel Money', code: 1 };
    }
    
    // Try 3-digit prefix check as fallback
    const prefix3 = phone.substring(0, 3);
    if (['077', '078'].includes(prefix3)) {
        return { name: 'MTN Money', code: 2 };
    } else if (['070', '075'].includes(prefix3)) {
        return { name: 'Airtel Money', code: 1 };
    }
    
    return { name: 'Unknown Network (Phone: ' + phone + ')', code: 0 };
}

// Toggle mobile money network field
function toggleMedium() {
    var paymentType = document.getElementById('payment_type').value;
    var mediumDiv = document.getElementById('medium_div');
    var phoneDiv = document.getElementById('phone_div');
    var mediumInput = document.getElementById('medium');
    var networkDisplay = document.getElementById('detected_network');
    var memberPhone = document.getElementById('member_phone').value;
    
    if(paymentType == '2') {
        mediumDiv.style.display = 'block';
        phoneDiv.style.display = 'block';
        mediumInput.required = true;
        
        // Auto-detect network
        const network = detectNetwork(memberPhone);
        networkDisplay.value = network.name;
        mediumInput.value = network.code;
        
        if (network.code === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Network Detection Failed',
                text: 'Could not detect mobile money network from phone number: ' + memberPhone,
                confirmButtonText: 'OK'
            });
        }
    } else {
        mediumDiv.style.display = 'none';
        phoneDiv.style.display = 'none';
        mediumInput.required = false;
    }
}

// Open repayment modal - Keep outside for onclick attribute
function openRepayModal(scheduleId, dueDate, amount) {
    $('#schedule_id').val(scheduleId);
    $('#payment_amount').val(amount.toFixed(0));
    $('#repaymentModal').modal('show');
}

$(document).ready(function() {
    // Schedule filtering
    $('input[name="scheduleFilter"]').change(function() {
        var filter = $(this).val();
        
        $('.schedule-row').hide();
        
        if (filter === 'all') {
            $('.schedule-row').show();
        } else {
            $('.schedule-row[data-filter="' + filter + '"]').show();
        }
    });
    
    // Handle repayment form submission with mobile money polling
    $('#repaymentForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted!');
        
        const paymentType = $('#payment_type').val();
        const formData = $(this).serialize();
        
        console.log('Payment Type:', paymentType);
        console.log('Form Data:', formData);
        
        // For mobile money, show polling modal
        if (paymentType == '2') {
            console.log('Handling mobile money payment...');
            handleMobileMoneyPayment(formData);
        } else {
            console.log('Handling cash/bank payment...');
            // For cash/bank, submit normally
            submitRepayment(formData);
        }
    });
    
    // Handle partial payment form submission
    $('#partialPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('loan_id', {{ $loan->id }});
        formData.append('_token', '{{ csrf_token() }}');
        
        $.ajax({
            url: '{{ route("admin.loans.repayments.partial") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#partialPaymentModal').modal('hide');
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire('Error!', message, 'error');
            }
        });
    });
});

function handleMobileMoneyPayment(formData) {
    const memberPhone = $('#member_phone').val();
    const amount = $('#payment_amount').val();
    const network = $('#detected_network').val();
    
    // Close repayment modal
    $('#repaymentModal').modal('hide');
    
    // Show processing modal with countdown
    Swal.fire({
        title: 'Processing Mobile Money Payment',
        html: `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p><strong>${network}</strong></p>
                <p>Phone: <strong>${memberPhone}</strong></p>
                <p>Amount: <strong>UGX ${parseFloat(amount).toLocaleString()}</strong></p>
                <hr>
                <p class="text-info">
                    <i class="fas fa-mobile-alt"></i> 
                    Please check your phone and enter your Mobile Money PIN to complete the payment
                </p>
                <p class="text-muted">Time remaining: <span id="countdown">60</span>s</p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            // Submit the payment request
            $.ajax({
                url: '{{ route("admin.loans.repayments.store") }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        const transactionId = response.transaction_id;
                        
                        // Start 60-second countdown and polling
                        startPaymentPolling(transactionId, 60);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Initiation Failed',
                            html: `
                                <p>${response.message || 'Payment initiation failed'}</p>
                                <p class="mt-3"><strong>Would you like to retry?</strong></p>
                            `,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-redo"></i> Retry Payment',
                            cancelButtonText: 'Close',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Reopen the payment modal
                                $('#repaymentModal').modal('show');
                            } else {
                                window.location.reload();
                            }
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Error',
                        html: `
                            <p>${message}</p>
                            <p class="mt-3"><strong>Would you like to retry?</strong></p>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-redo"></i> Retry Payment',
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reopen the payment modal
                            $('#repaymentModal').modal('show');
                        } else {
                            window.location.reload();
                        }
                    });
                }
            });
        }
    });
}

function startPaymentPolling(transactionId, maxSeconds) {
    let secondsRemaining = maxSeconds;
    const countdownElement = document.getElementById('countdown');
    
    // Update countdown every second
    const countdownTimer = setInterval(() => {
        secondsRemaining--;
        if (countdownElement) {
            countdownElement.textContent = secondsRemaining;
        }
        
        if (secondsRemaining <= 0) {
            clearInterval(countdownTimer);
            clearInterval(pollingTimer);
            handlePaymentTimeout();
        }
    }, 1000);
    
    // Poll every 3 seconds
    const pollingTimer = setInterval(() => {
        checkPaymentStatus(transactionId, pollingTimer, countdownTimer);
    }, 3000);
}

function checkPaymentStatus(transactionId, pollingTimer, countdownTimer) {
    $.ajax({
        url: '/admin/check-payment-status/' + transactionId,
        method: 'GET',
        success: function(response) {
            if (response.status === 'SUCCESS' || response.status === 'COMPLETED') {
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Successful!',
                    html: `
                        <p>Transaction ID: <strong>${transactionId}</strong></p>
                        <p>The repayment has been recorded successfully.</p>
                    `,
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            } else if (response.status === 'FAILED' || response.status === 'CANCELLED') {
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    html: `
                        <p>${response.message || 'The mobile money payment was not completed'}</p>
                        <p class="mt-3"><strong>Would you like to retry?</strong></p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-redo"></i> Retry Payment',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    } else {
                        window.location.reload();
                    }
                });
            }
            // If PENDING, continue polling
        },
        error: function() {
            // Continue polling on error (network issue)
            console.log('Polling error, retrying...');
        }
    });
}

function handlePaymentTimeout() {
    Swal.fire({
        icon: 'warning',
        title: 'Payment Timeout',
        html: `
            <p>The 60-second verification period has expired.</p>
            <p>If you completed the payment, it may still be processing.</p>
            <p class="mt-3"><strong>What would you like to do?</strong></p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fas fa-sync"></i> Check Progress',
        denyButtonText: '<i class="fas fa-redo"></i> Retry Payment',
        cancelButtonText: 'Close',
        confirmButtonColor: '#3085d6',
        denyButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            // Check progress - continue polling for 30 more seconds
            checkProgressManually();
        } else if (result.isDenied) {
            // Retry payment - reload page to start fresh
            window.location.reload();
        } else {
            // Close - just reload
            window.location.reload();
        }
    });
}

function checkProgressManually() {
    const scheduleId = $('#schedule_id').val();
    checkScheduleProgress(scheduleId);
}

// Check progress for a specific schedule
function checkScheduleProgress(scheduleId) {
    Swal.fire({
        title: 'Checking Payment Status',
        html: `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Checking if payment has been processed...</p>
                <p class="text-muted">Checking for <span id="manual_countdown">30</span> seconds...</p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            // Get the last pending transaction for this schedule
            $.ajax({
                url: '/admin/loans/repayments/get-pending-transaction/' + scheduleId,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.transaction_id) {
                        // Found pending transaction - start polling
                        startManualPolling(response.transaction_id, 30);
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Pending Payment',
                            text: response.message || 'No recent pending payment found for this schedule.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not check payment status',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }
    });
}

function startManualPolling(transactionId, maxSeconds) {
    let secondsRemaining = maxSeconds;
    const countdownElement = document.getElementById('manual_countdown');
    
    // Update countdown every second
    const countdownTimer = setInterval(() => {
        secondsRemaining--;
        if (countdownElement) {
            countdownElement.textContent = secondsRemaining;
        }
        
        if (secondsRemaining <= 0) {
            clearInterval(countdownTimer);
            clearInterval(pollingTimer);
            
            Swal.fire({
                icon: 'info',
                title: 'Still Processing',
                text: 'Payment is still being processed. Please check again in a few moments.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        }
    }, 1000);
    
    // Poll every 3 seconds
    const pollingTimer = setInterval(() => {
        checkPaymentStatus(transactionId, pollingTimer, countdownTimer);
    }, 3000);
}

function submitRepayment(formData) {
    $.ajax({
        url: '{{ route("admin.loans.repayments.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire('Success!', 'Repayment recorded successfully', 'success').then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred';
            Swal.fire('Error!', message, 'error');
        }
    });
}
</script>
@endpush
@endsection