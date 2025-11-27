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
    <div class="row" id="loan-summary-section">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Loan Summary</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="printSchedules()">
                        <i class="mdi mdi-printer"></i> Print Schedules
                    </button>
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
                                <p class="mb-1"><strong>Interest Rate:</strong> {{ number_format($loan->interest_rate, 2) }}%</p>
                                <p class="mb-1"><strong>Term:</strong> {{ $loan->loan_term }} {{ $loan->period_type_name ?? 'installments' }}</p>
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
                                        <p class="mb-1"><strong>Days Late:</strong> <span class="text-danger">{{ $loan->days_overdue }} {{ $loan->days_overdue == 1 ? 'day' : 'days' }}</span></p>
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
    <div class="row" id="repayment-schedules-section">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Repayment Schedules ({{ count($schedules) }} installments)</h5>
                        </div>
                        <div class="col-auto">
                            @if($overdueCount > 0)
                                <button type="button" class="btn btn-warning btn-sm me-2" onclick="openRescheduleModal()" title="Reschedule overdue payments due to system upgrade">
                                    <i class="mdi mdi-calendar-refresh"></i> Reschedule Overdue
                                </button>
                            @endif
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
                                                        ->where(function($query) {
                                                            $query->where('status', 1)
                                                                  ->orWhere('payment_status', 'Completed');
                                                        })
                                                        ->orderBy('id', 'desc')
                                                        ->first();
                                                @endphp
                                                @if($repayment)
                                                    <a href="{{ route('admin.repayments.receipt', $repayment->id) }}" 
                                                       class="btn btn-primary btn-sm px-2 py-1" 
                                                       target="_blank"
                                                       title="View Receipt">
                                                        <i class="fas fa-receipt"></i> Receipt
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
                <input type="hidden" name="member_name" value="{{ $loan->member->fname ?? '' }} {{ $loan->member->lname ?? '' }}">
                <input type="hidden" id="schedule_id" name="s_id">
                
                <div class="modal-body bg-white">
                    <!-- Mobile Money Processing Alert (Hidden by default) -->
                    <div id="repaymentMmProcessingAlert" class="alert alert-info" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <span id="repaymentMmStatusText">Processing payment...</span>
                        </div>
                        <div class="mt-2">
                            <small id="repaymentMmCountdown" class="text-muted"></small>
                        </div>
                    </div>

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
                        <input type="text" class="form-control bg-white" id="member_phone" name="member_phone" readonly value="{{ $loan->member->contact ?? '' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark">Repayment Details</label>
                        <textarea class="form-control bg-white" name="details" rows="3" maxlength="200" placeholder="Type Here (max 200 characters)..." required></textarea>
                        <small class="text-muted">Keep it brief - only essential payment information</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark">Transaction Generated By</label>
                        <div class="text-muted">{{ trim((Auth::user()->fname ?? '') . ' ' . (Auth::user()->lname ?? '')) ?: (Auth::user()->name ?? 'System') }}, {{ date('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
                
                <div class="modal-footer bg-white border-0" id="repaymentModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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

{{-- Reschedule Modal --}}
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="mdi mdi-calendar-refresh"></i> Reschedule Overdue Payments</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rescheduleForm">
                <div class="modal-body bg-white">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i>
                        <strong>System Upgrade Reschedule</strong><br>
                        <small>This will shift all overdue unpaid schedules forward to remove late fees caused by the system upgrade period.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="rescheduleAction" required>
                            <option value="">Select Rescheduling Option</option>
                            <option value="start_today">Start repayments from today</option>
                            <option value="custom_days">Postpone by custom days</option>
                        </select>
                    </div>

                    <div class="mb-3" id="customDaysDiv" style="display: none;">
                        <label class="form-label">Number of Days to Postpone <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="postponeDays" name="days" min="1" max="365" value="7">
                        <div class="form-text">Enter the number of days to shift all schedules forward (e.g., 7 for one week)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Waive Late Fees? <span class="text-danger">*</span></label>
                        <select class="form-select" name="waive_fees" required>
                            <option value="1" selected>Yes - Waive all late fees</option>
                            <option value="0">No - Keep late fees</option>
                        </select>
                        <div class="form-text">Recommended: Waive late fees if delay was due to system upgrade</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason for Rescheduling <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required>System upgrade maintenance period - rescheduling to remove unwarranted late fees</textarea>
                        <div class="form-text">Provide a reason for audit purposes (minimum 10 characters)</div>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="mdi mdi-alert"></i> <strong>This will update {{ $overdueCount }} overdue schedule(s)</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="rescheduleSubmitBtn">
                        <i class="mdi mdi-calendar-refresh"></i> Reschedule Payments
                    </button>
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
    
    // Validate phone number
    if (!memberPhone) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Phone Number',
            text: 'Member phone number is required for mobile money payments.',
            confirmButtonText: 'OK'
        }).then(() => {
            $('#repaymentModal').modal('show');
        });
        return;
    }
    
    // Keep modal open and prevent dismissal
    const modal = $('#repaymentModal');
    modal.modal({
        backdrop: 'static',
        keyboard: false
    });
    
    // Hide the close button and footer during processing
    $('#repaymentModal .btn-close').hide();
    $('#repaymentModalFooter').hide();
    
    // Show processing alert in modal
    const processingAlert = $('#repaymentMmProcessingAlert');
    const statusText = $('#repaymentMmStatusText');
    const countdown = $('#repaymentMmCountdown');
    
    // Disable form inputs
    $('#repaymentForm input, #repaymentForm select, #repaymentForm textarea').prop('disabled', true);
    
    // Show processing alert at the top
    processingAlert.removeClass('alert-info alert-success alert-warning alert-danger').addClass('alert-info').show();
    statusText.text('Sending payment request to member\'s phone...');
    countdown.text('');
    
    // Submit the payment request to new mobile money endpoint
    $.ajax({
        url: '{{ route("admin.loans.repayments.store-mobile-money") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                const transactionRef = response.transaction_reference;
                const repaymentId = response.repayment_id;
                
                // Update status - USSD sent
                processingAlert.removeClass('alert-info').addClass('alert-success');
                statusText.html('<i class="fas fa-check-circle me-1"></i> USSD prompt sent! Please check your phone and enter your PIN.');
                
                // Start 30-second wait countdown
                let waitSeconds = 30;
                countdown.html(`<strong>Waiting ${waitSeconds} seconds before checking status...</strong>`);
                
                const waitTimer = setInterval(() => {
                    waitSeconds--;
                    countdown.html(`<strong>Waiting ${waitSeconds} seconds before checking status...</strong>`);
                    
                    if (waitSeconds <= 0) {
                        clearInterval(waitTimer);
                        // After 30 seconds, start polling for 120 seconds
                        statusText.html('<i class="fas fa-sync fa-spin me-1"></i> Checking payment status...');
                        countdown.html('');
                        startRepaymentPollingInModal(transactionRef, repaymentId, memberPhone, amount, network);
                    }
                }, 1000);
            } else {
                // Show error in modal
                processingAlert.removeClass('alert-info').addClass('alert-danger');
                statusText.html('<i class="fas fa-times-circle me-1"></i> ' + (response.message || 'Payment initiation failed'));
                countdown.html('<button type="button" class="btn btn-sm btn-warning mt-2" onclick="retryFromModal()"><i class="fas fa-redo me-1"></i> Retry Payment</button>');
                
                // Re-enable form and show close options
                $('#repaymentForm input, #repaymentForm select, #repaymentForm textarea').prop('disabled', false);
                $('#repaymentModalFooter').show();
                $('#repaymentModal .btn-close').show();
                $('#repaymentModal').modal({backdrop: true, keyboard: true});
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred';
            
            // Show error in modal
            processingAlert.removeClass('alert-info').addClass('alert-danger');
            statusText.html('<i class="fas fa-times-circle me-1"></i> ' + message);
            countdown.html('<button type="button" class="btn btn-sm btn-warning mt-2" onclick="retryFromModal()"><i class="fas fa-redo me-1"></i> Retry Payment</button>');
            
            // Re-enable form and show close options
            $('#repaymentForm input, #repaymentForm select, #repaymentForm textarea').prop('disabled', false);
            $('#repaymentModalFooter').show();
            $('#repaymentModal .btn-close').show();
            $('#repaymentModal').modal({backdrop: true, keyboard: true});
        }
    });
}

