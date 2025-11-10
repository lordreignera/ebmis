@extends('layouts.admin')

@section('title', 'Group Details - ' . $group->name)

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Group Details</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.groups.index') }}">Groups</a></li>
                        <li class="breadcrumb-item active">{{ $group->name }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.groups.edit', $group->id) }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-pencil"></i> Edit Group
                </a>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Group Statistics -->
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Total Members</h4>
                        <h2 class="text-info mb-2">{{ $stats['total_members'] }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-account-multiple icon-lg text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Active Loans</h4>
                        <h2 class="text-warning mb-2">{{ $stats['active_loans'] }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-bank icon-lg text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Total Borrowed</h4>
                        <h2 class="text-success mb-2">{{ number_format($stats['total_disbursed'] ?? 0, 0) }}</h2>
                        <small class="text-muted">UGX</small>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-cash-multiple icon-lg text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Outstanding</h4>
                        <h2 class="text-danger mb-2">{{ number_format($stats['total_disbursed'] ?? 0, 0) }}</h2>
                        <small class="text-muted">UGX</small>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-alert-circle icon-lg text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Group Information -->
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Group Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Group Code:</label>
                            <p class="text-muted">{{ $group->code }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Group Name:</label>
                            <p class="text-muted">{{ $group->name }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Inception Date:</label>
                            <p class="text-muted">{{ $group->inception_date }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status:</label>
                            <p>
                                @if($group->type == 1)
                                    <span class="badge badge-success">Open Group</span>
                                @else
                                    <span class="badge badge-warning">Closed Group</span>
                                @endif
                                
                                @if($group->verified == 1)
                                    <span class="badge badge-primary">Verified</span>
                                @else
                                    <span class="badge badge-secondary">Not Verified</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Branch:</label>
                            <p class="text-muted">{{ $group->branch->name ?? 'Not assigned' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Sector:</label>
                            <p class="text-muted">{{ $group->sector }}</p>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Address:</label>
                            <p class="text-muted">{{ $group->address ?: 'No address provided' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Creation Information -->
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Creation Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Date Created:</label>
                            <p class="text-muted">
                                @if($group->datecreated)
                                    {{ \Carbon\Carbon::parse($group->datecreated)->format('M d, Y g:i A') }}
                                @else
                                    {{ $group->created_at ? $group->created_at->format('M d, Y g:i A') : 'Unknown' }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Added By:</label>
                            <p class="text-muted">{{ $group->addedBy->name ?? 'Unknown' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Group Type:</label>
                            <p class="text-muted">
                                @if($group->type == 1)
                                    Preliminary (Open Group)
                                @else
                                    Incubation (Closed Group)
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Verification Status:</label>
                            <p class="text-muted">
                                @if($group->verified == 1)
                                    <span class="badge badge-success">Verified</span>
                                @else
                                    <span class="badge badge-warning">Pending Verification</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Group Members -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Group Members ({{ $group->total_members }}/{{ \App\Models\Group::MAX_MEMBERS }})</h4>
                        @if($group->canAcceptNewMembers())
                            <small class="text-success">{{ $group->getRemainingSlots() }} slots remaining</small>
                        @else
                            <small class="text-warning">Group is at maximum capacity</small>
                        @endif
                    </div>
                    <div>
                        @if($group->canAcceptNewMembers())
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMemberModal">
                                <i class="mdi mdi-plus"></i> Add Member
                            </button>
                        @endif
                        <button type="button" class="btn btn-info btn-sm" onclick="checkLoanEligibility()">
                            <i class="mdi mdi-check-circle"></i> Check Loan Eligibility
                        </button>
                    </div>
                </div>

                <!-- Loan Eligibility Status -->
                <div id="eligibility-status" class="mb-3" style="display: none;">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                @if($group->members->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Member ID</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Branch</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group->members as $member)
                            <tr>
                                <td>
                                    <span class="font-weight-bold">{{ $member->code }}</span>
                                </td>
                                <td>
                                    <div>
                                        <a href="{{ route('admin.members.show', $member->id) }}" class="text-decoration-none">
                                            {{ $member->fname }} {{ $member->lname }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span>{{ $member->contact }}</span>
                                        @if($member->email)
                                            <br><small class="text-muted">{{ $member->email }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($member->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($member->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($member->status === 'suspended')
                                        <span class="badge badge-danger">Suspended</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($member->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $member->branch->name ?? 'Unknown' }}
                                </td>
                                <td>
                                    {{ $member->created_at->format('M d, Y') }}
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.members.show', $member->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i> View
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmRemoveMember({{ $member->id }}, '{{ $member->fname }} {{ $member->lname }}')">
                                            <i class="mdi mdi-delete"></i> Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-account-multiple-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No members found</h5>
                    <p class="text-muted">This group needs at least {{ \App\Models\Group::MIN_MEMBERS_FOR_LOAN }} approved members for group loans</p>
                    @if($group->canAcceptNewMembers())
                        <button type="button" class="btn btn-primary mt-3" data-toggle="modal" data-target="#addMemberModal">
                            <i class="mdi mdi-plus"></i> Add First Member
                        </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Group Loans -->
@if($group->loans && $group->loans->count() > 0)
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Group Loans ({{ $group->loans->count() }})</h4>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Loan ID</th>
                                <th>Amount</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group->loans as $loan)
                            <tr>
                                <td>
                                    <span class="font-weight-bold">{{ $loan->code }}</span>
                                </td>
                                <td>
                                    <span>UGX {{ number_format($loan->principal) }}</span>
                                </td>
                                <td>
                                    <span>UGX {{ number_format($loan->outstanding_balance ?? 0) }}</span>
                                </td>
                                <td>
                                    @switch($loan->status)
                                        @case('disbursed')
                                            <span class="badge badge-success">Active</span>
                                            @break
                                        @case('pending')
                                            <span class="badge badge-warning">Pending</span>
                                            @break
                                        @case('paid')
                                            <span class="badge badge-info">Paid</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($loan->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($loan->due_date)
                                        <span>{{ \Carbon\Carbon::parse($loan->due_date)->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">Add Member to Group</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addMemberForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="member_id">Select Member</label>
                        <select name="member_id" id="member_id" class="form-control select2" required>
                            <option value="">Choose a member...</option>
                        </select>
                        <small class="form-text text-muted">Only approved members without a group can be selected</small>
                    </div>
                    <div id="memberPreview" class="card mt-3" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title">Member Details</h6>
                            <div id="memberDetails"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Member Modal -->
<div class="modal fade" id="removeMemberModal" tabindex="-1" role="dialog" aria-labelledby="removeMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeMemberModalLabel">Remove Member from Group</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="removeMemberForm">
                @csrf
                @method('DELETE')
                <input type="hidden" id="removeMemberId" name="member_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        <strong>Are you sure?</strong>
                        <p class="mb-0">You are about to remove <strong id="removeMemberName"></strong> from this group. This action cannot be undone.</p>
                    </div>
                    <div id="removeWarnings"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete"></i> Remove Member
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
    // Initialize Select2 for member selection
    $('#member_id').select2({
        placeholder: 'Search for a member...',
        allowClear: true,
        dropdownParent: $('#addMemberModal'),
        ajax: {
            url: '{{ route("admin.groups.available-members", $group->id) }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(member) {
                        return {
                            id: member.id,
                            text: member.fname + ' ' + member.lname + ' (' + member.pm_code + ')',
                            member: member
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Show member preview when selected
    $('#member_id').on('select2:select', function (e) {
        var data = e.params.data;
        if (data.member) {
            showMemberPreview(data.member);
        }
    });

    // Clear member preview when cleared
    $('#member_id').on('select2:clear', function (e) {
        $('#memberPreview').hide();
    });

    // Add member form submission
    $('#addMemberForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Adding...');
        
        $.ajax({
            url: '{{ route("admin.groups.add-member", $group->id) }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#addMemberModal').modal('hide');
                location.reload(); // Refresh the page to show updated data
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors || {};
                var message = xhr.responseJSON.message || 'An error occurred';
                
                showAlert('danger', message);
                
                // Show field-specific errors
                $.each(errors, function(field, messages) {
                    var input = $('[name="' + field + '"]');
                    input.addClass('is-invalid');
                    input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="mdi mdi-plus"></i> Add Member');
            }
        });
    });

    // Remove member form submission
    $('#removeMemberForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Removing...');
        
        $.ajax({
            url: '{{ route("admin.groups.remove-member", $group->id) }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#removeMemberModal').modal('hide');
                location.reload(); // Refresh the page to show updated data
            },
            error: function(xhr) {
                var message = xhr.responseJSON.message || 'An error occurred';
                showAlert('danger', message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="mdi mdi-delete"></i> Remove Member');
            }
        });
    });

    // Reset form when modal is hidden
    $('#addMemberModal').on('hidden.bs.modal', function () {
        $('#addMemberForm')[0].reset();
        $('#member_id').val(null).trigger('change');
        $('#memberPreview').hide();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    });
});

// Show member preview
function showMemberPreview(member) {
    var html = `
        <div class="row">
            <div class="col-md-6">
                <strong>Name:</strong> ${member.fname} ${member.lname}<br>
                <strong>PM Code:</strong> ${member.pm_code}<br>
                <strong>Phone:</strong> ${member.contact || 'N/A'}
            </div>
            <div class="col-md-6">
                <strong>Email:</strong> ${member.email || 'N/A'}<br>
                <strong>Status:</strong> <span class="badge badge-success">Approved</span><br>
                <strong>Type:</strong> ${member.member_type || 'Individual'}
            </div>
        </div>
    `;
    
    $('#memberDetails').html(html);
    $('#memberPreview').show();
}

// Confirm remove member
function confirmRemoveMember(memberId, memberName) {
    $('#removeMemberId').val(memberId);
    $('#removeMemberName').text(memberName);
    
    // Check if removing this member will affect loan eligibility
    var currentCount = {{ $group->members->count() }};
    var minRequired = {{ \App\Models\Group::MIN_MEMBERS_FOR_LOAN }};
    
    if (currentCount - 1 < minRequired) {
        $('#removeWarnings').html(`
            <div class="alert alert-info">
                <i class="mdi mdi-information-outline"></i>
                <strong>Note:</strong> Removing this member will make the group ineligible for group loans 
                (minimum ${minRequired} members required).
            </div>
        `);
    } else {
        $('#removeWarnings').empty();
    }
    
    $('#removeMemberModal').modal('show');
}

// Check loan eligibility
function checkLoanEligibility() {
    $.ajax({
        url: '{{ route("admin.groups.check-eligibility", $group->id) }}',
        method: 'GET',
        success: function(response) {
            var statusClass = response.eligible ? 'success' : 'warning';
            var statusIcon = response.eligible ? 'check-circle' : 'alert-circle';
            
            var html = `
                <div class="alert alert-${statusClass}">
                    <i class="mdi mdi-${statusIcon}"></i>
                    <strong>${response.eligible ? 'Eligible' : 'Not Eligible'}</strong>
                    <p class="mb-0">${response.message}</p>
                </div>
            `;
            
            showAlert(statusClass, response.message);
        },
        error: function(xhr) {
            showAlert('danger', 'Error checking eligibility');
        }
    });
}

// Show alert helper
function showAlert(type, message) {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        $('.alert').first().alert('close');
    }, 5000);
}
</script>
@endpush