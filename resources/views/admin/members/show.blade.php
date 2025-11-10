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
                                                <span class="badge {{ $member->member_type == 1 ? 'bg-info' : 'bg-primary' }}">
                                                    {{ $member->member_type == 1 ? 'Group Member' : 'Individual' }}
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
                                                    <span class="badge {{ $fee->status == 1 ? 'bg-success' : 'bg-warning' }}">
                                                        {{ $fee->status == 1 ? 'Paid' : 'Pending' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary receipt-btn" data-fee-id="{{ $fee->id }}">
                                                        <i class="mdi mdi-receipt"></i> Receipt
                                                    </button>
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
                                    <a href="{{ route('admin.savings.create', ['member_id' => $member->id]) }}" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-plus"></i> Add Savings
                                    </a>
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
                            <a href="{{ route('admin.savings.create', ['member_id' => $member->id]) }}" class="btn btn-success w-100">
                                <i class="mdi mdi-piggy-bank"></i> Add Savings
                            </a>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3"
                                  placeholder="Enter approval notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"
                                  placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Member</button>
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
            <form action="{{ route('admin.fees.store') }}" method="POST">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
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
                        <input type="number" name="amount" id="amount" class="form-control" min="1" required>
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
                    <div class="mb-3">
                        <label for="description" class="form-label" style="color: #000;">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" 
                                  placeholder="Additional notes about this payment"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
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

@endpush
@endsection