function retryFromModal() {
    // Reset and show form again
    const processingAlert = $('#repaymentMmProcessingAlert');
    processingAlert.hide();
    $('#repaymentForm input, #repaymentForm select, #repaymentForm textarea').prop('disabled', false);
    $('#repaymentModalFooter').show();
    $('#repaymentModal .btn-close').show();
    
    // Re-enable modal dismissal
    $('#repaymentModal').modal({
        backdrop: true,
        keyboard: true
    });
}

function startRepaymentPollingInModal(transactionRef, repaymentId, memberPhone, amount, network) {
    let pollAttempts = 0;
    const maxPolls = 24; // 24 × 5 seconds = 120 seconds
    
    const processingAlert = $('#repaymentMmProcessingAlert');
    const statusText = $('#repaymentMmStatusText');
    const countdown = $('#repaymentMmCountdown');
    
    // Countdown timer
    let secondsRemaining = 120;
    
    const countdownTimer = setInterval(() => {
        secondsRemaining--;
        countdown.html(`<strong>Time remaining: ${secondsRemaining}s (Attempt ${pollAttempts + 1}/${maxPolls})</strong>`);
        
        if (secondsRemaining <= 0) {
            clearInterval(countdownTimer);
            clearInterval(pollingTimer);
            handleRepaymentTimeoutInModal(repaymentId, transactionRef);
        }
    }, 1000);
    
    // Poll every 5 seconds
    const pollingTimer = setInterval(() => {
        pollAttempts++;
        checkRepaymentStatusInModal(transactionRef, repaymentId, pollingTimer, countdownTimer, pollAttempts, maxPolls);
    }, 5000);
    
    // First immediate check
    checkRepaymentStatusInModal(transactionRef, repaymentId, pollingTimer, countdownTimer, 1, maxPolls);
}

