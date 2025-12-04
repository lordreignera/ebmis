@extends('layouts.admin')

@section('title', 'Pending Members')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-clock-alert"></i> Pending Members Approval
                    </h3>
                    <div>
                        <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left"></i> Back to All Members
                        </a>
                        <a href="{{ route('admin.members.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus"></i> Add New Member
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Important Notice -->
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Registration Fee Requirement:</strong> Members must pay the registration fee before approval. 
                        Use the "Pay" button or view member details to record the registration fee payment.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

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

                    @if($pendingMembers->isEmpty())
                        <div class="text-center py-5">
                            <i class="mdi mdi-account-check mdi-72px text-success"></i>
                            <h4 class="mt-3 mb-2">All Caught Up!</h4>
                            <p class="text-muted mb-3">There are no pending members waiting for approval.</p>
                            <a href="{{ route('admin.members.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-2"></i>Add New Member
                            </a>
                        </div>
                    @else
                        <!-- Pending Members Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>NIN</th>
                                        <th>Contact</th>
                                        <th>Branch</th>
                                        <th>Member Type</th>
                                        <th>Registration Fee</th>
                                        <th>Date Created</th>
                                        <th>Added By</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingMembers as $member)
                                        <tr id="member-row-{{ $member->id }}">
                                            <td>{{ $member->id }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $member->code ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $member->fname }} {{ $member->lname }}</strong>
                                                @if($member->mname)
                                                    <br><small class="text-muted">{{ $member->mname }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $member->nin }}</td>
                                            <td>
                                                {{ $member->contact }}
                                                @if($member->alt_contact)
                                                    <br><small class="text-muted">{{ $member->alt_contact }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $member->branch->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ $member->member_type == 1 ? 'bg-primary' : 'bg-info' }}">
                                                    {{ $member->member_type == 1 ? 'Individual' : 'Group' }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    // Check if registration fee has been paid
                                                    $registrationFee = \App\Models\FeeType::active()
                                                        ->where(function($q) {
                                                            $q->where('name', 'like', '%registration%')
                                                              ->orWhere('name', 'like', '%Registration%');
                                                        })
                                                        ->first();
                                                    
                                                    $hasPaidRegistration = false;
                                                    if ($registrationFee) {
                                                        $hasPaidRegistration = \App\Models\Fee::where('member_id', $member->id)
                                                            ->where('fees_type_id', $registrationFee->id)
                                                            ->where('status', 1)
                                                            ->exists();
                                                    }
                                                @endphp
                                                
                                                @if($hasPaidRegistration)
                                                    <span class="badge bg-success">
                                                        <i class="mdi mdi-check-circle"></i> Paid
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="mdi mdi-alert-circle"></i> Not Paid
                                                    </span>
                                                    <br>
                                                    <a href="{{ route('admin.members.show', $member) }}#fees" 
                                                       class="btn btn-xs btn-outline-primary mt-1"
                                                       title="Pay Registration Fee">
                                                        <i class="mdi mdi-cash"></i> Pay
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $member->datecreated ? \Carbon\Carbon::parse($member->datecreated)->format('d M Y') : 'N/A' }}</small>
                                            </td>
                                            <td>
                                                @if($member->addedBy)
                                                    <small>{{ $member->addedBy->name }}</small>
                                                @else
                                                    <small class="text-muted">Unknown</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.members.show', $member) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="View Details">
                                                        <i class="mdi mdi-eye"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success {{ !$hasPaidRegistration ? 'disabled' : '' }}" 
                                                            onclick="approveModal({{ $member->id }}, '{{ $member->fname }} {{ $member->lname }}', {{ $hasPaidRegistration ? 'true' : 'false' }})"
                                                            title="{{ $hasPaidRegistration ? 'Approve Member' : 'Registration fee must be paid first' }}"
                                                            {{ !$hasPaidRegistration ? 'disabled' : '' }}>
                                                        <i class="mdi mdi-check"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="rejectModal({{ $member->id }}, '{{ $member->fname }} {{ $member->lname }}')"
                                                            title="Reject Member">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                    <a href="{{ route('admin.members.edit', $member) }}" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Edit Member">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
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
                                Showing {{ $pendingMembers->firstItem() ?? 0 }} to {{ $pendingMembers->lastItem() ?? 0 }} 
                                of {{ $pendingMembers->total() }} pending members
                            </div>
                            <div>
                                {{ $pendingMembers->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Member Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="mdi mdi-check-circle me-2"></i>Approve Member
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve <strong id="approveMemberName"></strong>?</p>
                    
                    <div class="alert alert-warning" id="registrationFeeWarning" style="display: none;">
                        <i class="mdi mdi-alert me-2"></i>
                        <strong>Registration Fee Not Paid!</strong><br>
                        This member has not paid the registration fee. Please record the payment before approval.
                    </div>
                    
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        Once approved, the member will be able to apply for loans and access all member services.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-check me-2"></i>Approve Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Member Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="mdi mdi-close-circle me-2"></i>Reject Member
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong id="rejectMemberName"></strong>?</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">
                            Rejection Reason <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" 
                                  placeholder="Please provide a reason for rejecting this member..." required></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert me-2"></i>
                        This member will not be able to access member services after rejection.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-close me-2"></i>Reject Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function approveModal(memberId, memberName, hasPaidRegistration) {
        document.getElementById('approveMemberName').textContent = memberName;
        document.getElementById('approveForm').action = `/admin/members/${memberId}/approve`;
        
        // Show/hide registration fee warning
        const warning = document.getElementById('registrationFeeWarning');
        if (!hasPaidRegistration) {
            warning.style.display = 'block';
        } else {
            warning.style.display = 'none';
        }
        
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    }

    function rejectModal(memberId, memberName) {
        document.getElementById('rejectMemberName').textContent = memberName;
        document.getElementById('rejectForm').action = `/admin/members/${memberId}/reject`;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    // Hide modal and remove row after successful action
    document.addEventListener('DOMContentLoaded', function() {
        const forms = ['approveForm', 'rejectForm'];
        
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Optional: Add loading state
                    const submitBtn = form.querySelector('[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-2"></i>Processing...';
                });
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .btn-group .btn {
        margin: 0;
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-top-left-radius: 0.25rem;
        border-bottom-left-radius: 0.25rem;
    }
    
    .btn-group .btn:last-child {
        border-top-right-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }
    
    .modal-body .alert {
        margin-bottom: 0;
    }
</style>
@endpush
