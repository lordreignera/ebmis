@extends('layouts.admin')

@section('title', 'Disbursement Details')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Disbursement Details</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.disbursement.index') }}">Disbursements</a></li>
                        <li class="breadcrumb-item active">Disbursement #{{ $disbursement->id }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.disbursement.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to List
                </a>
                @if($disbursement->status == 'pending')
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#approveModal">
                        <i class="mdi mdi-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal">
                        <i class="mdi mdi-close"></i> Reject
                    </button>
                @endif
                @if($disbursement->status == 'approved' && !$disbursement->disbursed_at)
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#disburseModal">
                        <i class="mdi mdi-cash-multiple"></i> Disburse
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Status Alert -->
<div class="row">
    <div class="col-md-12">
        @if($disbursement->status == 'pending')
            <div class="alert alert-warning">
                <i class="mdi mdi-clock-outline"></i> This disbursement is pending approval.
            </div>
        @elseif($disbursement->status == 'approved' && !$disbursement->disbursed_at)
            <div class="alert alert-info">
                <i class="mdi mdi-check-circle"></i> This disbursement has been approved and is ready for disbursement.
            </div>
        @elseif($disbursement->status == 'disbursed')
            <div class="alert alert-success">
                <i class="mdi mdi-cash-multiple"></i> This disbursement has been completed successfully.
            </div>
        @elseif($disbursement->status == 'rejected')
            <div class="alert alert-danger">
                <i class="mdi mdi-close-circle"></i> This disbursement has been rejected.
            </div>
        @endif
    </div>
</div>

