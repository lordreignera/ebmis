@extends('layouts.admin')

@section('title', 'Member Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Member Header Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-account"></i> Member Details
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-warning">
                            <i class="mdi mdi-pencil"></i> Edit
                        </a>
                        @if($member->isPending())
                            <button type="button" class="btn btn-success" onclick="approveMember({{ $member->id }})">
                                <i class="mdi mdi-check"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectMember({{ $member->id }})">
                                <i class="mdi mdi-close"></i> Reject
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            @if($member->pp_file)
                                <img src="{{ Storage::url($member->pp_file) }}" 
                                     class="rounded-circle mb-3" width="150" height="150" alt="Member Photo">
                            @else
                                <div class="bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                                     style="width: 150px; height: 150px;">
                                    <span class="text-white" style="font-size: 3rem; font-weight: bold;">
                                        {{ substr($member->fname, 0, 1) }}{{ substr($member->lname, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                            
                            <h4>{{ $member->fname }} {{ $member->mname }} {{ $member->lname }}</h4>
                            <p class="text-muted">{{ $member->code ?? 'No Code Assigned' }}</p>
                            
                            <!-- Status Badges -->
                            <div class="mb-3">
                                <span class="badge {{ $member->status_badge }} fs-6 mb-2">
                                    {{ $member->status_display }}
                                </span>
                                <br>
                                @if($member->verified)
                                    <span class="badge bg-success fs-6">
                                        <i class="mdi mdi-shield-check"></i> Verified
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark fs-6">
                                        <i class="mdi mdi-shield-alert"></i> Unverified
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="mdi mdi-card-account-details text-primary"></i> Personal Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>NIN:</strong></td>
                                            <td>{{ $member->nin }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Gender:</strong></td>
                                            <td>{{ $member->gender ?? 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date of Birth:</strong></td>
                                            <td>{{ $member->dob ? $member->dob->format('M d, Y') : 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Member Type:</strong></td>
                                            <td>
                                                <span class="badge {{ $member->member_type == 1 ? 'bg-primary' : 'bg-info' }}">
                                                    {{ $member->member_type == 1 ? 'Individual' : 'Group Member' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @if($member->group)
                                            <tr>
                                                <td><strong>Group:</strong></td>
                                                <td>{{ $member->group->name }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5><i class="mdi mdi-phone text-success"></i> Contact Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Primary Contact:</strong></td>
                                            <td><a href="tel:{{ $member->contact }}" class="text-primary">{{ $member->contact }}</a></td>
                                        </tr>
                                        @if($member->alt_contact)
                                            <tr>
                                                <td><strong>Alternative Contact:</strong></td>
                                                <td><a href="tel:{{ $member->alt_contact }}" class="text-primary">{{ $member->alt_contact }}</a></td>
                                            </tr>
                                        @endif
                                        @if($member->email)
                                            <tr>
                                                <td><strong>Email:</strong></td>
                                                <td><a href="mailto:{{ $member->email }}" class="text-primary">{{ $member->email }}</a></td>
                                            </tr>
                                        @endif
                                        @if($member->fixed_line)
                                            <tr>
                                                <td><strong>Fixed Line:</strong></td>
                                                <td>{{ $member->fixed_line }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="mdi mdi-map-marker text-warning"></i> Address Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Plot No:</strong></td>
                                    <td>{{ $member->plot_no ?? 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Village:</strong></td>
                                    <td>{{ $member->village ?? 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Parish:</strong></td>
                                    <td>{{ $member->parish ?? 'Not specified' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Subcounty:</strong></td>
                                    <td>{{ $member->subcounty ?? 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>County:</strong></td>
                                    <td>{{ $member->county ?? 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Country:</strong></td>
                                    <td>{{ $member->country->name ?? 'Not specified' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="mdi mdi-cog text-info"></i> System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Branch:</strong></td>
                                    <td>{{ $member->branch->name ?? 'Not assigned' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Added By:</strong></td>
                                    <td>{{ $member->addedBy->name ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registration Date:</strong></td>
                                    <td>{{ $member->created_at ? $member->created_at->format('M d, Y H:i') : ($member->datecreated ? $member->datecreated->format('M d, Y H:i') : 'N/A') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($member->approved_by)
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Approved By:</strong></td>
                                        <td>{{ $member->approvedBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Approved At:</strong></td>
                                        <td>{{ $member->approved_at ? $member->approved_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @if($member->approval_notes)
                                        <tr>
                                            <td><strong>Approval Notes:</strong></td>
                                            <td>{{ $member->approval_notes }}</td>
                                        </tr>
                                    @endif
                                </table>
                            @endif
                            
                            @if($member->comments)
                                <div class="mt-3">
                                    <strong>Comments:</strong>
                                    <p class="text-muted">{{ $member->comments }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            @if($member->pp_file || $member->id_file)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="mdi mdi-file-document text-secondary"></i> Documents</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if($member->pp_file)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Profile Photo</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <img src="{{ Storage::url($member->pp_file) }}" 
                                                 class="img-fluid rounded" style="max-height: 200px;" alt="Profile Photo">
                                            <br>
                                            <a href="{{ Storage::url($member->pp_file) }}" target="_blank" class="btn btn-sm btn-primary mt-2">
                                                <i class="mdi mdi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($member->id_file)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">ID Document</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <img src="{{ Storage::url($member->id_file) }}" 
                                                 class="img-fluid rounded" style="max-height: 200px;" alt="ID Document">
                                            <br>
                                            <a href="{{ Storage::url($member->id_file) }}" target="_blank" class="btn btn-sm btn-primary mt-2">
                                                <i class="mdi mdi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Loans Information -->
            @if($member->loans->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="mdi mdi-cash-multiple text-success"></i> Loan History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loan Code</th>
                                        <th>Product</th>
                                        <th>Principal</th>
                                        <th>Interest</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($member->loans as $loan)
                                        <tr>
                                            <td><span class="badge bg-secondary">{{ $loan->code }}</span></td>
                                            <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                            <td>UGX {{ number_format($loan->principal, 2) }}</td>
                                            <td>{{ $loan->interest }}%</td>
                                            <td>{{ $loan->period }} {{ $loan->period_type }}</td>
                                            <td>
                                                @php
                                                    $statusClass = match($loan->status) {
                                                        0 => 'bg-warning text-dark',
                                                        1 => 'bg-info',
                                                        2 => 'bg-success',
                                                        3 => 'bg-primary',
                                                        default => 'bg-secondary'
                                                    };
                                                    $statusText = match($loan->status) {
                                                        0 => 'Pending',
                                                        1 => 'Approved',
                                                        2 => 'Disbursed',
                                                        3 => 'Completed',
                                                        default => 'Unknown'
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.loans.show', $loan) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="mdi mdi-eye"></i> View
                                                    </a>
                                                    @if($loan->status == 2 && $loan->outstanding_balance > 0)
                                                        <a href="{{ route('admin.repayments.create', ['loan_id' => $loan->id]) }}" 
                                                           class="btn btn-sm btn-outline-success" title="Record Payment">
                                                            <i class="mdi mdi-cash"></i> Pay
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Financial Information Tabs -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="mdi mdi-finance"></i> Financial Information</h5>
                </div>
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="financialTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button" role="tab">
                                <i class="mdi mdi-credit-card"></i> Fees & Payments
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cash-security-tab" data-bs-toggle="tab" data-bs-target="#cash-security" type="button" role="tab">
                                <i class="mdi mdi-shield-check"></i> Cash Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="savings-tab" data-bs-toggle="tab" data-bs-target="#savings" type="button" role="tab">
                                <i class="mdi mdi-piggy-bank"></i> Savings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">
                                <i class="mdi mdi-chart-pie"></i> Summary
                            </button>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content mt-3" id="financialTabsContent">
                        <!-- Fees & Payments Tab -->
                        <div class="tab-pane fade show active" id="fees" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6><i class="mdi mdi-credit-card text-primary"></i> Fee Payments History</h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                                        <i class="mdi mdi-plus"></i> Add Fee Payment
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Fee Type</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($member->fees as $fee)
                                            <tr>
                                                <td>{{ $fee->created_at ? $fee->created_at->format('d M Y') : ($fee->datecreated ? $fee->datecreated->format('d M Y') : 'N/A') }}</td>
                                                <td>{{ $fee->feeType->name ?? 'N/A' }}</td>
                                                <td><strong>UGX {{ number_format($fee->amount, 2) }}</strong></td>
                                                <td>
                                                    @php
                                                        $paymentMethods = [1 => 'Mobile Money', 2 => 'Cash', 3 => 'Bank'];
                                                    @endphp
                                                    <span class="badge bg-info">
                                                        {{ $paymentMethods[$fee->payment_type] ?? 'Unknown' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($fee->status == 1)
                                                        <span class="badge bg-success">Paid</span>
                                                    @elseif($fee->status == 2)
                                                        <span class="badge bg-danger">Failed</span>
                                                    @else
                                                        <span class="badge bg-warning">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($fee->status == 1)
                                                        <button class="btn btn-sm btn-outline-primary receipt-btn" data-fee-id="{{ $fee->id }}">
                                                            <i class="mdi mdi-receipt"></i> Receipt
                                                        </button>
                                                    @elseif(($fee->status == 2 || $fee->status == 0) && $fee->payment_type == 1)
                                                        <!-- Show retry for both Failed (2) and Pending (0) mobile money payments -->
                                                        <button class="btn btn-sm btn-warning retry-payment-btn" 
                                                                data-fee-id="{{ $fee->id }}"
                                                                data-member-phone="{{ $member->contact }}"
                                                                data-member-name="{{ $member->fname }} {{ $member->lname }}"
                                                                data-amount="{{ $fee->amount }}"
                                                                data-fee-type="{{ $fee->feeType->name ?? 'Fee' }}">
                                                            <i class="mdi mdi-refresh"></i> Retry Payment
                                                        </button>
                                                        <!-- Add Check Status button for pending/failed mobile money payments -->
                                                        @if(!empty($fee->pay_ref))
                                                        <button class="btn btn-sm btn-info check-status-btn ms-1" 
                                                                data-transaction-ref="{{ $fee->pay_ref }}"
                                                                data-fee-id="{{ $fee->id }}">
                                                            <i class="mdi mdi-cash-check"></i> Check Status Now
                                                        </button>
                                                        @endif
                                                    @elseif($fee->status == 0)
                                                        <!-- For pending non-mobile money payments, show a status check button -->
                                                        <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                                            <i class="mdi mdi-refresh"></i> Check Status
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="mdi mdi-credit-card-off mdi-48px text-muted"></i>
                                                    <br>No fee payments found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Cash Security Tab -->
                        <div class="tab-pane fade" id="cash-security" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6><i class="mdi mdi-shield-check text-success"></i> Cash Security History</h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCashSecurityModal">
                                        <i class="mdi mdi-plus"></i> Add Cash Security
                                    </button>
                                </div>
                            </div>
                            
                            @php
                                $cashSecurityFees = $member->fees->where('fee_type_id', 7); // Individual affiliation fee is cash security
                                $totalCashSecurity = $cashSecurityFees->sum('amount');
                            @endphp
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h4 class="mb-0">UGX {{ number_format($totalCashSecurity, 2) }}</h4>
                                            <small>Total Cash Security</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($cashSecurityFees as $security)
                                            <tr>
                                                <td>{{ $security->created_at ? $security->created_at->format('d M Y') : ($security->datecreated ? $security->datecreated->format('d M Y') : 'N/A') }}</td>
                                                <td><strong>UGX {{ number_format($security->amount, 2) }}</strong></td>
                                                <td>
                                                    @php
                                                        $paymentMethods = [1 => 'Mobile Money', 2 => 'Cash', 3 => 'Bank'];
                                                    @endphp
                                                    <span class="badge bg-info">
                                                        {{ $paymentMethods[$security->payment_type] ?? 'Unknown' }}
                                                    </span>
                                                </td>
                                                <td>{{ $security->description }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary receipt-btn" data-fee-id="{{ $security->id }}">
                                                        <i class="mdi mdi-receipt"></i> Receipt
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="mdi mdi-shield-off mdi-48px text-muted"></i>
                                                    <br>No cash security payments found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Savings Tab -->
                        <div class="tab-pane fade" id="savings" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6><i class="mdi mdi-piggy-bank text-info"></i> Savings Accounts</h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addSavingsModal">
                                        <i class="mdi mdi-plus"></i> Add Savings
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Balance</th>
                                            <th>Interest Rate</th>
                                            <th>Created Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($member->savings as $saving)
                                            <tr>
                                                <td>{{ $saving->product->name ?? 'N/A' }}</td>
                                                <td><strong>UGX {{ number_format($saving->balance, 2) }}</strong></td>
                                                <td>{{ $saving->product->interest ?? 0 }}%</td>
                                                <td>{{ $saving->created_at ? $saving->created_at->format('d M Y') : ($saving->datecreated ? $saving->datecreated->format('d M Y') : 'N/A') }}</td>
                                                <td>
                                                    <span class="badge {{ $saving->is_active ? 'bg-success' : 'bg-warning' }}">
                                                        {{ $saving->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.savings.show', $saving) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="mdi mdi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="mdi mdi-piggy-bank-outline mdi-48px text-muted"></i>
                                                    <br>No savings accounts found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Summary Tab -->
                        <div class="tab-pane fade" id="summary" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h4 class="mb-0">{{ $member->loans->count() }}</h4>
                                            <small>Total Loans</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h4 class="mb-0">UGX {{ number_format($member->fees->sum('amount'), 2) }}</h4>
                                            <small>Total Fees Paid</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h4 class="mb-0">UGX {{ number_format($member->savings->sum('balance'), 2) }}</h4>
                                            <small>Total Savings</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body">
                                            <h4 class="mb-0">UGX {{ number_format($totalCashSecurity, 2) }}</h4>
                                            <small>Cash Security</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="mdi mdi-lightning-bolt text-warning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            @if($member->canApplyForLoans())
                                <a href="{{ route('admin.loans.create', ['member_id' => $member->id]) }}" class="btn btn-primary w-100">
                                    <i class="mdi mdi-cash-plus"></i> Create Loan
                                </a>
                            @else
                                <button class="btn btn-secondary w-100" disabled title="Member must be approved to apply for loans">
                                    <i class="mdi mdi-cash-remove"></i> Cannot Create Loan
                                </button>
                            @endif
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addSavingsModal">
                                <i class="mdi mdi-piggy-bank"></i> Add Savings
                            </button>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                                <i class="mdi mdi-credit-card"></i> Add Fee Payment
                            </button>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#addCashSecurityModal">
                                <i class="mdi mdi-shield-check"></i> Cash Security
                            </button>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="{{ route('admin.repayments.create', ['member_id' => $member->id]) }}" class="btn btn-secondary w-100">
                                <i class="mdi mdi-cash-register"></i> Record Payment
                            </a>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="#" class="btn btn-dark w-100">
                                <i class="mdi mdi-message-text"></i> Send SMS
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white; border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 25px 30px;">
                <div>
                    <h5 class="modal-title mb-1" style="font-weight: 600; font-size: 1.4rem;">
                        <i class="mdi mdi-check-circle me-2"></i>Approve Member
                    </h5>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Confirm member approval and add notes</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 1;"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 30px;">
                    <div class="alert alert-success" style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 10px;">
                        <i class="mdi mdi-information-outline me-2"></i>
                        <strong>Approval Confirmation:</strong> This member will be activated and can access all services.
                    </div>
                    
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label" style="font-weight: 600; color: #2c3e50; margin-bottom: 10px;">
                            <i class="mdi mdi-note-text-outline me-1"></i>Approval Notes (Optional)
                        </label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="4"
                                  placeholder="Add any notes about this approval (e.g., Documents verified, requirements met...)"
                                  style="border: 2px solid #e0e0e0; border-radius: 10px; padding: 15px; font-size: 0.95rem; transition: all 0.3s;"
                                  onfocus="this.style.borderColor='#28a745'; this.style.boxShadow='0 0 0 0.2rem rgba(40, 167, 69, 0.1)'"
                                  onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border: none; border-radius: 0 0 15px 15px; padding: 20px 30px; gap: 10px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="padding: 12px 30px; border-radius: 8px; font-weight: 500; border: 2px solid #e0e0e0;">
                        <i class="mdi mdi-close me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" style="padding: 12px 30px; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);">
                        <i class="mdi mdi-check-circle me-1"></i>Approve Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white; border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 25px 30px;">
                <div>
                    <h5 class="modal-title mb-1" style="font-weight: 600; font-size: 1.4rem;">
                        <i class="mdi mdi-close-circle me-2"></i>Reject Member
                    </h5>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Provide a reason for rejecting this application</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 1;"></button>
            </div>
            <form id="rejectionForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 30px;">
                    <div class="alert alert-danger" style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 10px;">
                        <i class="mdi mdi-alert-outline me-2"></i>
                        <strong>Important:</strong> This member will be rejected and must provide a clear reason.
                    </div>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label" style="font-weight: 600; color: #2c3e50; margin-bottom: 10px;">
                            <i class="mdi mdi-comment-alert-outline me-1"></i>Rejection Reason <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4"
                                  placeholder="Explain why this member is being rejected (e.g., Incomplete documents, Invalid information...)"
                                  style="border: 2px solid #e0e0e0; border-radius: 10px; padding: 15px; font-size: 0.95rem; transition: all 0.3s;"
                                  onfocus="this.style.borderColor='#dc3545'; this.style.boxShadow='0 0 0 0.2rem rgba(220, 53, 69, 0.1)'"
                                  onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"
                                  required></textarea>
                        <small class="text-muted">
                            <i class="mdi mdi-information-outline"></i> This reason will be saved and can be reviewed later.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border: none; border-radius: 0 0 15px 15px; padding: 20px 30px; gap: 10px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="padding: 12px 30px; border-radius: 8px; font-weight: 500; border: 2px solid #e0e0e0;">
                        <i class="mdi mdi-close me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" style="padding: 12px 30px; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);">
                        <i class="mdi mdi-close-circle me-1"></i>Reject Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Fee Payment Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: white; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #000;">Add Fee Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.fees.store') }}" method="POST" id="feePaymentForm">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <input type="hidden" name="member_phone" id="member_phone" value="{{ $member->contact }}">
                <input type="hidden" name="member_name" id="member_name" value="{{ $member->fname }} {{ $member->lname }}">
                <div class="modal-body" style="background-color: white;">
                    <div class="mb-3">
                        <label for="fee_type_id" class="form-label" style="color: #000;">Fee Type <span class="text-danger">*</span></label>
                        <select name="fee_type_id" id="fee_type_id" class="form-select" required>
                            <option value="">Select Fee Type</option>
                            @foreach($feeTypes as $feeType)
                                @if($feeType->id != 7) {{-- Exclude cash security fee type --}}
                                    <option value="{{ $feeType->id }}">{{ $feeType->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label" style="color: #000;">Amount (UGX) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="fee_amount" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_type" class="form-label" style="color: #000;">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_type" id="payment_type" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="1">Mobile Money</option>
                            <option value="2">Cash</option>
                            <option value="3">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <!-- Mobile Money Payment Section (Hidden by default) -->
                    <div id="mobileMoneySection" style="display: none;">
                        <div class="alert alert-info mb-3">
                            <i class="mdi mdi-information"></i>
                            <strong>Mobile Money Payment</strong><br>
                            A payment request will be sent to: <strong>{{ $member->contact }}</strong><br>
                            <small>The member will receive a prompt on their phone to authorize the payment.</small>
                        </div>
                        <div class="mb-3">
                            <label for="mm_phone" class="form-label">Phone Number</label>
                            <input type="tel" name="mm_phone" id="mm_phone" class="form-control" 
                                   value="{{ $member->contact }}" readonly style="background-color: #e9ecef;">
                            <small class="text-muted">Member's registered phone number</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label" style="color: #000;">Description</label>
                        <textarea name="description" id="fee_description" class="form-control" rows="3" 
                                  placeholder="Additional notes about this payment"></textarea>
                    </div>
                    
                    <!-- Processing Status Alert (Hidden by default) -->
                    <div id="mmProcessingAlert" class="alert alert-warning" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <span id="mmStatusText">Processing mobile money payment...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="feeSubmitBtn">
                        <span id="feeSubmitText">Add Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Cash Security Modal -->
<div class="modal fade" id="addCashSecurityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Cash Security</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.fees.store') }}" method="POST">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <input type="hidden" name="fee_type_id" value="7"> {{-- Individual affiliation fee is cash security --}}
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cash_amount" class="form-label">Amount (UGX) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="cash_amount" class="form-control" min="1" required>
                        <small class="form-text text-muted">Enter the cash security amount to be paid</small>
                    </div>
                    <div class="mb-3">
                        <label for="cash_payment_type" class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_type" id="cash_payment_type" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="1">Mobile Money</option>
                            <option value="2">Cash</option>
                            <option value="3">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cash_description" class="form-label">Description</label>
                        <textarea name="description" id="cash_description" class="form-control" rows="3" 
                                  placeholder="Cash security payment notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Cash Security</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Retry Payment Modal -->
<div class="modal fade" id="retryPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: white; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #000;">
                    <i class="mdi mdi-refresh"></i> Retry Mobile Money Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="retryPaymentForm">
                <input type="hidden" id="retryFeeId">
                <input type="hidden" id="retryMemberPhone">
                <input type="hidden" id="retryMemberName">
                
                <div class="modal-body" style="background-color: white;">
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert"></i> <strong>Retry Payment</strong><br>
                        A new payment request will be sent for: <strong id="retryFeeType"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="retryPhoneNumber" class="form-label">
                            Phone Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="retryPhoneNumber" class="form-control" 
                               placeholder="256772123456" required>
                        <small class="text-muted">
                            <i class="mdi mdi-information"></i> 
                            <span id="retryNetworkInfo">Original: <strong id="retryOriginalPhone"></strong></span>
                            <br>
                            <span class="text-info">💡 Tip: Change MTN (077/078/076) to Airtel (075/070/074) if needed</span>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="retryAmount" class="form-label">
                            Amount (UGX) <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="retryAmount" class="form-control" 
                               min="500" step="1" required>
                        <small class="text-muted">
                            <i class="mdi mdi-information"></i> Minimum amount: 500 UGX
                        </small>
                    </div>
                    
                    <div id="retryProcessingAlert" class="alert alert-warning" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <span id="retryStatusText">Sending payment request...</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="retrySubmitBtn">
                        <i class="mdi mdi-refresh"></i> Retry Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approveMember(memberId) {
    document.getElementById('approvalForm').action = `/admin/members/${memberId}/approve`;
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function rejectMember(memberId) {
    document.getElementById('rejectionForm').action = `/admin/members/${memberId}/reject`;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

// Receipt modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const receiptButtons = document.querySelectorAll('.receipt-btn');
    
    receiptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const feeId = this.getAttribute('data-fee-id');
            showReceiptModal(feeId);
        });
    });
});

function showReceiptModal(feeId) {
    // Show loading state
    const modalBody = document.getElementById('receiptModalBody');
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    modal.show();
    
    // Fetch receipt content
    fetch(`/admin/fees/${feeId}/receipt-modal`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load receipt</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading receipt</div>';
            console.error('Error:', error);
        });
}

function printReceipt() {
    window.print();
}

function downloadReceipt() {
    // Create a new window for printing/downloading
    const receiptContent = document.querySelector('.receipt-modal-content').innerHTML;
    const newWindow = window.open('', '', 'width=800,height=600');
    newWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            ${receiptContent}
        </body>
        </html>
    `);
    newWindow.document.close();
    newWindow.focus();
    newWindow.print();
    newWindow.close();
}
</script>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: white; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="receiptModalLabel" style="color: #000;">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptModalBody" style="background-color: white;">
                <!-- Receipt content will be loaded here -->
            </div>
            <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="mdi mdi-printer"></i> Print
                </button>
                <button type="button" class="btn btn-success" onclick="downloadReceipt()">
                    <i class="mdi mdi-download"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Savings Account Modal -->
<div class="modal fade" id="addSavingsModal" tabindex="-1" aria-labelledby="addSavingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <form action="{{ route('admin.savings.store') }}" method="POST" id="addSavingsForm">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <input type="hidden" name="branch_id" value="{{ $member->branch_id }}">
                
                <div class="modal-header" style="background-color: white; border-bottom: 1px solid #dee2e6;">
                    <h5 class="modal-title" id="addSavingsModalLabel" style="color: #000;">Add Savings Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body" style="background-color: white;">
                    <!-- Savings Product -->
                    <div class="mb-3">
                        <label for="product_id" class="form-label">
                            Savings Product <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Savings Product</option>
                            @php
                                $savingsProducts = \App\Models\SavingsProduct::where('isactive', 1)->get();
                            @endphp
                            @forelse($savingsProducts as $product)
                                <option value="{{ $product->id }}" 
                                        data-interest="{{ $product->interest }}"
                                        data-min="{{ $product->min_amt ?? 0 }}"
                                        data-max="{{ $product->max_amt ?? 0 }}">
                                    {{ $product->name }}
                                </option>
                            @empty
                                <option value="" disabled>No savings products available</option>
                            @endforelse
                        </select>
                        @if($savingsProducts->isEmpty())
                            <small class="text-warning d-block mt-1">
                                <i class="mdi mdi-alert"></i> No savings products configured. 
                                <a href="{{ route('admin.settings.savings-products') }}" target="_blank">Add one now</a>
                            </small>
                        @endif
                    </div>

                    <!-- Initial Deposit -->
                    <div class="mb-3">
                        <label for="initial_deposit" class="form-label">
                            Initial Deposit (UGX)
                        </label>
                        <input type="number" class="form-control" id="initial_deposit" name="initial_deposit" 
                               step="0.01" min="0" placeholder="0.00">
                    </div>

                    <!-- Interest Rate (Auto-filled, read-only) -->
                    <div class="mb-3">
                        <label for="interest" class="form-label">
                            Interest Rate (%) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="interest" name="interest" 
                               step="0.01" min="0" max="100" placeholder="0.00" 
                               readonly style="background-color: #e9ecef;">
                    </div>

                    <!-- Description/Notes -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Additional notes about this savings account"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Savings Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endpush

@push('scripts')
<script>
// Show/Hide Mobile Money section based on payment method
document.getElementById('payment_type').addEventListener('change', function() {
    const paymentType = this.value;
    const mobileMoneySection = document.getElementById('mobileMoneySection');
    const submitBtn = document.getElementById('feeSubmitBtn');
    const submitText = document.getElementById('feeSubmitText');
    
    if (paymentType === '1') { // Mobile Money
        mobileMoneySection.style.display = 'block';
        submitText.textContent = 'Send Payment Request';
    } else {
        mobileMoneySection.style.display = 'none';
        submitText.textContent = 'Add Payment';
    }
});

// Handle Fee Payment Form Submission with Mobile Money
document.getElementById('feePaymentForm').addEventListener('submit', function(e) {
    const paymentType = document.getElementById('payment_type').value;
    
    // If Mobile Money is selected, process via AJAX
    if (paymentType === '1') {
        e.preventDefault();
        processMobileMoneyPayment();
    }
    // Otherwise, let form submit normally for Cash/Bank
});

function processMobileMoneyPayment() {
    const form = document.getElementById('feePaymentForm');
    const submitBtn = document.getElementById('feeSubmitBtn');
    const processingAlert = document.getElementById('mmProcessingAlert');
    const statusText = document.getElementById('mmStatusText');
    
    // Disable form and show processing
    submitBtn.disabled = true;
    processingAlert.style.display = 'block';
    statusText.textContent = 'Sending payment request to member\'s phone...';
    
    // Gather form data
    const formData = new FormData(form);
    formData.append('is_mobile_money', '1');
    
    // Map fee_type_id to fees_type_id for mobile money endpoint
    const feeTypeId = document.getElementById('fee_type_id').value;
    if (feeTypeId) {
        formData.append('fees_type_id', feeTypeId);
    }
    
    // Send AJAX request
    fetch('{{ route("admin.fees.store-mobile-money") }}', {
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
            // Payment request sent successfully
            statusText.textContent = 'Payment request sent! Checking status...';
            
            // Start polling for payment status
            pollPaymentStatus(data.transaction_reference, data.fee_id);
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

function cancelPaymentCheck() {
    // Clear any pending timeout
    if (window.paymentCheckTimeout) {
        clearTimeout(window.paymentCheckTimeout);
    }
    
    const processingAlert = document.getElementById('mmProcessingAlert');
    const statusText = document.getElementById('mmStatusText');
    
    processingAlert.className = 'alert alert-info';
    statusText.innerHTML = `
        <i class="mdi mdi-information"></i> Payment check stopped. The transaction may still be processing.<br>
        <button type="button" class="btn btn-primary btn-sm mt-2" onclick="location.reload()">
            <i class="mdi mdi-refresh"></i> Refresh to Check Status
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-2 ms-2" onclick="document.getElementById('mmProcessingAlert').style.display='none'">
            <i class="mdi mdi-close"></i> Close
        </button>
    `;
}

function pollPaymentStatus(transactionRef, feeId, attempts = 0) {
    const maxAttempts = 120; // Poll for up to 120 seconds (allow time for 3 retry attempts)
    const processingAlert = document.getElementById('mmProcessingAlert');
    const statusText = document.getElementById('mmStatusText');
    
    // Wait 30 seconds before FIRST status check (give user time to enter PIN)
    if (attempts === 0) {
        statusText.innerHTML = `
            <i class="mdi mdi-loading mdi-spin"></i> USSD prompt sent to phone. Waiting for user to enter PIN... (30s)
            <br><small class="text-muted">Please check your phone and enter your Mobile Money PIN when prompted</small>
            <br>
            <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="cancelPaymentCheck()">
                <i class="mdi mdi-close"></i> Stop Checking
            </button>
        `;
        
        // Wait 30 seconds before first check
        window.paymentCheckTimeout = setTimeout(() => {
            pollPaymentStatus(transactionRef, feeId, 1);
        }, 30000); // Wait 30 seconds
        return;
    }
    
    if (attempts >= maxAttempts) {
        // Perform a FINAL status check before showing timeout
        statusText.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Performing final status check...';
        
        fetch(`/admin/fees/check-mm-status/${transactionRef}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                // Payment was completed during polling!
                processingAlert.className = 'alert alert-success';
                statusText.innerHTML = '<i class="mdi mdi-check-circle"></i> Payment received successfully!';
                setTimeout(() => location.reload(), 2000);
            } else if (data.status === 'failed') {
                // Payment failed
                processingAlert.className = 'alert alert-danger';
                statusText.innerHTML = '<i class="mdi mdi-close-circle"></i> Payment failed: ' + (data.message || 'Transaction declined');
                setTimeout(() => location.reload(), 3000);
            } else {
                // Still pending after 60 seconds - show timeout message
                processingAlert.className = 'alert alert-warning';
                statusText.innerHTML = `
                    <i class="mdi mdi-alert"></i> Payment request timed out. The transaction may still be processing.<br>
                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="location.reload()">
                        <i class="mdi mdi-refresh"></i> Refresh Status
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2 ms-2" onclick="document.getElementById('mmProcessingAlert').style.display='none'">
                        <i class="mdi mdi-close"></i> Close
                    </button>
                `;
            }
        })
        .catch(error => {
            console.error('Final status check error:', error);
            // Show timeout message on error
            processingAlert.className = 'alert alert-warning';
            statusText.innerHTML = `
                <i class="mdi mdi-alert"></i> Unable to verify payment status. Please refresh to check.<br>
                <button type="button" class="btn btn-primary btn-sm mt-2" onclick="location.reload()">
                    <i class="mdi mdi-refresh"></i> Refresh Status
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 ms-2" onclick="document.getElementById('mmProcessingAlert').style.display='none'">
                    <i class="mdi mdi-close"></i> Close
                </button>
            `;
        });
        
        return; // Stop the polling loop
    }
    
    // Check transaction status
    fetch(`/admin/fees/check-mm-status/${transactionRef}`, {
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
            statusText.innerHTML = '<i class="mdi mdi-check-circle"></i> Payment received successfully!';
            
            setTimeout(() => {
                location.reload(); // Reload to show updated payment
            }, 2000);
            
        } else if (data.status === 'failed') {
            // Payment failed
            processingAlert.className = 'alert alert-danger';
            statusText.textContent = 'Payment failed: ' + (data.message || 'Transaction declined');
            
            setTimeout(() => {
                location.reload();
            }, 3000);
            
        } else {
            // Still pending, continue polling
            const elapsedSeconds = 30 + (attempts - 1) * 5; // 30s initial wait + checks every 5s
            const retriesRemaining = Math.floor((120 - elapsedSeconds) / 30); // FlexiPay retries every ~30s
            
            statusText.innerHTML = `
                <i class="mdi mdi-loading mdi-spin"></i> Waiting for payment authorization... (${elapsedSeconds}s elapsed)
                <br><small class="text-muted">FlexiPay will retry ${retriesRemaining} more time(s) if user cancelled. Please wait...</small>
                <br>
                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="cancelPaymentCheck()">
                    <i class="mdi mdi-close"></i> Stop Checking
                </button>
            `;
            
            // Store timeout ID so we can cancel it
            window.paymentCheckTimeout = setTimeout(() => {
                pollPaymentStatus(transactionRef, feeId, attempts + 1);
            }, 5000); // Check every 5 seconds after initial 30s wait
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        // Continue polling even on error
        setTimeout(() => {
            pollPaymentStatus(transactionRef, feeId, attempts + 1);
        }, 1000);
    });
}

// Handle Retry Payment for Failed Transactions
document.addEventListener('DOMContentLoaded', function() {
    const retryButtons = document.querySelectorAll('.retry-payment-btn');
    
    retryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const feeId = this.getAttribute('data-fee-id');
            const memberPhone = this.getAttribute('data-member-phone');
            const memberName = this.getAttribute('data-member-name');
            const amount = this.getAttribute('data-amount');
            const feeType = this.getAttribute('data-fee-type');
            
            // Show modal to edit amount before retrying
            showRetryPaymentModal(feeId, memberPhone, memberName, amount, feeType);
        });
    });
});

function detectNetwork(phone) {
    // Remove non-digits and get the phone number
    const cleanPhone = phone.replace(/\D/g, '');
    
    // Get last 9 digits (Uganda format)
    let phoneDigits = cleanPhone;
    if (cleanPhone.startsWith('256')) {
        phoneDigits = cleanPhone.substring(3);
    } else if (cleanPhone.startsWith('0')) {
        phoneDigits = cleanPhone.substring(1);
    }
    
    // Get first 2 digits
    const prefix = phoneDigits.substring(0, 2);
    
    // Detect network
    if (['77', '78', '76'].includes(prefix)) {
        return { network: 'MTN', icon: '📱', color: 'warning' };
    } else if (['75', '70', '74'].includes(prefix)) {
        return { network: 'Airtel', icon: '📞', color: 'danger' };
    }
    
    return { network: 'Unknown', icon: '❓', color: 'secondary' };
}

function showRetryPaymentModal(feeId, memberPhone, memberName, amount, feeType) {
    // Set modal data
    document.getElementById('retryFeeId').value = feeId;
    document.getElementById('retryMemberPhone').value = memberPhone;
    document.getElementById('retryMemberName').value = memberName;
    document.getElementById('retryAmount').value = amount;
    document.getElementById('retryFeeType').innerHTML = feeType;
    
    // Set editable phone number field
    document.getElementById('retryPhoneNumber').value = memberPhone;
    document.getElementById('retryOriginalPhone').innerHTML = memberPhone;
    
    // Detect and display network
    const networkInfo = detectNetwork(memberPhone);
    document.getElementById('retryOriginalPhone').innerHTML = `${memberPhone} (${networkInfo.icon} ${networkInfo.network})`;
    
    // Hide processing alert
    document.getElementById('retryProcessingAlert').style.display = 'none';
    
    // Add real-time network detection on phone input
    document.getElementById('retryPhoneNumber').oninput = function(e) {
        const newPhone = e.target.value;
        const newNetwork = detectNetwork(newPhone);
        
        // Update border color based on network
        if (newNetwork.network === 'MTN') {
            e.target.className = 'form-control border-warning';
        } else if (newNetwork.network === 'Airtel') {
            e.target.className = 'form-control border-success';
        } else {
            e.target.className = 'form-control';
        }
    };
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('retryPaymentModal'));
    modal.show();
    
    // Handle form submission
    document.getElementById('retryPaymentForm').onsubmit = function(e) {
        e.preventDefault();
        
        // Get updated values from modal
        const updatedPhone = document.getElementById('retryPhoneNumber').value.trim();
        const updatedAmount = document.getElementById('retryAmount').value;
        
        // Validate phone number
        if (!updatedPhone || updatedPhone.length < 9) {
            alert('Please enter a valid phone number');
            return;
        }
        
        // Validate minimum amount
        if (parseFloat(updatedAmount) < 500) {
            alert('The minimum amount should be 500 UGX');
            return;
        }
        
        // Detect network and warn if MTN
        const networkInfo = detectNetwork(updatedPhone);
        if (networkInfo.network === 'MTN') {
            if (!confirm('⚠️ Warning: MTN payments are currently not sending prompts due to missing merchant credentials.\n\nConsider changing to an Airtel number (075/070/074) instead.\n\nContinue with MTN anyway?')) {
                return;
            }
        }
        
        // Call retry function with updated phone and amount
        retryMobileMoneyPayment(feeId, updatedPhone, memberName, updatedAmount, feeType);
    };
}

function retryMobileMoneyPayment(feeId, memberPhone, memberName, amount, feeType) {
    // Show processing alert in modal
    const processingAlert = document.getElementById('retryProcessingAlert');
    const statusText = document.getElementById('retryStatusText');
    const submitBtn = document.getElementById('retrySubmitBtn');
    
    processingAlert.style.display = 'block';
    processingAlert.className = 'alert alert-warning';
    statusText.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Sending payment request...';
    submitBtn.disabled = true;
    
    // Send retry request
    fetch('{{ route("admin.fees.retry-mobile-money") }}', {
        method: 'POST',
        body: JSON.stringify({
            fee_id: feeId,
            member_phone: memberPhone,
            member_name: memberName,
            amount: amount,
            description: feeType + ' Payment'
        }),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status
            statusText.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Waiting for member to authorize payment...';
            
            // Start polling for new payment status
            pollRetryPaymentStatus(data.transaction_reference, feeId);
        } else {
            // Show error
            processingAlert.className = 'alert alert-danger';
            statusText.textContent = 'Error: ' + (data.message || 'Failed to retry payment');
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Retry error:', error);
        processingAlert.className = 'alert alert-danger';
        statusText.textContent = 'Network error. Please try again.';
        submitBtn.disabled = false;
    });
}

function pollRetryPaymentStatus(transactionRef, feeId, attempts = 0) {
    const maxAttempts = 120; // Extended to 120 seconds (2 minutes for FlexiPay retries)
    const processingAlert = document.getElementById('retryProcessingAlert');
    const statusText = document.getElementById('retryStatusText');
    
    // Wait 30 seconds before FIRST status check (give user time to receive USSD and enter PIN)
    if (attempts === 0) {
        statusText.innerHTML = `
            <i class="mdi mdi-loading mdi-spin"></i> USSD prompt sent to phone. Waiting for user to enter PIN... (30s)
            <br><small class="text-muted">Please check your phone and enter your Mobile Money PIN when prompted</small>
        `;
        
        // Wait 30 seconds before first check
        setTimeout(() => {
            pollRetryPaymentStatus(transactionRef, feeId, 1);
        }, 30000); // Wait 30 seconds
        return;
    }
    
    if (attempts >= maxAttempts) {
        processingAlert.className = 'alert alert-warning';
        statusText.innerHTML = `
            <i class="mdi mdi-alert"></i> Payment check timed out. The transaction may still be processing.<br>
            <button type="button" class="btn btn-primary btn-sm mt-2" onclick="location.reload()">
                <i class="mdi mdi-refresh"></i> Refresh Status
            </button>
        `;
        return;
    }
    
    fetch(`/admin/fees/check-mm-status/${transactionRef}`, {
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
            statusText.innerHTML = '<i class="mdi mdi-check-circle"></i> Payment received successfully!';
            
            setTimeout(() => {
                location.reload();
            }, 2000);
            
        } else if (data.status === 'failed') {
            // Payment failed again
            processingAlert.className = 'alert alert-danger';
            statusText.textContent = 'Payment failed: ' + (data.message || 'Transaction declined');
            
            setTimeout(() => {
                location.reload();
            }, 3000);
            
        } else {
            // Still pending, continue polling
            const elapsedSeconds = 30 + (attempts - 1) * 5; // 30s initial wait + checks every 5s
            const retriesRemaining = Math.floor((120 - elapsedSeconds) / 30); // FlexiPay retries every ~30s
            
            statusText.innerHTML = `
                <i class="mdi mdi-loading mdi-spin"></i> Waiting for payment authorization... (${elapsedSeconds}s elapsed)
                <br><small class="text-muted">FlexiPay will retry ${retriesRemaining} more time(s) if user cancelled</small>
            `;
            
            setTimeout(() => {
                pollRetryPaymentStatus(transactionRef, feeId, attempts + 1);
            }, 5000); // Check every 5 seconds after initial 30s wait
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        setTimeout(() => {
            pollRetryPaymentStatus(transactionRef, feeId, attempts + 1);
        }, 1000);
    });
}

// Handle Check Status Now button clicks
document.addEventListener('click', function(e) {
    if (e.target.closest('.check-status-btn')) {
        e.preventDefault();
        const button = e.target.closest('.check-status-btn');
        const transactionRef = button.getAttribute('data-transaction-ref');
        const feeId = button.getAttribute('data-fee-id');
        
        // Disable button and show checking
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Checking...';
        
        // Check transaction status immediately
        fetch(`/admin/fees/check-mm-status/${transactionRef}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                // Payment was successful!
                button.className = 'btn btn-sm btn-success';
                button.innerHTML = '<i class="mdi mdi-check-circle"></i> Payment Confirmed!';
                
                // Show success alert
                alert('Payment found and confirmed! The page will refresh to show updated status.');
                
                // Reload page after 2 seconds
                setTimeout(() => {
                    location.reload();
                }, 2000);
                
            } else if (data.status === 'failed') {
                // Payment failed
                button.className = 'btn btn-sm btn-danger';
                button.innerHTML = '<i class="mdi mdi-close-circle"></i> Payment Failed';
                button.disabled = false;
                
                alert('Payment failed: ' + (data.message || 'Transaction was declined'));
                
                // Reload page after 3 seconds
                setTimeout(() => {
                    location.reload();
                }, 3000);
                
            } else {
                // Still pending
                button.innerHTML = originalText;
                button.disabled = false;
                
                alert('Payment is still pending. Please try again in a few moments or contact support if the issue persists.');
            }
        })
        .catch(error => {
            console.error('Status check error:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            alert('Unable to check status. Please try again or refresh the page.');
        });
    }
});

// Auto-fill interest rate when savings product is selected
document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const interest = selectedOption.getAttribute('data-interest');
    
    // Set interest rate
    document.getElementById('interest').value = interest || '';
});

// Validate form before submission
document.getElementById('addSavingsForm').addEventListener('submit', function(e) {
    const productId = document.getElementById('product_id').value;
    
    // Check if product is selected
    if (!productId) {
        e.preventDefault();
        alert('Please select a savings product');
        return false;
    }
});
</script>
@endpush
@endsection