function checkRepaymentStatusInModal(transactionRef, repaymentId, pollingTimer, countdownTimer, attempt, maxAttempts) {
    $.ajax({
        url: '{{ url("admin/loans/repayments/check-mm-status") }}/' + transactionRef,
        method: 'GET',
        success: function(response) {
            if (response.status === 'completed') {
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                
                const processingAlert = $('#repaymentMmProcessingAlert');
                const statusText = $('#repaymentMmStatusText');
                const countdown = $('#repaymentMmCountdown');
                
                let lateFeeMessage = '';
                if (response.late_fee_applied) {
                    lateFeeMessage = `<div class="alert alert-warning mt-2 mb-0"><i class="fas fa-exclamation-triangle me-1"></i> <strong>Late Fee Applied:</strong> UGX ${parseFloat(response.late_fee_amount).toLocaleString()} (${response.late_fee_days} day(s) late)</div>`;
                }
                
                processingAlert.removeClass('alert-info').addClass('alert-success');
                statusText.html('<i class="fas fa-check-circle me-1"></i> ' + response.message);
                countdown.html(lateFeeMessage);
                
                // Auto-close modal after 2 seconds and reload page
                setTimeout(function() {
                    $('#repaymentModal').modal('hide');
                    window.location.href = '{{ route("admin.loans.repayments.schedules", ["id" => $loan->id]) }}';
                }, 2000);
                
            } else if (response.status === 'failed') {
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                
                const processingAlert = $('#repaymentMmProcessingAlert');
                const statusText = $('#repaymentMmStatusText');
                const countdown = $('#repaymentMmCountdown');
                
                processingAlert.removeClass('alert-info').addClass('alert-danger');
                statusText.html('<i class="fas fa-times-circle me-1"></i> ' + (response.message || 'Payment failed'));
                countdown.html('<button type="button" class="btn btn-sm btn-warning mt-2" onclick="showRetryRepaymentModal(' + repaymentId + ')"><i class="fas fa-redo me-1"></i> Retry Payment</button>');
                
            } else if (attempt >= maxAttempts) {
                // Max attempts reached
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                handleRepaymentTimeoutInModal(repaymentId, transactionRef);
            }
            // If pending, continue polling
        },
        error: function() {
            console.log('Polling error, retrying...');
            // Continue polling on network errors
        }
    });
}

function handleRepaymentTimeoutInModal(repaymentId, transactionRef) {
    const processingAlert = $('#repaymentMmProcessingAlert');
    const statusText = $('#repaymentMmStatusText');
    const countdown = $('#repaymentMmCountdown');
    
    processingAlert.removeClass('alert-info').addClass('alert-warning');
    statusText.html('<i class="fas fa-clock me-1"></i> Payment verification timeout');
    countdown.html(`
        <p class="mb-2">The 2-minute verification period has expired. If you completed the payment, it may still be processing.</p>
        <button type="button" class="btn btn-sm btn-info me-2" onclick="checkRepaymentStatusManually('${transactionRef}', ${repaymentId})"><i class="fas fa-sync me-1"></i> Check Status Again</button>
        <button type="button" class="btn btn-sm btn-warning me-2" onclick="showRetryRepaymentModal(${repaymentId})"><i class="fas fa-redo me-1"></i> Cancel & Retry</button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="window.location.reload()">Close</button>
    `);
}

