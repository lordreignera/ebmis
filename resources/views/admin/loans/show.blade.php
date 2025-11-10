@extends('layouts.admin')

@section('title', 'Loan Details - ' . $loan->code)

@section('content')
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
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveLoanModal">
                    <i class="mdi mdi-check me-1"></i> Approve Loan
                </button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectLoanModal">
                    <i class="mdi mdi-close me-1"></i> Reject Loan
                </button>
                @elseif($loan->status == 1)
                <a href="{{ route('admin.disbursements.approve.show', $loan->id) }}?type={{ $loanType }}" class="btn btn-primary">
                    <i class="mdi mdi-cash-multiple me-1"></i> Process Disbursement
                </a>
                @endif
            </div>
        </div>
    </div>

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
                            
                            <!-- Charge Type Badge -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert {{ $loan->charge_type == 1 ? 'alert-primary' : 'alert-warning' }}">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <strong>Charge Type:</strong> 
                                                @if($loan->charge_type == 1)
                                                    <span class="badge bg-primary">Charges Deducted on Disbursement</span>
                                                    <p class="mb-0 mt-2 small">All charges will be automatically deducted from the principal amount at disbursement.</p>
                                                @else
                                                    <span class="badge bg-warning">Charges Paid Upfront</span>
                                                    <p class="mb-0 mt-2 small">Member must pay all charges BEFORE loan disbursement.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
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
                                    $upfrontFeeTypes = \App\Models\FeeType::where('required_disbursement', 1)
                                                                           ->where('isactive', 1)
                                                                           ->get();
                                    foreach ($upfrontFeeTypes as $feeType) {
                                        $paidFee = \App\Models\Fee::where('loan_id', $loan->id)
                                                                   ->where('fees_type_id', $feeType->id)
                                                                   ->where('status', 1)
                                                                   ->first();
                                        $upfrontFees[] = [
                                            'name' => $feeType->name,
                                            'amount' => $paidFee ? $paidFee->amount : 0,
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
                                            <th width="35%">Description</th>
                                            <th width="20%">Calculation</th>
                                            <th width="20%" class="text-end">Amount (UGX)</th>
                                            <th width="20%" class="text-center">Type</th>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.loans.approve', $loan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-1"></i>
                        You are about to approve loan <strong>{{ $loan->code }}</strong>. This will forward it to disbursement.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea class="form-control" name="comments" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-check me-1"></i> Forward to Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Loan Modal -->
<div class="modal fade" id="rejectLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.loans.reject', $loan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="loan_type" value="{{ $loanType }}">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert me-1"></i>
                        You are about to reject loan <strong>{{ $loan->code }}</strong>. This action cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="comments" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-close me-1"></i> Reject Loan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
