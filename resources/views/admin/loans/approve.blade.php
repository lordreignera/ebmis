@extends('admin.layout')

@section('title', 'Loan Approval - ' . $loan->code)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-check-circle"></i>
                        Loan Approval - {{ $loan->code }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $loan->verified == '0' ? 'warning' : ($loan->verified == '1' ? 'success' : 'danger') }}">
                            {{ $loan->verified == '0' ? 'Pending' : ($loan->verified == '1' ? 'Approved' : 'Rejected') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Loan Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h5 class="card-title">Loan Details</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Loan Code:</th>
                                            <td>{{ $loan->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>Principal Amount:</th>
                                            <td>UGX {{ number_format($loan->principal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Interest Rate:</th>
                                            <td>{{ $loan->interest }}%</td>
                                        </tr>
                                        <tr>
                                            <th>Period:</th>
                                            <td>{{ $loan->period }} 
                                                {{ $loan->product && $loan->product->period_type == '1' ? 'weeks' : 
                                                   ($loan->product && $loan->product->period_type == '2' ? 'months' : 'days') }}
                                            </td>
                                        </tr>
                                        @if($loan_type === 'personal')
                                        <tr>
                                            <th>Member:</th>
                                            <td>{{ $loan->member ? $loan->member->fname . ' ' . $loan->member->lname : 'N/A' }}</td>
                                        </tr>
                                        @else
                                        <tr>
                                            <th>Group:</th>
                                            <td>{{ $loan->group ? $loan->group->name : 'N/A' }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th>Product:</th>
                                            <td>{{ $loan->product ? $loan->product->name : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Branch:</th>
                                            <td>{{ $loan->branch ? $loan->branch->name : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date Created:</th>
                                            <td>{{ $loan->created_at ? $loan->created_at->format('d-M-Y H:i') : 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Eligibility Check -->
                            <div class="card card-outline card-{{ $approval_summary['eligibility']['eligible'] ? 'success' : 'warning' }}">
                                <div class="card-header">
                                    <h5 class="card-title">Eligibility Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-{{ $approval_summary['eligibility']['eligible'] ? 'success' : 'warning' }}">
                                        <i class="fas fa-{{ $approval_summary['eligibility']['eligible'] ? 'check' : 'exclamation-triangle' }}"></i>
                                        {{ $approval_summary['eligibility']['summary'] }}
                                    </div>
                                    
                                    @foreach($approval_summary['eligibility']['checks'] as $check_name => $check)
                                    <div class="mb-2">
                                        <span class="badge badge-{{ $check['passed'] ? 'success' : 'danger' }}">
                                            <i class="fas fa-{{ $check['passed'] ? 'check' : 'times' }}"></i>
                                        </span>
                                        <strong>{{ ucwords(str_replace('_', ' ', $check_name)) }}:</strong>
                                        {{ $check['message'] }}
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Disbursement Options -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h5 class="card-title">Disbursement Options</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Option 1: Deducted Charges</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Principal Amount:</th>
                                                    <td>UGX {{ number_format($approval_summary['disbursement_options']['deducted_charges']['principal'], 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Total Fees:</th>
                                                    <td>UGX {{ number_format($approval_summary['disbursement_options']['deducted_charges']['total_fees'], 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Fees Deducted:</th>
                                                    <td>UGX {{ number_format($approval_summary['disbursement_options']['deducted_charges']['fees_deducted'], 2) }}</td>
                                                </tr>
                                                <tr class="table-info">
                                                    <th>Disbursement Amount:</th>
                                                    <td><strong>UGX {{ number_format($approval_summary['disbursement_options']['deducted_charges']['disbursement_amount'], 2) }}</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Option 2: Upfront Charges</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Principal Amount:</th>
                                                    <td>UGX {{ number_format($approval_summary['disbursement_options']['upfront_charges']['principal'], 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Total Fees:</th>
                                                    <td>UGX {{ number_format($approval_summary['disbursement_options']['upfront_charges']['total_fees'], 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Fees Deducted:</th>
                                                    <td>UGX {{ number_format($approval_summary['disbursement_options']['upfront_charges']['fees_deducted'], 2) }}</td>
                                                </tr>
                                                <tr class="table-success">
                                                    <th>Disbursement Amount:</th>
                                                    <td><strong>UGX {{ number_format($approval_summary['disbursement_options']['upfront_charges']['disbursement_amount'], 2) }}</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Form -->
                    @if($loan->verified == '0' && $approval_summary['eligibility']['eligible'])
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h5 class="card-title">Approve Loan</h5>
                                </div>
                                <div class="card-body">
                                    <form id="approval-form">
                                        @csrf
                                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                                        <input type="hidden" name="loan_type" value="{{ $loan_type }}">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="charge_type">Charge Type *</label>
                                                    <select class="form-control" name="charge_type" id="charge_type" required>
                                                        <option value="1">Deduct charges from disbursement</option>
                                                        <option value="2">Member pays charges upfront</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="comments">Comments</label>
                                                    <textarea class="form-control" name="comments" id="comments" rows="3" placeholder="Optional approval comments"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-check"></i> Approve Loan
                                                </button>
                                                <button type="button" class="btn btn-danger btn-lg ml-2" data-toggle="modal" data-target="#reject-modal">
                                                    <i class="fas fa-times"></i> Reject Loan
                                                </button>
                                                <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary btn-lg ml-2">
                                                    <i class="fas fa-arrow-left"></i> Back to Loans
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="reject-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Reject Loan</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rejection-form">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                <input type="hidden" name="loan_type" value="{{ $loan_type }}">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_comments">Reason for Rejection *</label>
                        <textarea class="form-control" name="comments" id="rejection_comments" rows="4" required placeholder="Please provide a reason for rejecting this loan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Loan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle approval form
    $('#approval-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.approve") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status) {
                    toastr.success(response.msg || 'Loan approved successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.msg || 'Loan approval failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                toastr.error('An error occurred while processing the approval');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Handle rejection form
    $('#rejection-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.reject") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status) {
                    toastr.success(response.msg || 'Loan rejected successfully!');
                    $('#reject-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.msg || 'Loan rejection failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                toastr.error('An error occurred while processing the rejection');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush