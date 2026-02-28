@extends('layouts.admin')

@section('title', 'Repayment Schedules - ' . $loan->loan_code)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    #schedulesTable.schedules-table-compact {
        table-layout: fixed;
        width: 100%;
        font-size: 0.72rem;
    }

    #schedulesTable.schedules-table-compact th,
    #schedulesTable.schedules-table-compact td {
        padding: 0.2rem 0.25rem;
        vertical-align: middle;
    }

    #schedulesTable.schedules-table-compact th {
        white-space: normal;
        line-height: 1.1;
    }

    #schedulesTable.schedules-table-compact td {
        white-space: nowrap;
    }

    #schedulesTable.schedules-table-compact .btn.btn-sm {
        padding: 0.12rem 0.35rem;
        font-size: 0.67rem;
    }

    #schedulesTable.schedules-table-compact .badge {
        font-size: 0.65rem;
        padding: 0.2em 0.45em;
    }

    #schedulesTable.schedules-table-compact small {
        font-size: 0.62rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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
                        <span class="badge bg-danger ms-2">{{ number_format($loan->days_overdue, 0) }} days overdue</span>
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
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary" onclick="printSchedules()">
                            <i class="mdi mdi-printer"></i> Print Schedules
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export to PDF
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export to Excel
                        </button>
                    </div>
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
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Late Fees</h6>
                                <p class="mb-1"><strong>Total Late Fees:</strong> <span class="text-danger">UGX {{ number_format($totalLateFees ?? 0, 0) }}</span></p>
                                <p class="mb-1"><strong>Late Fees Paid:</strong> <span class="text-success">UGX {{ number_format($lateFeesPaid ?? 0, 0) }}</span></p>
                                <p class="mb-0"><strong>Late Fees Waived:</strong> <span class="text-info">UGX {{ number_format($lateFeesWaived ?? 0, 0) }}</span></p>
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
                                        <p class="mb-1"><strong>Days Late:</strong> <span class="text-danger">{{ number_format($loan->days_overdue, 0) }} {{ $loan->days_overdue == 1 ? 'day' : 'days' }}</span></p>
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
                            @php
                                // Use total_balance which includes P+I+Late Fees
                                $nextDueRemaining = $nextDue->total_balance ?? (($nextDue->total_payment ?? $nextDue->payment) - $nextDue->paid);
                            @endphp
                            <button type="button" class="btn btn-light btn-sm" 
                                    onclick="openRepayModal({{ $nextDue->id }}, '{{ $nextDue->due_date }}', {{ $nextDueRemaining }})">
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
                        @if($nextDue && isset($nextDueRemaining))
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success w-100" 
                                        onclick="openRepayModal({{ $nextDue->id }}, '{{ $nextDue->due_date }}', {{ $nextDueRemaining }})">
                                    <i class="mdi mdi-cash-fast me-1"></i>
                                    Record Payment
                                    <br><small>UGX {{ number_format($nextDueRemaining, 0) }}</small>
                                </button>
                            </div>
                        @endif
                        
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success w-100" onclick="$('#payBalanceModal').modal('show')">
                                <i class="mdi mdi-cash-check me-1"></i>
                                Pay Balance
                                <br><small>Clear specific balances</small>
                            </button>
                        </div>
                        
                        @if(auth()->user()->hasRole(['Super Administrator', 'superadmin', 'Administrator', 'administrator']))
                            @php
                                // Check for pending late fees in late_fees table (already recorded)
                                $unpaidLateFees = DB::table('late_fees')
                                    ->where('loan_id', $loan->id)
                                    ->where('status', 0)
                                    ->sum('amount');
                                
                                // Also check for calculated penalties from overdue schedules (not yet in late_fees table)
                                $calculatedLateFees = $schedules->where('status', 0)->sum('penalty');
                                
                                // Show button if either has late fees
                                $totalLateFees = $unpaidLateFees > 0 ? $unpaidLateFees : $calculatedLateFees;
                            @endphp
                            @if($totalLateFees > 0)
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-warning w-100" onclick="$('#waiveLateFeeModal').modal('show')">
                                        <i class="mdi mdi-cash-remove me-1"></i>
                                        Waive Late Fees
                                        <br><small>UGX {{ number_format($totalLateFees, 0) }} calculated</small>
                                    </button>
                                </div>
                            @endif
                        @endif
                        
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
                                <button class="btn btn-light dropdown-toggle w-100" type="button" id="printReportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-printer me-1"></i>
                                    Print Reports
                                    <br><small>Statements & notices</small>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="printReportsDropdown">
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
                @php
                    // Check if any schedule has excess amount > 0
                    $hasExcessAmounts = false;
                    foreach($schedules as $s) {
                        if(isset($s->excess_amount) && $s->excess_amount > 1) {
                            $hasExcessAmounts = true;
                            break;
                        }
                    }
                @endphp
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover schedules-table-compact" id="schedulesTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 3%;">#</th>
                                    <th style="width: 6%;">Date</th>
                                    <th style="width: 5%;">Principal</th>
                                    {{-- <th style="width: 6%;">Original Interest</th> --}}
                                    {{-- <th style="width: 6%;">Principal cal Intrest</th> --}}
                                    {{-- <th style="width: 6%;">Principal Bal</th> --}}
                                    {{-- <th style="width: 6%;">Principal for Intrest payable</th> --}}
                                    <th style="width: 5%;">Int. Pay.</th>
                                    <th style="width: 4%;">Arrears</th>
                                    <th style="width: 5%;">Late Fees</th>
                                    <th style="width: 5%;">Total Pay.</th>
                                    <th style="width: 5%;">Prin. Paid</th>
                                    <th style="width: 5%;">Int. Paid</th>
                                    <th style="width: 5%;">Late Paid</th>
                                    <th style="width: 6%;">Paid</th>
                                    <th style="width: 5%;">Balance</th>
                                    @if($hasExcessAmounts)
                                        <th style="width: 5%;">Excess</th>
                                    @endif
                                    <th style="width: 4%;">Status</th>
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
                                        {{-- <td class="text-end">{{ number_format($schedule->interest, 0) }}</td> --}}
                                        {{-- <td class="text-end">{{ number_format($schedule->pricipalcalIntrest, 0) }}</td> --}}
                                        {{-- <td class="text-end">{{ number_format($schedule->principal_balance, 0) }}</td> --}}
                                        {{-- <td class="text-end">{{ number_format($schedule->globalprincipal, 0) }}</td> --}}
                                        <td class="text-end">{{ number_format($schedule->intrestamtpayable, 0) }}</td>
                                        <td class="text-center">{{ number_format($schedule->periods_in_arrears, 0) }}</td>
                                        <td class="text-end">
                                            @php
                                                $originalLateFee = $schedule->penalty_original ?? $schedule->penalty;
                                                $waivedAmount = $schedule->penalty_waived ?? 0;
                                            @endphp
                                            @if($originalLateFee > 0)
                                                <span class="text-danger">{{ number_format($originalLateFee, 0) }}</span>
                                                @if($waivedAmount > 0)
                                                    <br><small class="text-info">({{ number_format($waivedAmount, 0) }} waived)</small>
                                                @endif
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
                                            @php
                                                $displayBalance = (float) ($schedule->total_balance ?? 0);
                                            @endphp
                                            @if($displayBalance > 1)
                                                <strong class="text-danger">{{ number_format($displayBalance, 0) }}</strong>
                                            @else
                                                <span class="text-success">0</span>
                                            @endif
                                        </td>
                                        @if($hasExcessAmounts)
                                            <td class="text-center">
                                                @php
                                                    $excessAmount = $schedule->excess_amount ?? 0;
                                                @endphp
                                                @if($excessAmount > 1)
                                                    <span class="text-success fw-bold">{{ number_format($excessAmount, 0) }}</span>
                                                    <br>
                                                    @if(auth()->user()->hasRole(['Super Administrator', 'superadmin', 'Administrator', 'administrator']))
                                                        <button type="button" class="btn btn-info btn-sm px-2 py-1 mt-1" 
                                                                onclick="openCarryOverModal({{ $schedule->id }}, {{ $excessAmount }}, '{{ date('M d, Y', strtotime($schedule->payment_date)) }}')"
                                                                title="Carry Over Excess to Next Schedule">
                                                            <i class="fas fa-arrow-right"></i> Carry Over
                                                        </button>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td class="text-center">{!! $statusBadge !!}</td>
                                        <td class="text-center">
                                            @if($schedule->status == 1)
                                                {{-- Paid - Show Receipt Button + View All Payments --}}
                                                @php
                                                    $allPayments = \App\Models\Repayment::where('schedule_id', $schedule->id)
                                                        ->where(function($query) {
                                                            $query->where('status', 1)
                                                                  ->orWhere('payment_status', 'Completed');
                                                        })
                                                        ->orderBy('id', 'desc')
                                                        ->get();
                                                    $repayment = $allPayments->first();
                                                    $paymentCount = $allPayments->count();
                                                @endphp
                                                @if($repayment)
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('admin.repayments.receipt', $repayment->id) }}" 
                                                           class="btn btn-primary btn-sm px-2 py-1" 
                                                           target="_blank"
                                                           title="View Latest Receipt">
                                                            <i class="fas fa-receipt"></i> Receipt
                                                        </a>
                                                        @if($paymentCount > 1)
                                                            <button type="button" 
                                                                    class="btn btn-info btn-sm px-2 py-1" 
                                                                    onclick="showAllPayments({{ $schedule->id }})"
                                                                    title="View All {{ $paymentCount }} Payments">
                                                                <i class="fas fa-list"></i> ({{ $paymentCount }})
                                                            </button>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">Paid</span>
                                                @endif
                                            @elseif($schedule->status == 0 && $schedule->pending_count == 0)
                                                {{-- Not Paid - Show Repay Button only if balance > 1 AND no earlier unpaid schedules --}}
                                                @php
                                                    // Use total_balance (includes P+I+Late Fees) calculated by controller
                                                    $scheduleRemaining = $schedule->total_balance ?? 0;
                                                    // If balance is less than 1 UGX, consider it paid (rounding tolerance)
                                                    $shouldShowRepay = $scheduleRemaining > 1;
                                                    
                                                    // SEQUENTIAL PAYMENT ENFORCEMENT: Check if any earlier schedule is unpaid
                                                    $hasEarlierUnpaid = false;
                                                    $earlierUnpaidDate = null;
                                                    $earlierUnpaidBalance = 0;
                                                    
                                                    if ($shouldShowRepay) {
                                                        foreach ($schedules as $earlierSchedule) {
                                                            // Check if this is an earlier schedule (by date)
                                                            if ($earlierSchedule->payment_date < $schedule->payment_date) {
                                                                $earlierBalance = $earlierSchedule->total_balance ?? 0;
                                                                // If earlier schedule has balance > 1, it's unpaid
                                                                if ($earlierBalance > 1) {
                                                                    $hasEarlierUnpaid = true;
                                                                    $earlierUnpaidDate = date('d-M-Y', strtotime($earlierSchedule->payment_date));
                                                                    $earlierUnpaidBalance = $earlierBalance;
                                                                    break; // Found first unpaid earlier schedule
                                                                }
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                @if($shouldShowRepay && !$hasEarlierUnpaid)
                                                    <button type="button" class="btn btn-success btn-sm px-2 py-1" 
                                                            onclick="openRepayModal({{ $schedule->id }}, '{{ date('M d, Y', strtotime($schedule->payment_date)) }}', {{ $scheduleRemaining }})"
                                                            title="Repay">
                                                        Repay
                                                    </button>
                                                @elseif($shouldShowRepay && $hasEarlierUnpaid)
                                                    <button type="button" class="btn btn-secondary btn-sm px-2 py-1" 
                                                            disabled
                                                            title="Cannot pay this schedule. Please pay the earlier schedule due on {{ $earlierUnpaidDate }} first (Balance: UGX {{ number_format($earlierUnpaidBalance, 0) }})">
                                                        <i class="fas fa-lock"></i> Locked
                                                    </button>
                                                @else
                                                    <span class="text-muted">Paid</span>
                                                @endif
                                            @elseif($schedule->pending_count > 0)
                                                {{-- Pending - Show Check Progress and Retry Buttons (with sequential check) --}}
                                                @php
                                                    // Use total_balance for retry as well
                                                    $scheduleRetryRemaining = $schedule->total_balance ?? (($schedule->total_payment ?? $schedule->payment) - $schedule->paid);
                                                    
                                                    // Check if any earlier schedule is unpaid (same logic as above)
                                                    $hasEarlierUnpaidForRetry = false;
                                                    foreach ($schedules as $earlierSchedule) {
                                                        if ($earlierSchedule->payment_date < $schedule->payment_date) {
                                                            $earlierBalance = $earlierSchedule->total_balance ?? 0;
                                                            if ($earlierBalance > 1) {
                                                                $hasEarlierUnpaidForRetry = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-info btn-sm px-2 py-1" 
                                                            onclick="checkScheduleProgress({{ $schedule->id }})"
                                                            title="Check Payment Status">
                                                        <i class="fas fa-sync"></i> Check
                                                    </button>
                                                    @if(!$hasEarlierUnpaidForRetry)
                                                        <button type="button" class="btn btn-warning btn-sm px-2 py-1" 
                                                                onclick="openRepayModal({{ $schedule->id }}, '{{ date('M d, Y', strtotime($schedule->payment_date)) }}', {{ $scheduleRetryRemaining }})"
                                                                title="Retry Payment">
                                                            <i class="fas fa-redo"></i> Retry
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-secondary btn-sm px-2 py-1" 
                                                                disabled
                                                                title="Cannot retry - pay earlier schedule first">
                                                            <i class="fas fa-lock"></i> Locked
                                                        </button>
                                                    @endif
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
                                    {{-- <th></th> --}}
                                    {{-- <th></th> --}}
                                    {{-- <th></th> --}}
                                    {{-- <th></th> --}}
                                    <th class="text-end">
                                        <strong>{{ number_format($schedules->sum('intrestamtpayable'), 0) }}</strong>
                                    </th>
                                    <th class="text-center">
                                        <strong>{{ number_format($schedules->sum('periods_in_arrears'), 0) }}</strong>
                                    </th>
                                    <th class="text-end">
                                        <strong>{{ number_format($schedules->sum('penalty_original'), 0) }}</strong>
                                        @if($schedules->sum('penalty_waived') > 0)
                                            <br><small class="text-info">({{ number_format($schedules->sum('penalty_waived'), 0) }} waived)</small>
                                        @endif
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
                                        @php
                                            // Only sum positive balances (unpaid amounts)
                                            // Negative balances (overpayments) should not reduce the total
                                            $totalOutstanding = $schedules->sum(function($schedule) {
                                                $balance = (float) ($schedule->total_balance ?? 0);
                                                return $balance > 1 ? $balance : 0;
                                            });
                                        @endphp
                                        <strong>{{ number_format($totalOutstanding, 0) }}</strong>
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
                        <input type="text" class="form-control bg-white" id="payment_amount" name="amount" required readonly>
                        <small class="text-muted">Auto-calculated based on schedule balance</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark">Payment Type</label>
                        <select class="form-select bg-white" id="payment_type" name="type" required onchange="toggleMedium()">
                            <option value="">Select Payment Type</option>
                            @if(auth()->user()->hasRole(['Super Administrator', 'Administrator']))
                                <option value="1">Cash (Instant Confirmation)</option>
                                <option value="3">Direct Bank Transfer (Instant Confirmation)</option>
                                <option value="2">Mobile Money (Requires Callback)</option>
                            @else
                                <option value="2">Mobile Money</option>
                            @endif
                        </select>
                        <small class="text-muted">
                            @if(auth()->user()->hasRole(['Super Administrator', 'Administrator']))
                                Cash & Bank Transfer are confirmed instantly. Mobile Money requires customer approval.
                            @else
                                Only Mobile Money payments available for your role.
                            @endif
                        </small>
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


<!-- Waive Late Fees Modal -->
<div class="modal fade" id="waiveLateFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark"><i class="mdi mdi-cash-remove me-1"></i> Waive Late Fees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="waiveLateFeeForm">
                @csrf
                <div class="modal-body bg-white">
                    <div class="alert alert-warning">
                        <small><i class="mdi mdi-alert me-1"></i> <strong>Waiving late fees will permanently remove them from the system.</strong> This action should only be used when late fees were caused by system issues, upgrades, or other circumstances beyond the client's control. Select the late fees to waive below.</small>
                    </div>
                    
                    <div class="table-responsive mb-3" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAllLateFees" class="form-check-input">
                                    </th>
                                    <th>Schedule Date</th>
                                    <th>Days Overdue</th>
                                    <th>Calculated Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="lateFeeList">
                                @php
                                    // Get late fees already in the database
                                    $pendingLateFees = DB::table('late_fees')
                                        ->where('loan_id', $loan->id)
                                        ->where('status', 0)
                                        ->orderBy('calculated_date')
                                        ->get();
                                    
                                    // Get calculated penalties from overdue schedules (not yet in late_fees table)
                                    $overdueSchedules = $schedules->filter(function($schedule) {
                                        return $schedule->status == 0 && $schedule->penalty > 0;
                                    });
                                @endphp
                                
                                @if($pendingLateFees->count() > 0)
                                    {{-- Show late fees from late_fees table --}}
                                    @foreach($pendingLateFees as $lateFee)
                                        <tr>
                                            <td>
                                                <input type="checkbox" 
                                                       class="form-check-input late-fee-checkbox" 
                                                       name="late_fee_ids[]" 
                                                       value="{{ $lateFee->id }}"
                                                       data-amount="{{ $lateFee->amount }}"
                                                       data-schedule-id="{{ $lateFee->schedule_id }}"
                                                       data-schedule-date="{{ date('d-m-Y', strtotime($lateFee->schedule_due_date)) }}">
                                            </td>
                                            <td><small>{{ date('d-m-Y', strtotime($lateFee->schedule_due_date)) }}</small></td>
                                            <td><span class="badge bg-danger">{{ $lateFee->days_overdue }} days</span></td>
                                            <td><small class="text-muted">{{ date('d-m-Y', strtotime($lateFee->calculated_date)) }}</small></td>
                                            <td class="text-end text-danger"><strong>{{ number_format($lateFee->amount, 0) }}</strong></td>
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- Show calculated penalties from schedules --}}
                                    @forelse($overdueSchedules as $schedule)
                                        <tr>
                                            <td>
                                                <input type="checkbox" 
                                                       class="form-check-input late-fee-checkbox calculated-penalty" 
                                                       name="schedule_ids[]" 
                                                       value="{{ $schedule->id }}"
                                                       data-amount="{{ $schedule->penalty }}"
                                                       data-schedule-id="{{ $schedule->id }}"
                                                       data-schedule-date="{{ date('d-m-Y', strtotime($schedule->payment_date)) }}"
                                                       data-days-overdue="{{ $schedule->days_overdue }}">
                                            </td>
                                            <td><small>{{ date('d-m-Y', strtotime($schedule->payment_date)) }}</small></td>
                                            <td><span class="badge bg-danger">{{ $schedule->days_overdue }} days</span></td>
                                            <td><small class="text-muted">Calculated now</small></td>
                                            <td class="text-end text-danger"><strong>{{ number_format($schedule->penalty, 0) }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No late fees to waive</td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-secondary mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Selected Late Fees:</strong> <span id="selectedLateFeeCount">0</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Total to Waive:</strong> UGX <span id="totalToWaive">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Waiver Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="waiver_reason" id="waiverReason" rows="3" required placeholder="Explain why these late fees are being waived (e.g., System upgrade/maintenance, Technical issues, etc.)">System upgrade maintenance period - waiving unwarranted late fees</textarea>
                        <div class="form-text">This reason will be recorded in the system for audit purposes</div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmWaiver" required>
                        <label class="form-check-label" for="confirmWaiver">
                            I confirm that I have reviewed these late fees and they should be waived
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="processWaiverBtn" disabled>
                        <i class="mdi mdi-check me-1"></i> Waive Selected Fees
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Carry Over Excess Modal -->
<div class="modal fade" id="carryOverModal" tabindex="-1" aria-labelledby="carryOverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="carryOverModalLabel">
                    <i class="fas fa-arrow-right me-2"></i>Carry Over Excess Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="carryOverForm" action="{{ route('admin.loans.carry-over') }}" method="POST">
                @csrf
                <input type="hidden" name="schedule_id" id="carry_schedule_id">
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Schedule:</strong> <span id="carry_schedule_date"></span><br>
                        <strong>Excess Amount:</strong> UGX <span id="carry_excess_amount"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Apply To</label>
                        <select class="form-select" name="target_action" id="carry_target_action" required>
                            <option value="">-- Select Action --</option>
                            <option value="next_schedule">Next Unpaid Schedule</option>
                            <option value="late_fees">Late Fees on This Schedule</option>
                            <option value="specific">Specific Schedule</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="specific_schedule_group" style="display: none;">
                        <label class="form-label">Select Schedule</label>
                        <select class="form-select" name="target_schedule_id" id="target_schedule_id">
                            <option value="">-- Select Schedule --</option>
                            @foreach($schedules as $sch)
                                @if($sch->status == 0 && $sch->balance > 1)
                                    <option value="{{ $sch->id }}">
                                        {{ date('M d, Y', strtotime($sch->payment_date)) }} - 
                                        Balance: UGX {{ number_format($sch->balance, 0) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note/Reason</label>
                        <textarea class="form-control" name="carry_note" rows="2" placeholder="Optional note about this carry-over">Manual carry-over of excess payment</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-arrow-right me-1"></i> Apply Excess
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pay Balance Modal -->
<div class="modal fade" id="payBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white">
            <div class="modal-header bg-white">
                <h5 class="modal-title"><i class="mdi mdi-cash-check me-1"></i> Pay Outstanding Balances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="payBalanceForm">
                <div class="modal-body bg-white">
                    <div class="alert alert-info">
                        <small><i class="mdi mdi-information me-1"></i> Select ONE schedule to pay its outstanding balance at a time.</small>
                    </div>
                    
                    <div class="table-responsive mb-3" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th width="40">Select</th>
                                    <th>Date</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody id="balanceList">
                                @foreach($schedules as $schedule)
                                    @php
                                        $totalDue = ($schedule->principal + $schedule->interest);
                                        $totalPaid = $schedule->paid ?? 0;
                                        $balance = $totalDue - $totalPaid;
                                        // Round to 2 decimal places to avoid floating point issues
                                        $balance = round($balance, 2);
                                    @endphp
                                    @if($balance >= 1)
                                        <tr>
                                            <td>
                                                <input type="radio" 
                                                       class="form-check-input balance-radio" 
                                                       name="schedule_id" 
                                                       value="{{ $schedule->id }}"
                                                       data-balance="{{ $balance }}"
                                                       data-due-date="{{ date('d-m-Y', strtotime($schedule->payment_date)) }}">
                                            </td>
                                            <td><small>{{ date('d-m-Y', strtotime($schedule->payment_date)) }}</small></td>
                                            <td class="text-end text-danger"><strong>{{ number_format($balance, 0) }}</strong></td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-secondary mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Selected Schedule:</strong> <span id="selectedCount">None</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Amount to Pay:</strong> UGX <span id="totalToPay">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="balancePaymentAmount" name="amount" step="0.01" min="0" required readonly>
                        <div class="form-text">Amount to pay for the selected schedule</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" id="balance_payment_method" required onchange="toggleBalanceMedium()">
                            <option value="">Select Method</option>
                            @if(auth()->user()->hasRole(['Super Administrator', 'superadmin', 'Administrator', 'administrator']))
                                <option value="mobile_money">Mobile Money</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            @else
                                <option value="mobile_money">Mobile Money</option>
                            @endif
                        </select>
                    </div>
                    
                    <div class="mb-3" id="balance_medium_div" style="display: none;">
                        <label class="form-label">Mobile Money Network</label>
                        <input type="text" class="form-control" id="balance_detected_network" readonly>
                        <input type="hidden" id="balance_medium" name="medium">
                        <small class="text-muted">Auto-detected from member's phone number</small>
                    </div>
                    
                    <div class="mb-3" id="balance_phone_div" style="display: none;">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="balance_member_phone" name="member_phone" readonly value="{{ $loan->member->contact ?? ($loan->group->contact ?? '') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional payment notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="processBalancePaymentBtn" disabled>
                        <i class="mdi mdi-check me-1"></i> Process Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
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

{{-- All Payments Modal --}}
<div class="modal fade" id="allPaymentsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-white">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-list"></i> All Payments for Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-white">
                <div id="allPaymentsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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

// Toggle mobile money network field for Pay Balance modal
function toggleBalanceMedium() {
    var paymentMethod = document.getElementById('balance_payment_method').value;
    var mediumDiv = document.getElementById('balance_medium_div');
    var phoneDiv = document.getElementById('balance_phone_div');
    var mediumInput = document.getElementById('balance_medium');
    var networkDisplay = document.getElementById('balance_detected_network');
    var memberPhone = document.getElementById('balance_member_phone').value;
    
    if(paymentMethod == 'mobile_money') {
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
    $('#payment_amount').attr('max', amount.toFixed(0));
    
    // Update helper text to show this is the exact amount owed (no more, no less)
    const helperText = 'Total amount owed: UGX ' + amount.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' (exact amount required - no overpayments allowed)';
    $('#payment_amount').next('small').text(helperText);
    
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
        
        // Validate payment amount doesn't exceed schedule balance
        const paymentAmount = parseFloat($('#payment_amount').val());
        const maxAmount = parseFloat($('#payment_amount').attr('max'));
        
        if (paymentAmount > maxAmount) {
            Swal.fire({
                icon: 'error',
                title: 'Excess Payment Not Allowed',
                html: 'Payment amount <strong>UGX ' + paymentAmount.toLocaleString() + '</strong> exceeds the schedule balance of <strong>UGX ' + maxAmount.toLocaleString() + '</strong>.<br><br>Please pay the exact amount due. Overpayments are not permitted.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
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
    
    
    // Handle Pay Balance radio button selection
    function updateBalanceSelection() {
        var selectedRadio = $('.balance-radio:checked');
        
        if (selectedRadio.length > 0) {
            var balance = parseFloat(selectedRadio.data('balance'));
            var dueDate = selectedRadio.data('due-date');
            
            $('#selectedCount').text(dueDate);
            $('#totalToPay').text(balance.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
            $('#balancePaymentAmount').val(balance.toFixed(2));
            $('#processBalancePaymentBtn').prop('disabled', false);
        } else {
            $('#selectedCount').text('None');
            $('#totalToPay').text('0');
            $('#balancePaymentAmount').val('');
            $('#processBalancePaymentBtn').prop('disabled', true);
        }
    }
    
    // Handle individual radio button clicks
    $(document).on('change', '.balance-radio', function() {
        updateBalanceSelection();
    });
    
    // Handle Pay Balance form submission
    $('#payBalanceForm').on('submit', function(e) {
        e.preventDefault();
        
        var selectedRadio = $('.balance-radio:checked');
        
        if (selectedRadio.length === 0) {
            Swal.fire('Error!', 'Please select a schedule to pay', 'error');
            return;
        }
        
        var selectedSchedules = [{
            schedule_id: selectedRadio.val(),
            balance: parseFloat(selectedRadio.data('balance')),
            due_date: selectedRadio.data('due-date')
        }];
        
        var paymentMethod = $('#balance_payment_method').val();
        
        // Validate mobile money fields if mobile money is selected
        if (paymentMethod === 'mobile_money') {
            var medium = $('#balance_medium').val();
            var phone = $('#balance_member_phone').val();
            
            if (!medium || medium === '0') {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Not Detected',
                    html: 'Mobile money network could not be detected.<br><br>Phone: ' + phone + '<br><br>Please ensure the member has a valid mobile money phone number (MTN or Airtel).',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            if (!phone) {
                Swal.fire('Error!', 'Member phone number is required for mobile money payments', 'error');
                return;
            }
        }
        
        var formData = new FormData(this);
        formData.append('loan_id', {{ $loan->id }});
        formData.append('schedules', JSON.stringify(selectedSchedules));
        formData.append('_token', '{{ csrf_token() }}');
        
        // Show loading state
        $('#processBalancePaymentBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i> Processing...');
        
        $.ajax({
            url: '{{ route("admin.loans.repayments.pay-balance") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Different handling for mobile money vs cash/bank
                    if (response.payment_type === 'mobile_money') {
                        $('#payBalanceModal').modal('hide');
                        
                        // Show USSD prompt sent notification with 30s + 120s polling flow
                        const phone = $('#balance_member_phone').val();
                        const amount = $('#balancePaymentAmount').val();
                        const transactionRef = response.transaction_id;
                        const scheduleCount = response.payments_created || 1;
                        
                        // Start the 30s + 120s flow
                        Swal.fire({
                            title: 'USSD Prompt Sent!',
                            html: `
                                <div class="text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-mobile-alt fa-3x text-primary"></i>
                                    </div>
                                    <p>Phone: <strong>${phone}</strong></p>
                                    <p>Amount: <strong>UGX ${parseFloat(amount).toLocaleString()}</strong></p>
                                    <p>Schedules: <strong>${scheduleCount}</strong></p>
                                    <hr>
                                    <p class="text-info">
                                        <i class="fas fa-check-circle"></i> 
                                        Please check your phone for the USSD prompt
                                    </p>
                                    <p class="text-muted mt-3">
                                        Waiting <span id="balance_wait_countdown">30</span> seconds before checking status...
                                    </p>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                let waitSeconds = 30;
                                const waitCountdownElement = document.getElementById('balance_wait_countdown');
                                
                                const waitTimer = setInterval(() => {
                                    waitSeconds--;
                                    if (waitCountdownElement) {
                                        waitCountdownElement.textContent = waitSeconds;
                                    }
                                    
                                    if (waitSeconds <= 0) {
                                        clearInterval(waitTimer);
                                        startBalancePaymentPolling(transactionRef, phone, amount, scheduleCount);
                                    }
                                }, 1000);
                            }
                        });
                    } else {
                        $('#payBalanceModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Successful!',
                            html: response.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $('#processBalancePaymentBtn').prop('disabled', false).html('<i class="mdi mdi-check me-1"></i> Process Payment');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'An error occurred while processing the payment';
                Swal.fire('Error!', message, 'error');
                $('#processBalancePaymentBtn').prop('disabled', false).html('<i class="mdi mdi-check me-1"></i> Process Payment');
            }
        });
    });
    
    // Reset modal when closed
    $('#payBalanceModal').on('hidden.bs.modal', function() {
        $('#payBalanceForm')[0].reset();
        $('.balance-radio').prop('checked', false);
        $('#balance_medium_div').hide();
        $('#balance_phone_div').hide();
        updateBalanceSelection();
    });
    
    // Auto-detect network when modal opens
    $('#payBalanceModal').on('shown.bs.modal', function() {
        // If mobile money is the only option or is selected, trigger network detection
        var paymentMethod = $('#balance_payment_method').val();
        if (paymentMethod === 'mobile_money' || $('#balance_payment_method option').length === 2) {
            // Only one payment option (mobile money) or already selected
            if (!paymentMethod) {
                $('#balance_payment_method').val('mobile_money');
            }
            toggleBalanceMedium();
        }
    });
    
    // ==================== WAIVE LATE FEES FUNCTIONALITY ====================
    
    // Handle late fee checkbox selection
    function updateLateFeeSelection() {
        var total = 0;
        var count = 0;
        
        $('.late-fee-checkbox:checked').each(function() {
            var amount = parseFloat($(this).data('amount'));
            total += amount;
            count++;
        });
        
        $('#selectedLateFeeCount').text(count);
        $('#totalToWaive').text(total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
        
        // Enable/disable submit button based on selection and confirmation
        var hasSelection = count > 0;
        var isConfirmed = $('#confirmWaiver').is(':checked');
        $('#processWaiverBtn').prop('disabled', !(hasSelection && isConfirmed));
    }
    
    // Handle individual late fee checkbox clicks
    $(document).on('change', '.late-fee-checkbox', function() {
        updateLateFeeSelection();
    });
    
    // Handle select all late fees checkbox
    $('#selectAllLateFees').on('change', function() {
        $('.late-fee-checkbox').prop('checked', $(this).prop('checked'));
        updateLateFeeSelection();
    });
    
    // Handle confirmation checkbox
    $('#confirmWaiver').on('change', function() {
        updateLateFeeSelection();
    });
    
    // Handle Waive Late Fee form submission
    $('#waiveLateFeeForm').on('submit', function(e) {
        e.preventDefault();
        
        var selectedLateFees = [];
        $('.late-fee-checkbox:checked').each(function() {
            var item = {};
            
            // Check if this is an existing late_fee record or calculated penalty
            if ($(this).hasClass('calculated-penalty')) {
                // Calculated penalty from schedule
                item.schedule_id = $(this).data('schedule-id');
                item.amount = parseFloat($(this).data('amount'));
                item.schedule_date = $(this).data('schedule-date');
            } else {
                // Existing late_fee record
                item.late_fee_id = $(this).val();
                item.amount = parseFloat($(this).data('amount'));
                item.schedule_date = $(this).data('schedule-date');
            }
            
            selectedLateFees.push(item);
        });
        
        if (selectedLateFees.length === 0) {
            Swal.fire('Error!', 'Please select at least one late fee to waive', 'error');
            return;
        }
        
        var waiverReason = $('#waiverReason').val().trim();
        if (!waiverReason) {
            Swal.fire('Error!', 'Please provide a reason for waiving these late fees', 'error');
            return;
        }
        
        // Confirm before waiving
        var totalToWaive = selectedLateFees.reduce((sum, fee) => sum + fee.amount, 0);
        Swal.fire({
            title: 'Confirm Waiver',
            html: `You are about to waive <strong>${selectedLateFees.length} late fee(s)</strong> totaling <strong>UGX ${totalToWaive.toLocaleString('en-US', {maximumFractionDigits: 0})}</strong>.<br><br>This action cannot be undone. Continue?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f0ad4e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Waive Fees',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                processWaiver(selectedLateFees, waiverReason);
            }
        });
    });
    
    function processWaiver(selectedLateFees, waiverReason) {
        var formData = new FormData();
        formData.append('loan_id', {{ $loan->id }});
        formData.append('late_fees', JSON.stringify(selectedLateFees));
        formData.append('waiver_reason', waiverReason);
        formData.append('_token', '{{ csrf_token() }}');
        
        // Show loading state
        $('#processWaiverBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i> Processing...');
        
        $.ajax({
            url: '{{ route("admin.loans.late-fees.waive") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#waiveLateFeeModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Late Fees Waived!',
                        html: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $('#processWaiverBtn').prop('disabled', false).html('<i class="mdi mdi-check me-1"></i> Waive Selected Fees');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'An error occurred while waiving late fees';
                Swal.fire('Error!', message, 'error');
                $('#processWaiverBtn').prop('disabled', false).html('<i class="mdi mdi-check me-1"></i> Waive Selected Fees');
            }
        });
    }
    
    // Reset waive late fee modal when closed
    $('#waiveLateFeeModal').on('hidden.bs.modal', function() {
        $('#waiveLateFeeForm')[0].reset();
        $('.late-fee-checkbox').prop('checked', false);
        $('#selectAllLateFees').prop('checked', false);
        $('#confirmWaiver').prop('checked', false);
        updateLateFeeSelection();
    });
    
    // Carry Over functionality
    $('#carry_target_action').on('change', function() {
        if ($(this).val() === 'specific') {
            $('#specific_schedule_group').show();
            $('#target_schedule_id').prop('required', true);
        } else {
            $('#specific_schedule_group').hide();
            $('#target_schedule_id').prop('required', false);
        }
    });
});

function openCarryOverModal(scheduleId, excessAmount, scheduleDate) {
    $('#carry_schedule_id').val(scheduleId);
    $('#carry_schedule_date').text(scheduleDate);
    $('#carry_excess_amount').text(excessAmount.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
    $('#carry_target_action').val('').trigger('change');
    $('#carryOverModal').modal('show');
}

function showAllPayments(scheduleId) {
    // Show modal
    $('#allPaymentsModal').modal('show');
    
    // Load payments via AJAX
    $.ajax({
        url: '/admin/loans/schedules/' + scheduleId + '/payments',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let html = '<div class="table-responsive">';
                html += '<table class="table table-bordered table-hover">';
                html += '<thead class="table-light">';
                html += '<tr>';
                html += '<th>#</th>';
                html += '<th>Date</th>';
                html += '<th>Amount</th>';
                html += '<th>Status</th>';
                html += '<th>Receipt</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';
                
                if (response.payments && response.payments.length > 0) {
                    response.payments.forEach((payment, index) => {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td>' + payment.date + '</td>';
                        html += '<td class="text-end"><strong>' + payment.amount_formatted + '</strong></td>';
                        html += '<td>' + payment.status_badge + '</td>';
                        html += '<td><a href="/admin/repayments/' + payment.id + '/receipt" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-receipt"></i> View</a></td>';
                        html += '</tr>';
                    });
                    
                    html += '<tr class="table-info">';
                    html += '<td colspan="2" class="text-end"><strong>Total:</strong></td>';
                    html += '<td class="text-end"><strong>' + response.total_formatted + '</strong></td>';
                    html += '<td colspan="2"></td>';
                    html += '</tr>';
                } else {
                    html += '<tr><td colspan="5" class="text-center text-muted">No payments found</td></tr>';
                }
                
                html += '</tbody>';
                html += '</table>';
                html += '</div>';
                
                $('#allPaymentsContent').html(html);
            } else {
                $('#allPaymentsContent').html('<div class="alert alert-danger">Error loading payments</div>');
            }
        },
        error: function() {
            $('#allPaymentsContent').html('<div class="alert alert-danger">Failed to load payments. Please try again.</div>');
        }
    });
}

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

// Balance payment polling function
function startBalancePaymentPolling(transactionRef, phone, amount, scheduleCount) {
    let pollAttempts = 0;
    const maxPolls = 24; // 24 × 5 seconds = 120 seconds
    
    // Countdown timer
    let secondsRemaining = 120;
    
    const countdownTimer = setInterval(() => {
        secondsRemaining--;
        const countdownElement = document.getElementById('balance_polling_countdown');
        if (countdownElement) {
            countdownElement.textContent = secondsRemaining;
        }
        
        if (secondsRemaining <= 0) {
            clearInterval(countdownTimer);
            clearInterval(pollingTimer);
            handleBalancePaymentTimeout(transactionRef);
        }
    }, 1000);
    
    // Update Swal to show polling status
    Swal.update({
        title: 'Verifying Payment...',
        html: `
            <div class="text-center">
                <div class="mb-3">
                    <i class="fas fa-sync fa-spin fa-3x text-primary"></i>
                </div>
                <p>Phone: <strong>${phone}</strong></p>
                <p>Amount: <strong>UGX ${parseFloat(amount).toLocaleString()}</strong></p>
                <p>Schedules: <strong>${scheduleCount}</strong></p>
                <hr>
                <p class="text-info">
                    <i class="fas fa-hourglass-half"></i> 
                    Waiting for payment confirmation...
                </p>
                <p class="text-muted mt-3">
                    Time remaining: <span id="balance_polling_countdown">${secondsRemaining}</span>s
                    <br><small>Attempt <span id="balance_poll_attempt">1</span>/${maxPolls}</small>
                </p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    // Poll every 5 seconds
    const pollingTimer = setInterval(() => {
        pollAttempts++;
        const attemptElement = document.getElementById('balance_poll_attempt');
        if (attemptElement) {
            attemptElement.textContent = pollAttempts;
        }
        checkBalancePaymentStatus(transactionRef, pollingTimer, countdownTimer, pollAttempts, maxPolls);
    }, 5000);
    
    // First immediate check
    checkBalancePaymentStatus(transactionRef, pollingTimer, countdownTimer, 1, maxPolls);
}

function checkBalancePaymentStatus(transactionRef, pollingTimer, countdownTimer, attempt, maxAttempts) {
    $.ajax({
        url: '{{ url("admin/loans/repayments/check-mm-status") }}/' + transactionRef,
        method: 'GET',
        success: function(response) {
            if (response.status === 'completed') {
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Successful!',
                    html: '<i class="fas fa-check-circle"></i> ' + (response.message || 'Balance payment completed successfully'),
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
                
            } else if (response.status === 'failed') {
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    html: '<i class="fas fa-times-circle"></i> ' + (response.message || 'Payment failed'),
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
                
            } else if (attempt >= maxAttempts) {
                // Max attempts reached
                clearInterval(pollingTimer);
                clearInterval(countdownTimer);
                handleBalancePaymentTimeout(transactionRef);
            }
            // If pending, continue polling
        },
        error: function() {
            console.log('Polling error, retrying...');
            // Continue polling on network errors
        }
    });
}

function handleBalancePaymentTimeout(transactionRef) {
    Swal.fire({
        icon: 'warning',
        title: 'Payment Verification Timeout',
        html: `
            <p>The 2-minute verification period has expired.</p>
            <p>If you completed the payment, it may still be processing.</p>
            <p class="mt-3"><strong>What would you like to do?</strong></p>
            <p class="text-muted"><small>Transaction ID: ${transactionRef}</small></p>
        `,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-sync"></i> Check Status',
        denyButtonText: '<i class="fas fa-redo"></i> Retry Payment',
        cancelButtonText: 'Close',
        confirmButtonColor: '#3085d6',
        denyButtonColor: '#f0ad4e',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            // Manual status check
            Swal.fire({
                title: 'Checking Status...',
                html: '<i class="fas fa-spinner fa-spin"></i> Please wait...',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            $.ajax({
                url: '{{ url("admin/loans/repayments/check-mm-status") }}/' + transactionRef,
                method: 'GET',
                success: function(response) {
                    if (response.status === 'completed') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Confirmed!',
                            html: response.message || 'Balance payment completed successfully',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else if (response.status === 'failed') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Failed',
                            html: (response.message || 'Payment failed') + '<br><br><small>You can retry the payment using the Pay Balance button.</small>',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Still Processing',
                            html: 'Payment is still being processed. FlexiPay retries 3 times if customer cancelled.<br><br>Please check again in a few moments.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not check payment status. Please try again.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        } else if (result.isDenied) {
            // User wants to retry - reload page to start fresh
            Swal.fire({
                icon: 'info',
                title: 'Retry Payment',
                html: 'Please use the <strong>Pay Balance</strong> button again to retry the payment.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        } else {
            window.location.reload();
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

// Export to PDF function - generates and downloads PDF directly
function exportToPDF() {
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait a moment',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'mm', 'a4');
            
            // Add header
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('Emuria Business Investment and Management Software (E-BIMS) Ltd', 148.5, 15, { align: 'center' });
            
            doc.setFontSize(8);
            doc.setFont(undefined, 'normal');
            doc.text('Akisim cell, Central ward, Akore town, Kapelebyong, Uganda', 148.5, 20, { align: 'center' });
            
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('LOAN REPAYMENT SCHEDULE', 148.5, 27, { align: 'center' });
            
            doc.setFontSize(7);
            doc.setFont(undefined, 'normal');
            doc.text('Generated on: ' + new Date().toLocaleString('en-UG'), 148.5, 32, { align: 'center' });
            
            // Add loan summary
            let yPos = 38;
            doc.setFontSize(7);
            
            // Summary box
            doc.setDrawColor(51, 51, 51);
            doc.setFillColor(248, 249, 250);
            doc.rect(10, yPos, 277, 18, 'FD');
            
            yPos += 4;
            const col1X = 15;
            const col2X = 80;
            const col3X = 145;
            const col4X = 210;
            
            doc.setFont(undefined, 'bold');
            doc.text('BORROWER INFORMATION', col1X, yPos);
            doc.text('LOAN TERMS', col2X, yPos);
            doc.text('PAYMENT STATUS', col3X, yPos);
            doc.text('NEXT PAYMENT', col4X, yPos);
            
            yPos += 4;
            doc.setFont(undefined, 'normal');
            doc.text('Name: {{ $loan->borrower_name }}', col1X, yPos);
            doc.text('Principal: UGX {{ number_format($loan->principal_amount, 0) }}', col2X, yPos);
            doc.text('Total Payable: UGX {{ number_format($loan->total_payable, 0) }}', col3X, yPos);
            @if($nextDue)
            doc.text('Due Date: {{ date("M d, Y", strtotime($nextDue->due_date)) }}', col4X, yPos);
            @else
            doc.text('No pending payments', col4X, yPos);
            @endif
            
            yPos += 3.5;
            doc.text('Phone: {{ $loan->phone_number }}', col1X, yPos);
            doc.text('Interest: {{ number_format($loan->interest_rate, 2) }}%', col2X, yPos);
            doc.text('Amount Paid: UGX {{ number_format($loan->amount_paid, 0) }}', col3X, yPos);
            @if($nextDue)
            doc.text('Amount: UGX {{ number_format($nextDue->due_amount, 0) }}', col4X, yPos);
            @endif
            
            yPos += 3.5;
            doc.text('Branch: {{ $loan->branch_name ?? "N/A" }}', col1X, yPos);
            doc.text('Term: {{ $loan->loan_term }} {{ $loan->period_type_name ?? "installments" }}', col2X, yPos);
            doc.text('Outstanding: UGX {{ number_format($loan->outstanding_balance, 0) }}', col3X, yPos);
            
            // Get table data
            const tableData = [];
            const rows = document.querySelectorAll('#schedulesTable tbody tr');
            rows.forEach((row) => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const rowData = [];
                    for (let i = 0; i < cells.length - 1; i++) { // Skip action column
                        let text = cells[i].textContent.trim();
                        // Clean up status badges
                        if (i === cells.length - 2) {
                            const badge = cells[i].querySelector('.badge');
                            if (badge) {
                                text = badge.textContent.trim();
                            }
                        }
                        rowData.push(text);
                    }
                    tableData.push(rowData);
                }
            });
            
            // Add table
            doc.autoTable({
                startY: yPos + 5,
                head: [[
                    '#', 'Inst. Date', 'Principal', 'Orig. Interest', 'Principal cal Int', 
                    'Principal Bal', 'Principal for Int', 'Interest Pay', 'Periods Arr', 
                    'Late Fees', 'Total Pay', 'Principal Pd', 'Interest Pd', 'Late fees Pd', 
                    'Total Amt Pd', 'Total Bal', 'Status'
                ]],
                body: tableData,
                theme: 'grid',
                styles: {
                    fontSize: 5,
                    cellPadding: 0.5,
                    lineColor: [153, 153, 153],
                    lineWidth: 0.1
                },
                headStyles: {
                    fillColor: [217, 217, 217],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold',
                    fontSize: 5,
                    halign: 'center'
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 6 },
                    1: { halign: 'center', cellWidth: 16 },
                    2: { halign: 'right', cellWidth: 14 },
                    3: { halign: 'right', cellWidth: 14 },
                    4: { halign: 'right', cellWidth: 14 },
                    5: { halign: 'right', cellWidth: 14 },
                    6: { halign: 'right', cellWidth: 14 },
                    7: { halign: 'right', cellWidth: 14 },
                    8: { halign: 'center', cellWidth: 10 },
                    9: { halign: 'right', cellWidth: 12 },
                    10: { halign: 'right', cellWidth: 14 },
                    11: { halign: 'right', cellWidth: 14 },
                    12: { halign: 'right', cellWidth: 14 },
                    13: { halign: 'right', cellWidth: 12 },
                    14: { halign: 'right', cellWidth: 14 },
                    15: { halign: 'right', cellWidth: 14 },
                    16: { halign: 'center', cellWidth: 14 }
                },
                margin: { left: 10, right: 10 }
            });
            
            // Save the PDF
            const fileName = 'Loan_{{ $loan->loan_code }}_Repayment_Schedule_' + new Date().toISOString().split('T')[0] + '.pdf';
            doc.save(fileName);
            
            Swal.fire({
                icon: 'success',
                title: 'PDF Downloaded',
                text: 'Repayment schedule has been saved',
                timer: 2000,
                showConfirmButton: false
            });
        } catch (error) {
            console.error('PDF generation error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'Failed to generate PDF: ' + error.message
            });
        }
    }, 500);
}

// Export to Excel function
function exportToExcel() {
    // Get table data
    const table = document.getElementById('schedulesTable');
    const rows = table.querySelectorAll('tbody tr');
    
    // Prepare CSV data with UTF-8 BOM for proper Excel encoding
    let csvContent = '\uFEFF'; // UTF-8 BOM
    
    // Add header with loan information
    csvContent += 'Emuria Business Investment and Management Software (E-BIMS) Ltd\n';
    csvContent += 'LOAN REPAYMENT SCHEDULE\n';
    csvContent += '\n';
    csvContent += 'Loan Code:,{{ $loan->loan_code }}\n';
    csvContent += 'Borrower:,{{ $loan->borrower_name }}\n';
    csvContent += 'Phone:,{{ $loan->phone_number }}\n';
    csvContent += 'Branch:,{{ $loan->branch_name ?? "N/A" }}\n';
    csvContent += 'Principal Amount:,{{ number_format($loan->principal_amount, 0) }}\n';
    csvContent += 'Interest Rate:,{{ number_format($loan->interest_rate, 2) }}%\n';
    csvContent += 'Total Payable:,{{ number_format($loan->total_payable, 0) }}\n';
    csvContent += 'Amount Paid:,{{ number_format($loan->amount_paid, 0) }}\n';
    csvContent += 'Outstanding Balance:,{{ number_format($loan->outstanding_balance, 0) }}\n';
    csvContent += '\n';
    
    // Add column headers
    csvContent += '#,Installment Date,Principal,Original Interest,Principal cal Interest,Principal Bal,Principal for Interest payable,Interest payable,Periods in Arrears,Late Fees,Total Payment,Principal Paid,Interest Paid,Late fees Paid,Total Amount Paid,Total Balance,Status\n';
    
    // Add rows data
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        
        // Skip the last cell (Action column)
        for (let i = 0; i < cells.length - 1; i++) {
            let cellText = cells[i].textContent.trim();
            // Remove commas from numbers for Excel
            cellText = cellText.replace(/,/g, '');
            // Handle status badges - extract text only
            if (i === cells.length - 2) { // Status column
                const badge = cells[i].querySelector('.badge');
                if (badge) {
                    cellText = badge.textContent.trim();
                }
            }
            rowData.push(cellText);
        }
        
        csvContent += rowData.join(',') + '\n';
    });
    
    // Add totals row
    const tfoot = table.querySelector('tfoot tr');
    if (tfoot) {
        const footerCells = tfoot.querySelectorAll('th');
        const footerData = [];
        for (let i = 0; i < footerCells.length - 2; i++) { // Skip last 2 cells
            let cellText = footerCells[i].textContent.trim();
            cellText = cellText.replace(/,/g, '');
            footerData.push(cellText);
        }
        csvContent += footerData.join(',') + '\n';
    }
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'Loan_{{ $loan->loan_code }}_Repayment_Schedule_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Repayment schedule has been exported to Excel',
        timer: 2000,
        showConfirmButton: false
    });
}

// Print function
function printSchedules() {
    // Add print timestamp
    const printDate = new Date().toLocaleString('en-UG', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Create a temporary element to show print date
    const printInfo = document.createElement('div');
    printInfo.id = 'print-info';
    printInfo.style.display = 'none';
    printInfo.innerHTML = `
        <div style="text-align: center; margin-bottom: 3mm; padding: 2mm; border-bottom: 2px solid #333;">
            <h2 style="margin: 0 0 1mm 0; font-size: 14pt; font-weight: bold;">Emuria Business Investment and Management Software (E-BIMS) Ltd</h2>
            <p style="margin: 0 0 1mm 0; font-size: 8pt;">Akisim cell, Central ward, Akore town, Kapelebyong, Uganda</p>
            <h3 style="margin: 1mm 0; font-size: 12pt; font-weight: bold;">LOAN REPAYMENT SCHEDULE</h3>
            <p style="margin: 0; font-size: 7pt; color: #666;">Printed on: ${printDate}</p>
        </div>
    `;
    document.body.insertBefore(printInfo, document.body.firstChild);
    
    // Trigger print
    window.print();
    
    // Remove the temporary element after printing
    setTimeout(() => {
        document.body.removeChild(printInfo);
    }, 100);
}

// Initialize Bootstrap dropdowns
$(document).ready(function() {
    // Ensure Bootstrap dropdowns are initialized
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    if (typeof bootstrap !== 'undefined') {
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    }
});
</script>

<style type="text/css" media="print">
    @media print {
        /* Page setup - MUST come first */
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
        
        /* Force hide everything first */
        * {
            visibility: hidden !important;
        }
        
        /* Make printable sections visible */
        #print-info,
        #print-info *,
        #loan-summary-section,
        #loan-summary-section *,
        #repayment-schedules-section,
        #repayment-schedules-section * {
            visibility: visible !important;
        }
        
        /* Show the print info header */
        #print-info {
            display: block !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            page-break-after: avoid !important;
            margin-bottom: 1mm !important;
        }
        
        /* Position printable sections */
        #loan-summary-section {
            position: absolute !important;
            top: 18mm !important;
            left: 0 !important;
            width: 100% !important;
            margin-bottom: 1mm !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
        }
        
        #repayment-schedules-section {
            position: absolute !important;
            top: 38mm !important;
            left: 0 !important;
            width: 100% !important;
            page-break-before: avoid !important;
            page-break-inside: avoid !important;
        }
        
        /* Hide buttons, alerts, and interactive elements */
        .btn,
        button,
        .btn-group,
        input[type="radio"],
        .page-title-right,
        .breadcrumb,
        .alert,
        .dropdown {
            display: none !important;
            visibility: hidden !important;
        }
        
        html, body {
            width: 297mm !important;
            height: 210mm !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 8pt !important;
            line-height: 1.1 !important;
            color: #000 !important;
            background: white !important;
            overflow: hidden !important;
        }
        
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .row {
            margin: 0 !important;
            display: block !important;
        }
        
        .col, .col-12, .col-md-3, .col-auto {
            padding: 0 1mm !important;
            float: left !important;
        }
        
        /* Card styling - more compact */
        .card {
            border: 1px solid #333 !important;
            page-break-inside: avoid !important;
            margin-bottom: 1mm !important;
            box-shadow: none !important;
            background: white !important;
        }
        
        .card-header {
            background-color: #e9ecef !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            border-bottom: 1px solid #333 !important;
            padding: 1mm 2mm !important;
            font-weight: bold !important;
            font-size: 8pt !important;
        }
        
        .card-body {
            padding: 1mm 2mm !important;
        }
        
        .card-title {
            margin: 0 !important;
            font-size: 8pt !important;
        }
        
        /* Loan summary - ultra compact horizontal */
        #loan-summary-section .card {
            margin-bottom: 0.5mm !important;
        }
        
        #loan-summary-section .card-body {
            padding: 0.8mm !important;
        }
        
        #loan-summary-section .card-body > .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            margin: 0 !important;
        }
        
        #loan-summary-section .row > div {
            padding: 0 0.8mm !important;
            flex: 1 !important;
            min-width: 0 !important;
        }
        
        #loan-summary-section h6 {
            font-size: 6pt !important;
            margin-bottom: 0.3mm !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
        }
        
        #loan-summary-section p {
            font-size: 5pt !important;
            margin-bottom: 0.2mm !important;
            line-height: 1.1 !important;
        }
        
        #loan-summary-section strong {
            font-weight: 600 !important;
        }
        
        /* Hide borders in summary for space */
        #loan-summary-section .border-end {
            border-right: 0.5px solid #ddd !important;
            padding-right: 0.8mm !important;
        }
        
        /* Hide page title and breadcrumb */
        .page-title-box {
            display: none !important;
        }
        
        /* Table styling - ultra compact */
        .table-responsive {
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 5pt !important;
            margin: 0 !important;
            table-layout: fixed !important;
        }
        
        thead {
            display: table-header-group !important;
        }
        
        tbody {
            display: table-row-group !important;
        }
        
        tfoot {
            display: table-footer-group !important;
        }
        
        tr {
            page-break-inside: avoid !important;
        }
        
        th {
            background-color: #d9d9d9 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            border: 0.5px solid #333 !important;
            padding: 0.5mm !important;
            font-weight: bold !important;
            text-align: center !important;
            font-size: 5pt !important;
            line-height: 1.1 !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
        }
        
        td {
            border: 0.5px solid #999 !important;
            padding: 0.3mm 0.5mm !important;
            font-size: 5pt !important;
            line-height: 1.1 !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        
        /* Alternating row colors */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        /* Status badges - smaller */
        .badge {
            padding: 0.3mm 0.8mm !important;
            border-radius: 1mm !important;
            font-size: 5pt !important;
            font-weight: bold !important;
            border: 0.3px solid #666 !important;
        }
        
        .bg-success {
            background-color: #28a745 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .bg-danger {
            background-color: #dc3545 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .bg-secondary {
            background-color: #6c757d !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
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
        
        /* Print info header - ultra compact */
        #print-info h2 {
            font-size: 8pt !important;
            margin: 0 0 0.3mm 0 !important;
        }
        
        #print-info h3 {
            font-size: 7pt !important;
            margin: 0.3mm 0 !important;
        }
        
        #print-info p {
            font-size: 5pt !important;
            margin: 0 !important;
        }
        
        #print-info > div {
            padding: 0.8mm !important;
            margin-bottom: 0.8mm !important;
        }
        
        /* Force single page */
        body {
            page-break-after: avoid !important;
        }
        
        /* Ensure content doesn't overflow */
        * {
            box-sizing: border-box !important;
        }
        
        img {
            max-width: 100% !important;
            height: auto !important;
        }
        
        /* Scale content to fit if needed */
        body {
            transform-origin: top left !important;
            transform: scale(0.88) !important;
        }
        
        /* Additional space optimization */
        #repayment-schedules-section .card-header {
            padding: 0.6mm 1.2mm !important;
        }
        
        #repayment-schedules-section .card-body {
            padding: 0.4mm !important;
        }
        
        /* Tighter card styling */
        .card-header {
            padding: 0.8mm 1.5mm !important;
            font-size: 7pt !important;
        }
    }
</style>
@endpush
@endsection