function checkRepaymentStatusManually(transactionRef, repaymentId) {
    const processingAlert = $('#repaymentMmProcessingAlert');
    const statusText = $('#repaymentMmStatusText');
    const countdown = $('#repaymentMmCountdown');
    
    processingAlert.removeClass('alert-warning').addClass('alert-info');
    statusText.html('<i class="fas fa-sync fa-spin me-1"></i> Checking status...');
    countdown.html('');
    
    $.ajax({
        url: '{{ url("admin/loans/repayments/check-mm-status") }}/' + transactionRef,
        method: 'GET',
        success: function(response) {
            if (response.status === 'completed') {
                let lateFeeMessage = '';
                if (response.late_fee_applied) {
                    lateFeeMessage = `<div class="alert alert-warning mt-2 mb-0"><i class="fas fa-exclamation-triangle me-1"></i> <strong>Late Fee Applied:</strong> UGX ${parseFloat(response.late_fee_amount).toLocaleString()} (${response.late_fee_days} day(s) late)</div>`;
                }
                
                processingAlert.removeClass('alert-info').addClass('alert-success');
                statusText.html('<i class="fas fa-check-circle me-1"></i> ' + response.message);
                countdown.html(lateFeeMessage + '<button type="button" class="btn btn-sm btn-success mt-2" onclick="window.location.reload()"><i class="fas fa-check me-1"></i> Done</button>');
            } else if (response.status === 'failed') {
                processingAlert.removeClass('alert-info').addClass('alert-danger');
                statusText.html('<i class="fas fa-times-circle me-1"></i> ' + response.message);
                countdown.html('<button type="button" class="btn btn-sm btn-warning mt-2" onclick="showRetryRepaymentModal(' + repaymentId + ')"><i class="fas fa-redo me-1"></i> Retry Payment</button>');
            } else {
                processingAlert.removeClass('alert-info').addClass('alert-warning');
                statusText.html('<i class="fas fa-clock me-1"></i> Payment still pending');
                countdown.html('<p class="mb-2">The payment is still being processed. Please check again in a few minutes.</p><button type="button" class="btn btn-sm btn-secondary mt-2" onclick="window.location.reload()">Close</button>');
            }
        },
        error: function() {
            processingAlert.removeClass('alert-info').addClass('alert-danger');
            statusText.html('<i class="fas fa-times-circle me-1"></i> Could not check payment status');
            countdown.html('<button type="button" class="btn btn-sm btn-secondary mt-2" onclick="window.location.reload()">Close</button>');
        }
    });
}

// Keep old functions for compatibility but redirect to modal-based ones
function startRepaymentPolling(transactionRef, repaymentId, memberPhone, amount, network) {
    startRepaymentPollingInModal(transactionRef, repaymentId, memberPhone, amount, network);
}

function checkRepaymentStatus(transactionRef, repaymentId, pollingTimer, countdownTimer, attempt, maxAttempts) {
    checkRepaymentStatusInModal(transactionRef, repaymentId, pollingTimer, countdownTimer, attempt, maxAttempts);
}

function handleRepaymentTimeout(repaymentId, transactionRef) {
    handleRepaymentTimeoutInModal(repaymentId, transactionRef);
}

/**
 * Check progress of pending payments for a schedule
 * Finds all pending mobile money repayments for the schedule and checks their status
 */