<div class="row">
    <!-- Main Disbursement Information -->
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Disbursement Information</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Disbursement ID</label>
                            <p class="font-weight-bold">#{{ $disbursement->id }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Status</label>
                            <p>
                                @if($disbursement->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @elseif($disbursement->status == 'approved')
                                    <span class="badge badge-info">Approved</span>
                                @elseif($disbursement->status == 'disbursed')
                                    <span class="badge badge-success">Disbursed</span>
                                @elseif($disbursement->status == 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Amount</label>
                            <p class="font-weight-bold text-primary">UGX {{ number_format($disbursement->amount, 2) }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Method</label>
                            <p class="text-capitalize">{{ $disbursement->method }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Reference Number</label>
                            <p class="font-weight-bold">{{ $disbursement->reference_number ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Transaction ID</label>
                            <p>{{ $disbursement->transaction_id ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Requested Date</label>
                            <p>{{ $disbursement->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Disbursed Date</label>
                            <p>{{ $disbursement->disbursed_at ? $disbursement->disbursed_at->format('M d, Y h:i A') : 'Not disbursed' }}</p>
                        </div>
                    </div>
                    
                    @if($disbursement->notes)
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="text-muted">Notes</label>
                            <p>{{ $disbursement->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Side Information -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Loan Information</h4>
                
                <div class="form-group">
                    <label class="text-muted">Loan ID</label>
                    <p><a href="{{ route('admin.loans.show', $disbursement->loan->id) }}" class="font-weight-bold">#{{ $disbursement->loan->id }}</a></p>
                </div>
                
                <div class="form-group">
                    <label class="text-muted">Member</label>
                    <p>
                        <a href="{{ route('admin.members.show', $disbursement->loan->member->id) }}">
                            {{ $disbursement->loan->member->fname }} {{ $disbursement->loan->member->lname }}
                        </a>
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="text-muted">Loan Product</label>
                    <p>{{ $disbursement->loan->product->name }}</p>
                </div>
                
                <div class="form-group">
                    <label class="text-muted">Principal Amount</label>
                    <p class="font-weight-bold">UGX {{ number_format($disbursement->loan->principal, 2) }}</p>
                </div>
                
                <div class="form-group">
                    <label class="text-muted">Interest Rate</label>
                    <p>{{ $disbursement->loan->interest_rate }}%</p>
                </div>
                
                <div class="form-group">
                    <label class="text-muted">Loan Term</label>
                    <p>{{ $disbursement->loan->loan_term }} months</p>
                </div>
                
                <div class="form-group">
                    <label class="text-muted">Loan Status</label>
                    <p>
                        <span class="badge badge-{{ $disbursement->loan->status == 'active' ? 'success' : ($disbursement->loan->status == 'pending' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($disbursement->loan->status) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@if($disbursement->status == 'disbursed' && $disbursement->method == 'mobile_money')
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Mobile Money Details</h4>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="text-muted">Phone Number</label>
                            <p class="font-weight-bold">{{ $disbursement->phone_number }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="text-muted">Network</label>
                            <p class="text-uppercase">{{ $disbursement->network }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="text-muted">FlexiPay Response</label>
                            <p>{{ $disbursement->flexipay_response ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="text-muted">Status Code</label>
                            <p>{{ $disbursement->status_code ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Approval History -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Approval History</h4>
                
                @if($disbursement->approved_by || $disbursement->rejected_by)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>By</th>
                                <th>Date</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($disbursement->approved_by)
                            <tr>
                                <td><span class="badge badge-success">Approved</span></td>
                                <td>{{ $disbursement->approvedBy->name }}</td>
                                <td>{{ $disbursement->approved_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $disbursement->approval_comments ?? 'No comments' }}</td>
                            </tr>
                            @endif
                            @if($disbursement->rejected_by)
                            <tr>
                                <td><span class="badge badge-danger">Rejected</span></td>
                                <td>{{ $disbursement->rejectedBy->name }}</td>
                                <td>{{ $disbursement->rejected_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $disbursement->rejection_comments ?? 'No comments' }}</td>
                            </tr>
                            @endif
                            @if($disbursement->disbursed_by)
                            <tr>
                                <td><span class="badge badge-info">Disbursed</span></td>
                                <td>{{ $disbursement->disbursedBy->name }}</td>
                                <td>{{ $disbursement->disbursed_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $disbursement->disbursement_comments ?? 'No comments' }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted">No approval actions taken yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">Approve Disbursement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.disbursement.approve', $disbursement->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to approve this disbursement of <strong>UGX {{ number_format($disbursement->amount, 2) }}</strong>?</p>
                    
                    <div class="form-group">
                        <label for="approval_comments">Comments (Optional)</label>
                        <textarea class="form-control" id="approval_comments" name="approval_comments" rows="3" 
                                  placeholder="Add any comments about this approval"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Disbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Disbursement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.disbursement.reject', $disbursement->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-danger">Are you sure you want to reject this disbursement? This action cannot be undone.</p>
                    
                    <div class="form-group">
                        <label for="rejection_comments" class="required">Reason for Rejection</label>
                        <textarea class="form-control" id="rejection_comments" name="rejection_comments" rows="3" 
                                  placeholder="Provide reason for rejecting this disbursement" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Disbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Disburse Modal -->
<div class="modal fade" id="disburseModal" tabindex="-1" role="dialog" aria-labelledby="disburseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disburseModalLabel">Process Disbursement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.disbursement.process', $disbursement->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="method" class="required">Disbursement Method</label>
                                <select class="form-control" id="method" name="method" required onchange="toggleMethodFields()">
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount" class="required">Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="{{ $disbursement->amount }}" step="0.01" required readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Money Fields -->
                    <div id="mobile_money_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number" class="required">Phone Number</label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" 
                                           value="{{ $disbursement->loan->member->contact }}" placeholder="256700000000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="network" class="required">Network</label>
                                    <select class="form-control" id="network" name="network">
                                        <option value="">Select Network</option>
                                        <option value="mtn">MTN</option>
                                        <option value="airtel">Airtel</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank Transfer Fields -->
                    <div id="bank_transfer_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_name">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_number">Account Number</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="disbursement_comments">Comments</label>
                        <textarea class="form-control" id="disbursement_comments" name="disbursement_comments" rows="3" 
                                  placeholder="Add any comments about this disbursement"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Disbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.required:after {
    content: ' *';
    color: red;
}

.badge {
    font-size: 0.875rem;
}

.card-title {
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 5px;
}

.form-group p {
    margin-bottom: 0;
}
</style>
@endpush

@push('scripts')
<script>
function toggleMethodFields() {
    const method = document.getElementById('method').value;
    
    // Hide all method-specific fields
    document.getElementById('mobile_money_fields').style.display = 'none';
    document.getElementById('bank_transfer_fields').style.display = 'none';
    
    // Show relevant fields
    if (method === 'mobile_money') {
        document.getElementById('mobile_money_fields').style.display = 'block';
        document.getElementById('phone_number').required = true;
        document.getElementById('network').required = true;
    } else if (method === 'bank_transfer') {
        document.getElementById('bank_transfer_fields').style.display = 'block';
    }
    
    // Reset required attributes
    if (method !== 'mobile_money') {
        document.getElementById('phone_number').required = false;
        document.getElementById('network').required = false;
    }
}

$(document).ready(function() {
    // Format phone number
    $('#phone_number').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 0 && !value.startsWith('256')) {
            value = '256' + value;
        }
        $(this).val(value);
    });
});
</script>
@endpush
@endsection