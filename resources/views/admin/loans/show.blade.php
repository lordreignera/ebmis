@extends('layouts.admin')

@section('title', 'Loan Details - ' . $loan->code)

@section('content')
@php
    // Get member from loan based on loan type
    $member = $loanType === 'group' 
        ? ($loan->group->members()->first() ?? null) 
        : ($loan->member ?? null);
@endphp

<div class="container-fluid">
    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.approvals') }}">Loan Approvals</a></li>
                        <li class="breadcrumb-item active">{{ $loan->code }}</li>
                    </ol>
                </div>
                <h4 class="page-title">Loan Profile / <strong class="text-primary">{{ $loan->code }}</strong></h4>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex gap-2">
                <a href="{{ route('admin.loans.approvals') }}" class="btn btn-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i> Back to Approvals
                </a>
                
                @if($loan->status == 0)
                    @if($loan->charge_type == 2)
                    {{-- Show Pay Fees button if there are unpaid upfront charges --}}
                    @php
                        $hasUnpaidFees = false;
                        if(isset($upfrontFees)) {
                            foreach($upfrontFees as $fee) {
                                if(!$fee['paid']) {
                                    $hasUnpaidFees = true;
                                    break;
                                }
                            }
                        }
                    @endphp
                    
                    @if($hasUnpaidFees)
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#payFeesModal">
                        <i class="mdi mdi-cash me-1"></i> Pay Upfront Fees
                    </button>
                    @endif
                    @endif
                    
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveLoanModal">
                    <i class="mdi mdi-check me-1"></i> Approve Loan
                </button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectLoanModal">
                    <i class="mdi mdi-close me-1"></i> Reject Loan
                </button>
                @elseif($loan->status == 1)
                    @if(auth()->user()->hasRole('Super Administrator') || auth()->user()->hasRole('superadmin'))
                    <a href="{{ route('admin.disbursements.approve.show', $loan->id) }}?type={{ $loanType }}" class="btn btn-primary">
                        <i class="mdi mdi-cash-multiple me-1"></i> Process Disbursement
                    </a>
                    @else
                    <button class="btn btn-secondary" disabled title="Only Super Administrator can process disbursements">
                        <i class="mdi mdi-lock me-1"></i> Process Disbursement (Restricted)
                    </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Rejection Information Card (if loan is rejected) -->
    @if($loan->status == 4)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card" style="border-left: 4px solid #dc3545; background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md" style="width: 60px; height: 60px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="mdi mdi-close-circle-outline" style="font-size: 2rem; color: white;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-2" style="color: #dc3545; font-weight: 600;">
                                <i class="mdi mdi-alert-circle-outline me-2"></i>Loan Application Rejected
                            </h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Rejected By:</strong> 
                                        <span class="text-muted">{{ $loan->rejectedBy->name ?? 'System' }}</span>
                                    </p>
                                    <p class="mb-2"><strong>Rejection Date:</strong> 
                                        <span class="text-muted">{{ $loan->date_rejected ? \Carbon\Carbon::parse($loan->date_rejected)->format('M d, Y \a\t h:i A') : 'N/A' }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Rejection Reason:</strong></p>
                                    <div style="background: white; padding: 15px; border-radius: 10px; border: 2px solid #ffc107;">
                                        <p class="mb-0" style="color: #495057; font-size: 0.95rem;">
                                            {{ $loan->Rcomments ?? 'No reason provided' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabs Card -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <ul class="nav nav-tabs nav-bordered mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#borrower" role="tab">
                            <i class="mdi mdi-account me-1"></i> Borrower
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#loan-details" role="tab">
                            <i class="mdi mdi-file-document me-1"></i> Loan Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#financials" role="tab">
                            <i class="mdi mdi-currency-usd me-1"></i> Financials
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#documents" role="tab">
                            <i class="mdi mdi-file-multiple me-1"></i> Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#charges" role="tab">
                            <i class="mdi mdi-calculator me-1"></i> Charges & Disbursement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#agreement" role="tab">
                            <i class="mdi mdi-file-sign me-1"></i> Loan Agreement
                        </a>
                    </li>
                </ul>

                <div class="card-body">
                    <div class="tab-content">
                        <!-- Borrower Tab -->
                        <div class="tab-pane show active" id="borrower" role="tabpanel">
                            @if($loanType === 'personal')
                            <h5 class="mb-3">Borrower Details</h5>
                            <h6 class="text-muted mb-3">Personal Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Full Name:</th>
                                            <td>{{ $loan->member->fname }} {{ $loan->member->lname }}</td>
                                        </tr>
                                        <tr>
                                            <th>Branch:</th>
                                            <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>National ID:</th>
                                            <td>{{ $loan->member->nin }}</td>
                                        </tr>
                                        <tr>
                                            <th>Mobile Number:</th>
                                            <td>{{ $loan->member->contact }}</td>
                                        </tr>
                                        <tr>
                                            <th>Alt. Mobile:</th>
                                            <td>{{ $loan->member->alt_contact ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td>{{ $loan->member->email ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Residential Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Plot Number:</th>
                                            <td>{{ $loan->member->plot_no ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Village:</th>
                                            <td>{{ $loan->member->village ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Parish:</th>
                                            <td>{{ $loan->member->parish ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Sub-county:</th>
                                            <td>{{ $loan->member->subcounty ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>County:</th>
                                            <td>{{ $loan->member->county ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Country:</th>
                                            <td>{{ $loan->member->country->name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            @else
                            <h5 class="mb-3">Group Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Group Name:</th>
                                            <td>{{ $loan->group->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Branch:</th>
                                            <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Members:</th>
                                            <td>{{ $loan->group->members->count() ?? 0 }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Loan Details Tab -->
                        <div class="tab-pane" id="loan-details" role="tabpanel">
                            <h5 class="mb-3">Loan Application Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Loan Code:</th>
                                            <td>{{ $loan->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>Product:</th>
                                            <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Principal:</th>
                                            <td>UGX {{ number_format($loan->principal, 0) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Interest Rate:</th>
                                            <td>{{ $loan->interest }}% p.a.</td>
                                        </tr>
                                        <tr>
                                            <th>Repayment Period:</th>
                                            <td>{{ $loan->period }} {{ $loan->product->period_type == 1 ? 'weeks' : ($loan->product->period_type == 2 ? 'months' : 'days') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Installment:</th>
                                            <td>UGX {{ number_format($loan->installment ?? 0, 0) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Application Date:</th>
                                            <td>{{ \Carbon\Carbon::parse($loan->datecreated)->format('M d, Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($loanType === 'personal' && isset($loan->guarantors))
                            <hr>
                            <h6 class="mb-3">Guarantor Details</h6>
                            @if($loan->guarantors->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>National ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($loan->guarantors as $index => $guarantor)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $guarantor->member->fname ?? '' }} {{ $guarantor->member->lname ?? '' }}</td>
                                            <td>{{ $guarantor->member->contact ?? 'N/A' }}</td>
                                            <td>{{ $guarantor->member->nin ?? 'N/A' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-warning">No guarantors attached to this loan!</div>
                            @endif
                            @endif
                        </div>

                        <!-- Financials Tab -->
                        <div class="tab-pane" id="financials" role="tabpanel">
                            <h5 class="mb-3">Financial Details</h5>
                            @if($loanType === 'personal')
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <h3 class="text-success">UGX {{ number_format($loan->member->savings->sum('amount') ?? 0, 0) }}</h3>
                                            <p class="mb-0">Cash Security</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <p class="text-muted">Financial information will be displayed here</p>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane" id="documents" role="tabpanel">
                            <h5 class="mb-3">Loan Documents</h5>
                            <div class="row">
                                @if($loanType === 'personal')
                                <div class="col-md-3 mb-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <div class="avatar-lg mx-auto mb-2">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded">
                                                    <i class="mdi mdi-file-document" style="font-size: 2rem;"></i>
                                                </span>
                                            </div>
                                            <h6>Trading License</h6>
                                            @if($loan->trading_file)
                                            @php
                                                $tradingUrl = file_exists(public_path('storage/' . $loan->trading_file)) 
                                                    ? asset('storage/' . $loan->trading_file)
                                                    : asset('../../bimsadmin/public/' . $loan->trading_file);
                                            @endphp
                                            <a href="{{ $tradingUrl }}" target="_blank" class="btn btn-sm btn-primary">View</a>
                                            @else
                                            <span class="text-muted">Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <div class="avatar-lg mx-auto mb-2">
                                                <span class="avatar-title bg-warning-subtle text-warning rounded">
                                                    <i class="mdi mdi-bank" style="font-size: 2rem;"></i>
                                                </span>
                                            </div>
                                            <h6>Bank Statement</h6>
                                            @if($loan->bank_file)
                                            @php
                                                $bankUrl = file_exists(public_path('storage/' . $loan->bank_file)) 
                                                    ? asset('storage/' . $loan->bank_file)
                                                    : asset('../../bimsadmin/public/' . $loan->bank_file);
                                            @endphp
                                            <a href="{{ $bankUrl }}" target="_blank" class="btn btn-sm btn-warning">View</a>
                                            @else
                                            <span class="text-muted">Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <div class="avatar-lg mx-auto mb-2">
                                                <span class="avatar-title bg-success-subtle text-success rounded">
                                                    <i class="mdi mdi-store" style="font-size: 2rem;"></i>
                                                </span>
                                            </div>
                                            <h6>Business Premise</h6>
                                            @if($loan->business_file)
                                            @php
                                                $businessUrl = file_exists(public_path('storage/' . $loan->business_file)) 
                                                    ? asset('storage/' . $loan->business_file)
                                                    : asset('../../bimsadmin/public/' . $loan->business_file);
                                            @endphp
                                            <a href="{{ $businessUrl }}" target="_blank" class="btn btn-sm btn-success">View</a>
                                            @else
                                            <span class="text-muted">Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Charges & Disbursement Tab -->
                        <div class="tab-pane" id="charges" role="tabpanel">
                            <h5 class="mb-3">Charges & Disbursement Calculation</h5>
                            
                            <!-- Charge Type Selection -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    @if($loan->status == 0)
                                    <!-- Editable Charge Type for Pending Loans -->
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <form action="{{ route('admin.loans.update-charge-type', $loan->id) }}" method="POST" id="chargeTypeForm">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                                                
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">
                                                            <i class="mdi mdi-cash-multiple me-1"></i> How should charges be handled?
                                                        </label>
                                                        <select name="charge_type" class="form-select" id="chargeTypeSelect">
                                                            <option value="1" {{ $loan->charge_type == 1 ? 'selected' : '' }}>
                                                                Deduct from Disbursement Amount (Member receives less)
                                                            </option>
                                                            <option value="2" {{ $loan->charge_type == 2 ? 'selected' : '' }}>
                                                                Member Pays Upfront (Full amount disbursed after payment)
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <button type="submit" class="btn btn-primary mt-3">
                                                            <i class="mdi mdi-check me-1"></i> Update Charge Type
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <div id="chargeTypeDescription" class="alert mb-0">
                                                        @if($loan->charge_type == 1)
                                                            <strong>Current Setting:</strong> Charges will be deducted from principal. Member receives reduced amount.
                                                        @else
                                                            <strong>Current Setting:</strong> Member must pay charges before disbursement. Full principal amount will be disbursed after payment.
                                                        @endif
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    @else
                                    <!-- Display Only for Approved/Disbursed Loans -->
                                    <div class="alert {{ $loan->charge_type == 1 ? 'alert-primary' : 'alert-warning' }}">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <strong>Charge Type:</strong> 
                                                @if($loan->charge_type == 1)
                                                    <span class="badge bg-primary">Charges Deducted on Disbursement</span>
                                                    <p class="mb-0 mt-2 small">All charges were deducted from the principal amount at disbursement.</p>
                                                @else
                                                    <span class="badge bg-warning">Charges Paid Upfront</span>
                                                    <p class="mb-0 mt-2 small">Member paid all charges before loan disbursement.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            @php
                                $product = $loan->product;
                                $principal = $loan->principal;
                                $productCharges = $product->charges()->where('isactive', 1)->get();
                                $totalCharges = 0;
                                
                                // Calculate total charges
                                foreach ($productCharges as $charge) {
                                    $chargeAmount = 0;
                                    // Get raw value and convert to float, default to 0 if null/empty
                                    $chargeValue = floatval($charge->getRawOriginal('value') ?? 0);
                                    
                                    switch ($charge->type) {
                                        case 1: // Fixed Amount
                                            $chargeAmount = $chargeValue;
                                            break;
                                        case 2: // Percentage
                                            $chargeAmount = ($principal * $chargeValue) / 100;
                                            break;
                                        case 3: // Per Day
                                            $chargeAmount = $chargeValue * $loan->period;
                                            break;
                                        case 4: // Per Month
                                            $months = ceil($loan->period / 30);
                                            $chargeAmount = $chargeValue * $months;
                                            break;
                                    }
                                    $totalCharges += $chargeAmount;
                                }
                                
                                // Calculate disbursable amount
                                $disbursableAmount = $loan->charge_type == 1 ? ($principal - $totalCharges) : $principal;
                                
                                // Get upfront fees if charge_type = 2
                                $upfrontFees = [];
                                if ($loan->charge_type == 2) {
                                    // Get charges from product that are upfront (charge_type = 2)
                                    $upfrontCharges = $productCharges->where('charge_type', 2);
                                    
                                    $memberId = $loanType === 'personal' ? $loan->member_id : ($loan->group->members()->first()->id ?? null);
                                    
                                    foreach ($upfrontCharges as $charge) {
                                        // Calculate charge amount
                                        $chargeAmount = 0;
                                        if ($charge->type == 1) { // Fixed
                                            $chargeAmount = floatval($charge->getRawOriginal('value') ?? 0);
                                        } elseif ($charge->type == 2) { // Percentage
                                            $percentageValue = floatval($charge->getRawOriginal('value') ?? 0);
                                            $chargeAmount = ($principal * $percentageValue) / 100;
                                        } elseif ($charge->type == 3) { // Per Day
                                            $perDayValue = floatval($charge->getRawOriginal('value') ?? 0);
                                            $chargeAmount = $perDayValue * $loan->period;
                                        } elseif ($charge->type == 4) { // Per Month
                                            $perMonthValue = floatval($charge->getRawOriginal('value') ?? 0);
                                            $chargeAmount = $perMonthValue * $loan->period;
                                        }
                                        
                                        // Check if fee has been paid
                                        $paidFee = \App\Models\Fee::where('loan_id', $loan->id)
                                                                   ->where('fees_type_id', $charge->id)
                                                                   ->where('status', 1)
                                                                   ->first();
                                        
                                        // For registration fees, check if member has paid before (one-time payment)
                                        $isRegistrationFee = stripos($charge->name, 'registration') !== false;
                                        if ($isRegistrationFee && !$paidFee && $memberId) {
                                            $paidFee = \App\Models\Fee::where('member_id', $memberId)
                                                                       ->where('fees_type_id', $charge->id)
                                                                       ->where('status', 1)
                                                                       ->first();
                                        }
                                        
                                        $upfrontFees[] = [
                                            'id' => $charge->id,
                                            'name' => $charge->name,
                                            'amount' => $chargeAmount,
                                            'paid' => $paidFee ? true : false,
                                            'payment_date' => $paidFee ? $paidFee->datecreated : null
                                        ];
                                    }
                                }
                            @endphp

                            <!-- Charges Breakdown Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Description</th>
                                            <th width="15%">Calculation</th>
                                            <th width="15%" class="text-end">Amount (UGX)</th>
                                            <th width="10%" class="text-center">Type</th>
                                            <th width="15%" class="text-center">Payment Status</th>
                                            <th width="15%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Principal Amount -->
                                        <tr class="table-success">
                                            <td>1</td>
                                            <td><strong>Loan Principal (Requested)</strong></td>
                                            <td>-</td>
                                            <td class="text-end"><strong>{{ number_format($principal, 0) }}</strong></td>
                                            <td class="text-center"><span class="badge bg-success">Credit</span></td>
                                        </tr>
                                        
                                        <!-- Product Charges -->
                                        @if($productCharges->count() > 0)
                                        <tr><td colspan="5" class="table-secondary"><strong>Product Charges</strong></td></tr>
                                        @foreach($productCharges as $index => $charge)
                                        @php
                                            $chargeAmount = 0;
                                            $calculation = '';
                                            // Get raw value and convert to float, default to 0 if null/empty
                                            $chargeValue = floatval($charge->getRawOriginal('value') ?? 0);
                                            
                                            switch ($charge->type) {
                                                case 1: // Fixed Amount
                                                    $chargeAmount = $chargeValue;
                                                    $calculation = 'Fixed';
                                                    break;
                                                case 2: // Percentage
                                                    $chargeAmount = ($principal * $chargeValue) / 100;
                                                    $calculation = $chargeValue . '% of ' . number_format($principal, 0);
                                                    break;
                                                case 3: // Per Day
                                                    $chargeAmount = $chargeValue * $loan->period;
                                                    $calculation = number_format($chargeValue, 0) . ' × ' . $loan->period . ' days';
                                                    break;
                                                case 4: // Per Month
                                                    $months = ceil($loan->period / 30);
                                                    $chargeAmount = $chargeValue * $months;
                                                    $calculation = number_format($chargeValue, 0) . ' × ' . $months . ' months';
                                                    break;
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 2 }}</td>
                                            <td>{{ $charge->name }}</td>
                                            <td>{{ $calculation }}</td>
                                            <td class="text-end text-danger">-{{ number_format($chargeAmount, 0) }}</td>
                                            <td class="text-center"><span class="badge bg-danger">Debit</span></td>
                                            @php
                                                // Check if this charge is paid
                                                $chargePaidFee = \App\Models\Fee::where('loan_id', $loan->id)
                                                                   ->where('fees_type_id', $charge->id)
                                                                   ->where('status', 1)
                                                                   ->first();
                                                
                                                // For registration fees, check member level
                                                $isRegFee = stripos($charge->name, 'registration') !== false;
                                                if ($isRegFee && !$chargePaidFee && isset($memberId)) {
                                                    $chargePaidFee = \App\Models\Fee::where('member_id', $memberId)
                                                                       ->where('fees_type_id', $charge->id)
                                                                       ->where('status', 1)
                                                                       ->first();
                                                }
                                            @endphp
                                            <td class="text-center">
                                                @if($loan->charge_type == 1)
                                                    {{-- For deducted charges, always show as paid --}}
                                                    <span class="badge bg-success"><i class="mdi mdi-check"></i> Paid (Deducted)</span>
                                                @elseif($chargePaidFee)
                                                    <span class="badge bg-success"><i class="mdi mdi-check"></i> Paid</span>
                                                @else
                                                    @php
                                                        // Check if there's a pending mobile money payment
                                                        $pendingFee = \App\Models\Fee::where('loan_id', $loan->id)
                                                                       ->where('fees_type_id', $charge->id)
                                                                       ->where('status', 0) // Pending
                                                                       ->where('payment_type', 1) // Mobile Money
                                                                       ->first();
                                                        
                                                        $failedFee = \App\Models\Fee::where('loan_id', $loan->id)
                                                                       ->where('fees_type_id', $charge->id)
                                                                       ->where('status', 2) // Failed
                                                                       ->where('payment_type', 1) // Mobile Money
                                                                       ->first();
                                                    @endphp
                                                    
                                                    @if($pendingFee)
                                                        <span class="badge bg-warning text-dark"><i class="mdi mdi-timer-sand"></i> Processing</span>
                                                    @elseif($failedFee)
                                                        <span class="badge bg-danger"><i class="mdi mdi-close-circle"></i> Failed</span>
                                                    @else
                                                        <span class="badge bg-danger"><i class="mdi mdi-close"></i> Unpaid</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($loan->charge_type == 1)
                                                    {{-- For deducted charges, no action needed --}}
                                                    <small class="text-muted">Auto-deducted</small>
                                                @elseif($chargePaidFee)
                                                    <small class="text-success">✓ Paid</small>
                                                @elseif($failedFee && $loan->status == 0)
                                                    {{-- Show retry button for failed payments --}}
                                                    <button class="btn btn-sm btn-danger retry-loan-fee-btn" 
                                                            data-fee-id="{{ $failedFee->id }}"
                                                            data-fee-name="{{ $charge->name }}"
                                                            data-fee-amount="{{ $chargeAmount }}"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#retryLoanFeeModal">
                                                        <i class="mdi mdi-refresh"></i> Retry
                                                    </button>
                                                @elseif($pendingFee && $loan->status == 0)
                                                    {{-- Show check status and cancel/retry buttons for pending payments --}}
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-warning check-loan-fee-status-btn" 
                                                                data-transaction-ref="{{ $pendingFee->pay_ref }}"
                                                                data-fee-id="{{ $pendingFee->id }}"
                                                                data-fee-name="{{ $charge->name }}">
                                                            <i class="mdi mdi-refresh"></i> Check Status
                                                        </button>
                                                        <button class="btn btn-sm btn-danger cancel-and-retry-loan-fee-btn" 
                                                                data-fee-id="{{ $pendingFee->id }}"
                                                                data-fee-name="{{ $charge->name }}"
                                                                data-fee-amount="{{ $chargeAmount }}"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#retryLoanFeeModal"
                                                                title="Cancel this pending payment and retry">
                                                            <i class="mdi mdi-close-circle"></i> Cancel & Retry
                                                        </button>
                                                    </div>
                                                @elseif(!$chargePaidFee && $loan->status == 0)
                                                    <button class="btn btn-sm btn-warning pay-single-fee-btn" 
                                                            data-fee-id="{{ $charge->id }}"
                                                            data-fee-name="{{ $charge->name }}"
                                                            data-fee-amount="{{ $chargeAmount }}"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#paySingleFeeModal">
                                                        <i class="mdi mdi-cash"></i> Pay
                                                    </button>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        
                                        <!-- Total Charges -->
                                        <tr class="table-warning">
                                            <td colspan="3" class="text-end"><strong>Total Charges:</strong></td>
                                            <td class="text-end"><strong class="text-danger">-{{ number_format($totalCharges, 0) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        @else
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <em>No product charges configured</em>
                                            </td>
                                        </tr>
                                        @endif
                                        
                                        <!-- Actual Disbursable Amount -->
                                        <tr class="table-info">
                                            <td colspan="3" class="text-end"><strong>ACTUAL DISBURSABLE AMOUNT:</strong></td>
                                            <td class="text-end"><strong class="text-success" style="font-size: 1.2rem;">{{ number_format($disbursableAmount, 0) }}</strong></td>
                                            <td class="text-center"><span class="badge bg-success">Credit</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Upfront Charges Payment Status -->
                            @if($loan->charge_type == 2 && count($upfrontFees) > 0)
                            <hr>
                            <h6 class="mb-3">Upfront Charges Payment Status</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fee Type</th>
                                            <th class="text-end">Amount (UGX)</th>
                                            <th class="text-center">Payment Status</th>
                                            <th>Payment Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($upfrontFees as $fee)
                                        <tr>
                                            <td>{{ $fee['name'] }}</td>
                                            <td class="text-end">{{ number_format($fee['amount'], 0) }}</td>
                                            <td class="text-center">
                                                @if($fee['paid'])
                                                    <span class="badge bg-success"><i class="mdi mdi-check"></i> Paid</span>
                                                @elseif($fee['status'] == 0 && $fee['payment_type'] == 1)
                                                    <span class="badge bg-warning text-dark"><i class="mdi mdi-timer-sand"></i> Processing</span>
                                                @elseif($fee['status'] == 2 && $fee['payment_type'] == 1)
                                                    <span class="badge bg-danger"><i class="mdi mdi-close-circle"></i> Failed</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="mdi mdi-close"></i> Not Paid</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($fee['payment_date'])
                                                    {{ \Carbon\Carbon::parse($fee['payment_date'])->format('M d, Y H:i') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @php
                                $allUpfrontPaid = collect($upfrontFees)->where('paid', false)->count() == 0;
                            @endphp
                            
                            @if(!$allUpfrontPaid)
                            <div class="alert alert-danger">
                                <i class="mdi mdi-alert-circle me-1"></i>
                                <strong>Warning:</strong> All upfront charges must be paid before this loan can be disbursed!
                            </div>
                            @else
                            <div class="alert alert-success">
                                <i class="mdi mdi-check-circle me-1"></i>
                                <strong>Good!</strong> All upfront charges have been paid. This loan is ready for disbursement.
                            </div>
                            @endif
                            @endif

                            <!-- Summary Card -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card border border-primary">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Requested Amount</h6>
                                            <h3 class="text-primary mb-0">UGX {{ number_format($principal, 0) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border border-success">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">
                                                @if($loan->charge_type == 1)
                                                    Amount to Disburse (After Deductions)
                                                @else
                                                    Amount to Disburse (Full)
                                                @endif
                                            </h6>
                                            <h3 class="text-success mb-0">UGX {{ number_format($disbursableAmount, 0) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loan Agreement Tab -->
                        <div class="tab-pane" id="agreement" role="tabpanel">
                            <h5 class="mb-3">Loan Agreement</h5>
                            <p>Download or view the loan agreement document for <strong>{{ $loanType === 'personal' ? $loan->member->fname . ' ' . $loan->member->lname : $loan->group->name }}</strong></p>
                            
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-lg">
                                            <span class="avatar-title bg-danger-subtle text-danger rounded">
                                                <i class="mdi mdi-file-pdf-box" style="font-size: 2.5rem;"></i>
                                            </span>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <h5 class="mb-1">Loan Agreement Document</h5>
                                            <p class="text-muted mb-0">Loan Code: {{ $loan->code }}</p>
                                            <p class="text-muted mb-0">Amount: UGX {{ number_format($loan->principal, 0) }}</p>
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.loans.view-agreement', ['id' => $loan->id, 'type' => $loanType]) }}" 
                                               class="btn btn-danger me-2" target="_blank">
                                                <i class="mdi mdi-eye me-1"></i> View PDF
                                            </a>
                                            <a href="{{ route('admin.loans.view-agreement', ['id' => $loan->id, 'type' => $loanType]) }}?download=1" 
                                               class="btn btn-outline-danger">
                                                <i class="mdi mdi-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($loan->status == 0)
                            <div class="alert alert-info mt-3">
                                <i class="mdi mdi-information me-1"></i>
                                <strong>Note:</strong> Review the loan agreement before approving this loan application.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Loan Modal -->
<div class="modal fade" id="approveLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white; border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 25px 30px;">
                <div>
                    <h5 class="modal-title mb-1" style="font-weight: 600; font-size: 1.4rem;">
                        <i class="mdi mdi-check-circle-outline me-2"></i>Approve Loan Application
                    </h5>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Forward this loan for disbursement</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 1;"></button>
            </div>
            <form action="{{ route('admin.loans.approve', $loan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                <div class="modal-body" style="padding: 30px;">
                    <div class="alert" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 12px; padding: 20px;">
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-information-outline" style="font-size: 2rem; color: #28a745; margin-right: 15px;"></i>
                            <div>
                                <h6 style="color: #155724; font-weight: 600; margin-bottom: 8px;">
                                    <i class="mdi mdi-check-decagram me-1"></i>Loan Approval Confirmation
                                </h6>
                                <p style="color: #155724; margin-bottom: 0; font-size: 0.95rem;">
                                    You are about to approve loan <strong style="color: #28a745;">{{ $loan->code }}</strong>. 
                                    This will forward the application to the disbursement queue.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-4">
                        <label class="form-label" style="font-weight: 600; color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center;">
                            <i class="mdi mdi-comment-text-outline me-2" style="font-size: 1.3rem; color: #28a745;"></i>
                            Approval Comments <span class="text-muted ms-1">(Optional)</span>
                        </label>
                        <textarea class="form-control" name="comments" rows="4"
                                  placeholder="Add any notes about this approval (e.g., Verified documents, Credit check passed, Collateral confirmed...)"
                                  style="border: 2px solid #e0e0e0; border-radius: 10px; padding: 15px; font-size: 0.95rem; transition: all 0.3s; resize: vertical;"
                                  onfocus="this.style.borderColor='#28a745'; this.style.boxShadow='0 0 0 0.2rem rgba(40, 167, 69, 0.1)'"
                                  onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"></textarea>
                        <div class="d-flex align-items-center mt-2" style="color: #6c757d; font-size: 0.875rem;">
                            <i class="mdi mdi-information-outline me-1"></i>
                            <small>Your comments will be recorded in the loan approval history.</small>
                        </div>
                    </div>

                    <div class="alert alert-light" style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; margin-top: 20px;">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-alert-circle-outline me-2" style="font-size: 1.5rem; color: #17a2b8;"></i>
                            <div style="font-size: 0.9rem; color: #495057;">
                                <strong>Next Step:</strong> After approval, this loan will appear in the disbursement queue for the super administrator to process.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border: none; border-radius: 0 0 15px 15px; padding: 20px 30px; gap: 10px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" 
                            style="padding: 12px 30px; border-radius: 8px; font-weight: 500; border: 2px solid #e0e0e0;">
                        <i class="mdi mdi-close me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" 
                            style="padding: 12px 30px; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);">
                        <i class="mdi mdi-check-circle me-1"></i>Approve & Forward
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Loan Modal -->
<div class="modal fade" id="rejectLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white; border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 25px 30px;">
                <div>
                    <h5 class="modal-title mb-1" style="font-weight: 600; font-size: 1.4rem;">
                        <i class="mdi mdi-close-circle-outline me-2"></i>Reject Loan Application
                    </h5>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Provide a clear reason for loan rejection</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 1;"></button>
            </div>
            <form action="{{ route('admin.loans.reject', $loan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                <div class="modal-body" style="padding: 30px;">
                    <div class="alert" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; border-radius: 12px; padding: 20px;">
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-alert-outline" style="font-size: 2rem; color: #ff6b6b; margin-right: 15px;"></i>
                            <div>
                                <h6 style="color: #856404; font-weight: 600; margin-bottom: 8px;">
                                    <i class="mdi mdi-information-outline me-1"></i>Critical Action - Cannot Be Undone
                                </h6>
                                <p style="color: #856404; margin-bottom: 0; font-size: 0.95rem;">
                                    You are about to reject loan <strong style="color: #dc3545;">{{ $loan->code }}</strong>. 
                                    This decision is permanent and the applicant will be notified.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-4">
                        <label class="form-label" style="font-weight: 600; color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center;">
                            <i class="mdi mdi-comment-alert-outline me-2" style="font-size: 1.3rem; color: #dc3545;"></i>
                            Reason for Rejection <span class="text-danger ms-1">*</span>
                        </label>
                        <textarea class="form-control" name="comments" rows="5" required
                                  placeholder="Explain why this loan is being rejected (e.g., Insufficient collateral, Credit history issues, Documentation incomplete...)"
                                  style="border: 2px solid #e0e0e0; border-radius: 10px; padding: 15px; font-size: 0.95rem; transition: all 0.3s; resize: vertical;"
                                  onfocus="this.style.borderColor='#dc3545'; this.style.boxShadow='0 0 0 0.2rem rgba(220, 53, 69, 0.1)'"
                                  onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"></textarea>
                        <div class="d-flex align-items-center mt-2" style="color: #6c757d; font-size: 0.875rem;">
                            <i class="mdi mdi-information-outline me-1"></i>
                            <small>This reason will be recorded in the loan history and may be shared with the applicant.</small>
                        </div>
                    </div>

                    <div class="alert alert-light" style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; margin-top: 20px;">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-lightbulb-on-outline me-2" style="font-size: 1.5rem; color: #17a2b8;"></i>
                            <div style="font-size: 0.9rem; color: #495057;">
                                <strong>Tip:</strong> Be specific and professional. Clear feedback helps applicants improve future applications.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border: none; border-radius: 0 0 15px 15px; padding: 20px 30px; gap: 10px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" 
                            style="padding: 12px 30px; border-radius: 8px; font-weight: 500; border: 2px solid #e0e0e0;">
                        <i class="mdi mdi-arrow-left me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" 
                            style="padding: 12px 30px; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);">
                        <i class="mdi mdi-close-circle me-1"></i>Reject Loan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pay Upfront Fees Modal -->
<div class="modal fade" id="payFeesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="mdi mdi-cash me-2"></i>Pay Upfront Loan Fees</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.loans.pay-fees', $loan->id) }}" method="POST" id="payFeesForm">
                @csrf
                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-1"></i>
                        Record payment for upfront loan fees. <strong>Registration fees</strong> are paid once per member, while <strong>other fees</strong> must be paid for each loan application.
                    </div>

                    @if(isset($upfrontFees) && count($upfrontFees) > 0)
                    <h6 class="mb-3">Select Fees to Pay:</h6>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllFees">
                                            <label class="form-check-label" for="selectAllFees"></label>
                                        </div>
                                    </th>
                                    <th>Fee Type</th>
                                    <th class="text-end">Amount (UGX)</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalUnpaidFees = 0; @endphp
                                @foreach($upfrontFees as $index => $fee)
                                @if(!$fee['paid'])
                                @php 
                                    $totalUnpaidFees += $fee['amount'];
                                    $isRegistrationFee = stripos($fee['name'], 'registration') !== false;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input fee-checkbox" 
                                                   type="checkbox" 
                                                   name="fees[]" 
                                                   value="{{ $fee['id'] }}"
                                                   data-amount="{{ $fee['amount'] }}"
                                                   id="fee_{{ $index }}">
                                        </div>
                                    </td>
                                    <td>
                                        <label for="fee_{{ $index }}" class="mb-0">
                                            {{ $fee['name'] }}
                                            @if($isRegistrationFee)
                                            <span class="badge bg-info">One-time fee</span>
                                            @else
                                            <span class="badge bg-warning">Per loan</span>
                                            @endif
                                        </label>
                                    </td>
                                    <td class="text-end">{{ number_format($fee['amount'], 0) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">Unpaid</span>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                                <tr class="table-info">
                                    <td colspan="2" class="text-end"><strong>Total Selected:</strong></td>
                                    <td class="text-end"><strong id="totalSelectedAmount">0</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="1">Cash</option>
                                <option value="2">Bank Transfer</option>
                                <option value="3">Mobile Money</option>
                                <option value="4">Card Payment</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Reference</label>
                            <input type="text" class="form-control" name="payment_reference" 
                                   placeholder="e.g., Transaction ID, Receipt Number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Notes</label>
                        <textarea class="form-control" name="payment_notes" rows="2" 
                                  placeholder="Optional notes about this payment"></textarea>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="mdi mdi-alert me-1"></i>
                        <strong>Note:</strong> Please ensure payment has been received before recording it in the system.
                    </div>
                    @else
                    <div class="alert alert-success">
                        <i class="mdi mdi-check-circle me-1"></i>
                        All upfront fees have been paid!
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="submitPaymentBtn">
                        <i class="mdi mdi-cash-check me-1"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fee selection and calculation
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllFees');
    const feeCheckboxes = document.querySelectorAll('.fee-checkbox');
    const totalAmountDisplay = document.getElementById('totalSelectedAmount');
    const submitBtn = document.getElementById('submitPaymentBtn');

    function updateTotalAmount() {
        let total = 0;
        let selectedCount = 0;
        
        feeCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                total += parseFloat(checkbox.dataset.amount);
                selectedCount++;
            }
        });

        if (totalAmountDisplay) {
            totalAmountDisplay.textContent = total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        }

        if (submitBtn) {
            submitBtn.disabled = selectedCount === 0;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            feeCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateTotalAmount();
        });
    }

    feeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTotalAmount();
            
            // Update "Select All" checkbox state
            if (selectAllCheckbox) {
                const allChecked = Array.from(feeCheckboxes).every(cb => cb.checked);
                const noneChecked = Array.from(feeCheckboxes).every(cb => !cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
            }
        });
    });

    // Initial calculation
    updateTotalAmount();
    
    // Single fee payment button handler
    document.querySelectorAll('.pay-single-fee-btn').forEach(button => {
        button.addEventListener('click', function() {
            const feeId = this.dataset.feeId;
            const feeName = this.dataset.feeName;
            const feeAmount = parseFloat(this.dataset.feeAmount);
            
            // Set modal content
            document.getElementById('singleFeeName').textContent = feeName;
            document.getElementById('singleFeeAmount').textContent = feeAmount.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            document.getElementById('singleFeeIdInput').value = feeId;
            document.getElementById('singleFeeTypeIdInput').value = feeId; // Fee type ID is same as charge ID
            
            // Store amount in hidden field for mobile money
            if (!document.getElementById('loanFeeAmountInput')) {
                const amountInput = document.createElement('input');
                amountInput.type = 'hidden';
                amountInput.name = 'amount';
                amountInput.id = 'loanFeeAmountInput';
                document.getElementById('paySingleFeeForm').appendChild(amountInput);
            }
            document.getElementById('loanFeeAmountInput').value = feeAmount;
            
            // Store description for mobile money
            if (!document.getElementById('loanFeeDescriptionInput')) {
                const descInput = document.createElement('input');
                descInput.type = 'hidden';
                descInput.name = 'description';
                descInput.id = 'loanFeeDescriptionInput';
                document.getElementById('paySingleFeeForm').appendChild(descInput);
            }
            document.getElementById('loanFeeDescriptionInput').value = 'Upfront payment for ' + feeName;
        });
    });
    
    // Charge type selector update description
    const chargeTypeSelect = document.getElementById('chargeTypeSelect');
    const chargeTypeDescription = document.getElementById('chargeTypeDescription');
    
    if (chargeTypeSelect && chargeTypeDescription) {
        chargeTypeSelect.addEventListener('change', function() {
            if (this.value == '1') {
                chargeTypeDescription.className = 'alert alert-primary mb-0';
                chargeTypeDescription.innerHTML = '<strong>Selected:</strong> Charges will be deducted from principal. Member receives reduced amount (Principal - Charges).';
            } else {
                chargeTypeDescription.className = 'alert alert-warning mb-0';
                chargeTypeDescription.innerHTML = '<strong>Selected:</strong> Member must pay charges before disbursement. Full principal amount will be disbursed after charges are paid.';
            }
        });
    }
});
</script>

<!-- Pay Single Fee Modal -->
<div class="modal fade" id="paySingleFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #ffc107; color: white;">
                <h5 class="modal-title"><i class="mdi mdi-cash me-2"></i>Pay Loan Charge</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.loans.pay-single-fee', $loan->id) }}" method="POST" id="paySingleFeeForm">
                @csrf
                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                <input type="hidden" name="member_id" value="{{ $member->id ?? '' }}">
                <input type="hidden" name="fee_id" id="singleFeeIdInput">
                <input type="hidden" name="fees_type_id" id="singleFeeTypeIdInput">
                <input type="hidden" name="member_phone" value="{{ $member->contact ?? '' }}">
                <input type="hidden" name="member_name" value="{{ $member ? $member->fname . ' ' . $member->lname : '' }}">
                <div class="modal-body" style="background-color: white;">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-1"></i>
                        Record payment for this upfront loan charge.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fee Name:</label>
                        <div class="form-control-plaintext fw-bold" id="singleFeeName"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (UGX):</label>
                        <div class="form-control-plaintext fw-bold text-success" style="font-size: 1.2rem;" id="singleFeeAmount"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" id="loanFeePaymentMethod" required>
                            <option value="">Select Payment Method</option>
                            <option value="1">Mobile Money</option>
                            <option value="2">Cash</option>
                            <option value="3">Bank Transfer</option>
                        </select>
                    </div>

                    <!-- Mobile Money Section (Hidden by default) -->
                    <div id="loanFeeMobileMoneySection" style="display: none;" class="mb-3">
                        @if($member)
                        <div class="alert alert-info">
                            <i class="mdi mdi-cellphone me-1"></i>
                            Payment request will be sent to: <strong>{{ $member->contact }}</strong>
                        </div>
                        <input type="hidden" name="mm_phone" value="{{ $member->contact }}">
                        @else
                        <div class="alert alert-warning">
                            <i class="mdi mdi-alert me-1"></i>
                            Member information not available for mobile money payment.
                        </div>
                        @endif
                    </div>

                    <!-- Processing Alert (Hidden by default) -->
                    <div id="loanFeeMmProcessingAlert" class="alert alert-warning" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <span id="loanFeeMmStatusText">Sending payment request...</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Notes</label>
                        <textarea class="form-control" name="payment_notes" rows="2" 
                                  placeholder="Optional notes about this payment"></textarea>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="mdi mdi-alert me-1"></i>
                        <strong>Note:</strong> Please ensure payment has been received before recording it.
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="loanFeeSubmitBtn">
                        <i class="mdi mdi-cash-check me-1"></i> <span id="loanFeeSubmitText">Record Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Retry Loan Fee Payment Modal -->
<div class="modal fade" id="retryLoanFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #dc3545; color: white;">
                <h5 class="modal-title"><i class="mdi mdi-refresh me-2"></i>Retry Loan Charge Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="retryLoanFeeForm">
                @csrf
                <input type="hidden" name="fee_id" id="retryLoanFeeId">
                <input type="hidden" name="member_name" value="{{ $member ? $member->fname . ' ' . $member->lname : '' }}">
                
                <div class="modal-body" style="background-color: white;">
                    <div class="alert alert-warning" id="retryLoanFeeAlertMessage">
                        <i class="mdi mdi-information me-1"></i>
                        <span id="retryAlertText">Previous payment attempt failed or was cancelled. You can retry with updated phone number or amount.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fee Name:</label>
                        <div class="form-control-plaintext fw-bold" id="retryLoanFeeName"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="member_phone" id="retryLoanFeePhone" 
                               value="{{ $member->contact ?? '' }}" required 
                               placeholder="256XXXXXXXXX">
                        <small class="text-muted">Update if member wants to use a different number</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (UGX) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" id="retryLoanFeeAmount" 
                               step="0.01" min="0" required>
                        <small class="text-muted">Adjust amount if needed (e.g., partial payment)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="description" id="retryLoanFeeDescription" required>
                    </div>

                    <!-- Processing Alert for Retry -->
                    <div id="retryLoanFeeMmProcessingAlert" class="alert alert-warning" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <span id="retryLoanFeeMmStatusText">Sending payment request...</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="background-color: white;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="retryLoanFeeSubmitBtn">
                        <i class="mdi mdi-refresh me-1"></i> Retry Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-check pending payments on page load
document.addEventListener('DOMContentLoaded', function() {
    // Find all pending fees and check their status
    const checkStatusButtons = document.querySelectorAll('.check-loan-fee-status-btn');
    
    if (checkStatusButtons.length > 0) {
        console.log(`Found ${checkStatusButtons.length} pending payment(s). Auto-checking status...`);
        
        // Auto-check status for each pending payment after 2 seconds
        setTimeout(() => {
            checkStatusButtons.forEach(btn => {
                const transactionRef = btn.dataset.transactionRef;
                const feeId = btn.dataset.feeId;
                
                fetch(`/admin/loans/check-mm-status/${transactionRef}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed' || data.status === 'failed') {
                        // Status changed - reload page to update UI
                        console.log(`Payment status changed to ${data.status}. Reloading page...`);
                        location.reload();
                    } else {
                        console.log(`Payment ${transactionRef} still pending`);
                    }
                })
                .catch(error => {
                    console.error('Auto status check error:', error);
                });
            });
        }, 2000); // Wait 2 seconds after page load
    }
});

// Show/Hide Mobile Money section based on payment method for loan fees
document.getElementById('loanFeePaymentMethod').addEventListener('change', function() {
    const paymentType = this.value;
    const mobileMoneySection = document.getElementById('loanFeeMobileMoneySection');
    const submitText = document.getElementById('loanFeeSubmitText');
    
    if (paymentType === '1') { // Mobile Money
        mobileMoneySection.style.display = 'block';
        submitText.textContent = 'Send Payment Request';
    } else {
        mobileMoneySection.style.display = 'none';
        submitText.textContent = 'Record Payment';
    }
});

// Handle Loan Fee Payment Form Submission with Mobile Money
document.getElementById('paySingleFeeForm').addEventListener('submit', function(e) {
    const paymentType = document.getElementById('loanFeePaymentMethod').value;
    
    // If Mobile Money is selected, process via AJAX
    if (paymentType === '1') {
        e.preventDefault();
        processLoanFeeMobileMoneyPayment();
    }
    // Otherwise, let form submit normally for Cash/Bank
});

function processLoanFeeMobileMoneyPayment() {
    const form = document.getElementById('paySingleFeeForm');
    const submitBtn = document.getElementById('loanFeeSubmitBtn');
    const processingAlert = document.getElementById('loanFeeMmProcessingAlert');
    const statusText = document.getElementById('loanFeeMmStatusText');
    
    // Disable form and show processing
    submitBtn.disabled = true;
    processingAlert.style.display = 'block';
    statusText.textContent = 'Sending payment request to member\'s phone...';
    
    // Gather form data
    const formData = new FormData(form);
    formData.append('is_mobile_money', '1');
    
    // Send AJAX request
    fetch('{{ route("admin.loans.store-mobile-money") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Payment request sent successfully - show 30-second wait message
            statusText.innerHTML = '<i class="mdi mdi-cellphone-check me-1"></i> USSD prompt sent to member\'s phone! Waiting 30 seconds before checking status...';
            
            // Wait 30 seconds before starting to poll
            setTimeout(() => {
                statusText.innerHTML = '<i class="mdi mdi-refresh me-1"></i> Checking payment status...';
                // Start polling for payment status (120 seconds max, 5-second intervals)
                pollLoanFeePaymentStatus(data.transaction_reference, data.fee_id);
            }, 30000); // 30 seconds
        } else {
            // Request failed
            processingAlert.className = 'alert alert-danger';
            statusText.textContent = 'Error: ' + (data.message || 'Failed to send payment request');
            submitBtn.disabled = false;
            
            setTimeout(() => {
                processingAlert.style.display = 'none';
                processingAlert.className = 'alert alert-warning';
            }, 5000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        processingAlert.className = 'alert alert-danger';
        statusText.textContent = 'Network error. Please try again.';
        submitBtn.disabled = false;
        
        setTimeout(() => {
            processingAlert.style.display = 'none';
            processingAlert.className = 'alert alert-warning';
        }, 5000);
    });
}

function pollLoanFeePaymentStatus(transactionRef, feeId, attempts = 0) {
    const maxAttempts = 24; // Poll for up to 120 seconds (24 checks × 5 seconds)
    const processingAlert = document.getElementById('loanFeeMmProcessingAlert');
    const statusText = document.getElementById('loanFeeMmStatusText');
    
    if (attempts >= maxAttempts) {
        processingAlert.className = 'alert alert-warning';
        statusText.innerHTML = '<i class="mdi mdi-clock-alert-outline me-1"></i> Payment status check timed out. The payment may still be processing. Page will refresh to check final status...';
        
        setTimeout(() => {
            location.reload();
        }, 3000);
        return;
    }
    
    // Check transaction status
    fetch(`/admin/loans/check-mm-status/${transactionRef}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'completed') {
            // Payment successful
            processingAlert.className = 'alert alert-success';
            statusText.innerHTML = '<i class="mdi mdi-check-circle me-1"></i> Payment received successfully! Reloading...';
            
            setTimeout(() => {
                location.reload();
            }, 2000);
            
        } else if (data.status === 'failed') {
            // Payment failed
            processingAlert.className = 'alert alert-danger';
            statusText.innerHTML = '<i class="mdi mdi-close-circle me-1"></i> Payment failed: ' + (data.message || 'Transaction declined') + '. Reloading...';
            
            setTimeout(() => {
                location.reload();
            }, 3000);
            
        } else {
            // Still pending, continue polling every 5 seconds
            const elapsedSeconds = (attempts + 1) * 5;
            statusText.innerHTML = `<i class="mdi mdi-timer-sand me-1"></i> Waiting for member to authorize payment... (${elapsedSeconds}s elapsed)`;
            setTimeout(() => {
                pollLoanFeePaymentStatus(transactionRef, feeId, attempts + 1);
            }, 5000); // Check every 5 seconds
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        // Continue polling even on error
        const elapsedSeconds = (attempts + 1) * 5;
        statusText.innerHTML = `<i class="mdi mdi-timer-sand me-1"></i> Checking status... (${elapsedSeconds}s elapsed)`;
        setTimeout(() => {
            pollLoanFeePaymentStatus(transactionRef, feeId, attempts + 1);
        }, 5000);
    });
}

// Populate retry modal when retry button is clicked (handles both failed and pending/cancel)
document.addEventListener('click', function(e) {
    if (e.target.closest('.retry-loan-fee-btn') || e.target.closest('.cancel-and-retry-loan-fee-btn')) {
        const btn = e.target.closest('.retry-loan-fee-btn') || e.target.closest('.cancel-and-retry-loan-fee-btn');
        const feeId = btn.dataset.feeId;
        const feeName = btn.dataset.feeName;
        const feeAmount = btn.dataset.feeAmount;
        const isPending = btn.classList.contains('cancel-and-retry-loan-fee-btn');
        
        document.getElementById('retryLoanFeeId').value = feeId;
        document.getElementById('retryLoanFeeName').textContent = feeName;
        document.getElementById('retryLoanFeeAmount').value = feeAmount;
        
        // Update alert message based on payment status
        const alertText = document.getElementById('retryAlertText');
        if (isPending) {
            alertText.innerHTML = '<strong>Cancelling pending payment:</strong> This payment is still pending (member didn\'t authorize it). This will cancel it and start a fresh payment request.';
            document.getElementById('retryLoanFeeDescription').value = `Cancelled pending payment - Retry: ${feeName}`;
        } else {
            alertText.innerHTML = 'Previous payment attempt failed. You can retry with updated phone number or amount.';
            document.getElementById('retryLoanFeeDescription').value = `Retry: Loan charge payment - ${feeName}`;
        }
    }
});

// Handle retry form submission
document.getElementById('retryLoanFeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = document.getElementById('retryLoanFeeSubmitBtn');
    const processingAlert = document.getElementById('retryLoanFeeMmProcessingAlert');
    const statusText = document.getElementById('retryLoanFeeMmStatusText');
    
    // Disable submit button and show processing
    submitBtn.disabled = true;
    processingAlert.style.display = 'block';
    statusText.textContent = 'Sending retry payment request...';
    
    // Gather form data
    const formData = new FormData(form);
    
    // Send retry request
    fetch('{{ route("admin.loans.retry-mobile-money") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Retry request sent successfully - show 30-second wait message
            statusText.innerHTML = '<i class="mdi mdi-cellphone-check me-1"></i> USSD prompt sent! Waiting 30 seconds before checking status...';
            
            // Wait 30 seconds before starting to poll
            setTimeout(() => {
                statusText.innerHTML = '<i class="mdi mdi-refresh me-1"></i> Checking payment status...';
                // Start polling (using same function, 120s max, 5s intervals)
                pollLoanFeePaymentStatus(data.transaction_reference, data.fee_id);
            }, 30000); // 30 seconds
        } else {
            // Retry failed
            processingAlert.className = 'alert alert-danger';
            statusText.textContent = 'Error: ' + (data.message || 'Failed to retry payment');
            submitBtn.disabled = false;
            
            setTimeout(() => {
                processingAlert.style.display = 'none';
                processingAlert.className = 'alert alert-warning';
            }, 5000);
        }
    })
    .catch(error => {
        console.error('Retry error:', error);
        processingAlert.className = 'alert alert-danger';
        statusText.textContent = 'Network error. Please try again.';
        submitBtn.disabled = false;
        
        setTimeout(() => {
            processingAlert.style.display = 'none';
            processingAlert.className = 'alert alert-warning';
        }, 5000);
    });
});

// Handle "Check Status" button for pending payments
document.addEventListener('click', function(e) {
    if (e.target.closest('.check-loan-fee-status-btn')) {
        const btn = e.target.closest('.check-loan-fee-status-btn');
        const transactionRef = btn.dataset.transactionRef;
        const feeId = btn.dataset.feeId;
        const feeName = btn.dataset.feeName;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';
        
        // Check status immediately
        fetch(`/admin/loans/check-mm-status/${transactionRef}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                // Payment successful - reload page
                alert('Payment completed successfully!');
                location.reload();
            } else if (data.status === 'failed') {
                // Payment failed - reload to show retry button
                alert('Payment failed: ' + (data.message || 'Transaction declined') + '. You can now retry the payment.');
                location.reload();
            } else {
                // Still pending
                alert('Payment is still pending. Please wait for the member to authorize the payment, or check again in a few moments.');
                btn.disabled = false;
                btn.innerHTML = '<i class="mdi mdi-refresh"></i> Check Status';
            }
        })
        .catch(error => {
            console.error('Status check error:', error);
            alert('Error checking status. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="mdi mdi-refresh"></i> Check Status';
        });
    }
});
</script>
@endpush