function checkScheduleProgress(scheduleId) {
    Swal.fire({
        title: 'Checking Payment Status',
        html: '<i class="fas fa-spinner fa-spin"></i> Please wait...',
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    // Get pending repayments for this schedule
    $.ajax({
        url: '{{ url("admin/loans/repayments/schedule-pending") }}/' + scheduleId,
        method: 'GET',
        success: function(response) {
            if (response.success && response.pending_repayments.length > 0) {
                // Check status of each pending repayment
                const repayment = response.pending_repayments[0]; // Get the first pending one
                const transactionRef = repayment.transaction_reference;
                
                $.ajax({
                    url: '{{ url("admin/loans/repayments/check-mm-status") }}/' + transactionRef,
                    method: 'GET',
                    success: function(statusResponse) {
                        if (statusResponse.status === 'completed') {
                            let lateFeeMessage = '';
                            if (statusResponse.late_fee_applied) {
                                lateFeeMessage = `<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle me-1"></i> <strong>Late Fee Applied:</strong> UGX ${parseFloat(statusResponse.late_fee_amount).toLocaleString()} (${statusResponse.late_fee_days} day(s) late)</div>`;
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Completed',
                                html: statusResponse.message + lateFeeMessage,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else if (statusResponse.status === 'failed') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Payment Failed',
                                html: statusResponse.message + '<br><br><small>You can retry the payment using the "Retry" button</small>',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'info',
                                title: 'Still Pending',
                                html: 'Payment is still being processed. Please check again in a few moments.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not check payment status. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No Pending Payments',
                    text: 'No pending mobile money payments found for this schedule.',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not retrieve pending payments. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    });
}

function showRetryRepaymentModal(repaymentId) {
    // Get repayment details
    $.ajax({
        url: '{{ url("admin/loans/repayments/get") }}/' + repaymentId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const repayment = response.repayment;
                
                Swal.fire({
                    title: 'Retry Mobile Money Payment',
                    html: `
                        <form id="retryRepaymentForm">
                            <input type="hidden" name="repayment_id" value="${repayment.id}">
                            <input type="hidden" name="member_name" value="{{ $loan->member->fname ?? '' }} {{ $loan->member->lname ?? '' }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            
                            <div class="mb-3 text-start">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="member_phone" class="form-control" 
                                       value="${repayment.payment_phone || ''}" required>
                            </div>
                            
                            <div class="mb-3 text-start">
                                <label class="form-label">Amount (UGX)</label>
                                <input type="number" name="amount" class="form-control" 
                                       value="${repayment.amount}" required step="0.01">
                            </div>
                            
                            <div class="mb-3 text-start">
                                <label class="form-label">Details (Optional)</label>
                                <textarea name="details" class="form-control" rows="2">${repayment.details || ''}</textarea>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-redo"></i> Retry Payment',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    preConfirm: () => {
                        const formData = $('#retryRepaymentForm').serialize();
                        
                        return $.ajax({
                            url: '{{ route("admin.loans.repayments.retry-mobile-money") }}',
                            method: 'POST',
                            data: formData
                        });
                    },
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed && result.value.success) {
                        const phone = $('input[name="member_phone"]').val();
                        const amount = $('input[name="amount"]').val();
                        const transactionRef = result.value.transaction_reference;
                        const newRepaymentId = result.value.repayment_id || repaymentId;
                        
                        // Start the 30s + 120s flow again
                        Swal.fire({
                            title: 'USSD Prompt Sent!',
                            html: `
                                <div class="text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-mobile-alt fa-3x text-primary"></i>
                                    </div>
                                    <p>Phone: <strong>${phone}</strong></p>
                                    <p>Amount: <strong>UGX ${parseFloat(amount).toLocaleString()}</strong></p>
                                    <hr>
                                    <p class="text-info">
                                        <i class="fas fa-check-circle"></i> 
                                        Please check your phone for the USSD prompt
                                    </p>
                                    <p class="text-muted mt-3">
                                        Waiting <span id="retry_wait_countdown">30</span> seconds before checking status...
                                    </p>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                let waitSeconds = 30;
                                const waitCountdownElement = document.getElementById('retry_wait_countdown');
                                
                                const waitTimer = setInterval(() => {
                                    waitSeconds--;
                                    if (waitCountdownElement) {
                                        waitCountdownElement.textContent = waitSeconds;
                                    }
                                    
                                    if (waitSeconds <= 0) {
                                        clearInterval(waitTimer);
                                        startRepaymentPolling(transactionRef, newRepaymentId, phone, amount, 'Mobile Money');
                                    }
                                }, 1000);
                            }
                        });
                    } else if (result.isDismissed) {
                        window.location.reload();
                    }
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not load repayment details',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Cash/bank payment submission
function submitRepayment(formData) {
    $.ajax({
        url: '{{ route("admin.loans.repayments.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#repaymentModal').modal('hide');
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

// Reschedule Functions
function openRescheduleModal() {
    const modal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
    modal.show();
}

// Show/hide custom days input based on action selection
document.getElementById('rescheduleAction').addEventListener('change', function() {
    const customDaysDiv = document.getElementById('customDaysDiv');
    const postponeDaysInput = document.getElementById('postponeDays');
    
    if (this.value === 'custom_days') {
        customDaysDiv.style.display = 'block';
        postponeDaysInput.required = true;
    } else if (this.value === 'start_today') {
        customDaysDiv.style.display = 'none';
        postponeDaysInput.required = false;
        // Calculate days from first overdue to today
        // This will be handled in the backend
    } else {
        customDaysDiv.style.display = 'none';
        postponeDaysInput.required = false;
    }
});

// Handle reschedule form submission
document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const action = document.getElementById('rescheduleAction').value;
    
    if (!action) {
        Swal.fire({
            icon: 'warning',
            title: 'Action Required',
            text: 'Please select a rescheduling option'
        });
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('rescheduleSubmitBtn');
    const originalBtnText = submitBtn.innerHTML;
    
    // Calculate days based on action
    if (action === 'start_today') {
        // Backend will calculate days to shift to start from today
        formData.set('days', 'auto');
        formData.set('action', 'start_today');
    } else {
        formData.set('action', 'custom_days');
    }
    
    // Confirm action
    Swal.fire({
        title: 'Confirm Reschedule',
        html: `
            <p>This will reschedule all <strong>{{ $overdueCount }} overdue payment(s)</strong> for this loan.</p>
            ${action === 'start_today' ? '<p>Schedules will be adjusted to start from <strong>today</strong>.</p>' : ''}
            <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reschedule',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Rescheduling...';
            
            fetch('{{ route("admin.loans.reschedule", $loan->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Close modal and reload page
                        bootstrap.Modal.getInstance(document.getElementById('rescheduleModal')).hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to reschedule loan'
                    });
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while rescheduling: ' + error.message
                });
            });
        }
    });
});

// Print function
function printSchedules() {
    window.print();
}
</script>

<style type="text/css" media="print">
    @media print {
        /* Force hide everything first */
        * {
            visibility: hidden !important;
        }
        
        /* Make only printable sections and their children visible */
        #loan-summary-section,
        #loan-summary-section *,
        #repayment-schedules-section,
        #repayment-schedules-section * {
            visibility: visible !important;
        }
        
        /* Position printable sections at top */
        #loan-summary-section,
        #repayment-schedules-section {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
        }
        
        #repayment-schedules-section {
            top: 300mm !important; /* Adjust based on loan summary height */
        }
        
        /* Hide buttons within printable sections */
        .btn,
        button {
            display: none !important;
            visibility: hidden !important;
        }
        
        /* Reset body and containers */
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Page setup */
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: white;
        }
        
        /* Header styling */
        .page-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10mm;
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 5mm;
        }
        
        /* Card styling */
        .card {
            border: 1px solid #ddd;
            page-break-inside: avoid;
            margin-bottom: 5mm;
            box-shadow: none !important;
        }
        
        .card-header {
            background-color: #f5f5f5 !important;
            border-bottom: 1px solid #ddd;
            padding: 3mm;
            font-weight: bold;
        }
        
        .card-body {
            padding: 3mm;
        }
        
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            page-break-inside: auto;
        }
        
        thead {
            display: table-header-group;
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        th {
            background-color: #f0f0f0 !important;
            border: 1px solid #999;
            padding: 2mm;
            font-weight: bold;
            text-align: left;
        }
        
        td {
            border: 1px solid #ddd;
            padding: 2mm;
        }
        
        /* Badge styling */
        .badge {
            border: 1px solid #666;
            padding: 1mm 2mm;
            font-size: 8pt;
            background-color: transparent !important;
            color: #000 !important;
        }
        
        .bg-success { border-color: #28a745; }
        .bg-warning { border-color: #ffc107; }
        .bg-danger { border-color: #dc3545; }
        .bg-info { border-color: #17a2b8; }
        
        /* Text colors */
        .text-success { color: #28a745 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-warning { color: #856404 !important; }
        .text-primary { color: #0066cc !important; }
        .text-muted { color: #666 !important; }
        
        /* Loan summary grid */
        .border-end {
            border-right: 1px solid #ddd !important;
        }
        
        /* Print header - removed duplicate title */
        
        /* Print footer with page numbers */
        @page {
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
            }
            @bottom-left {
                content: "Printed: " date();
            }
        }
        
        /* Ensure content doesn't overflow */
        * {
            box-sizing: border-box;
        }
        
        img {
            max-width: 100%;
            height: auto;
        }
    }
</style>
@endpush
@endsection