@extends('layouts.admin')

@section('title', 'Edit Group')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Group</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.groups.index') }}">Groups</a></li>
                        <li class="breadcrumb-item active">Edit Group</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-account-group-outline"></i> Edit Group: {{ $group->name }}
                    </h5>
                    <a href="{{ route('admin.groups.show', $group->id) }}" class="btn btn-info btn-sm">
                        <i class="mdi mdi-eye"></i> View Details
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.groups.update', $group->id) }}" method="POST" id="groupEditForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Group Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="{{ old('name', $group->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Group Code</label>
                                    <input type="text" class="form-control" id="code" name="code" 
                                           value="{{ $group->code }}" readonly>
                                    <small class="form-text text-muted">Auto-generated code cannot be changed</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" id="branch_id" name="branch_id" required>
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" 
                                                    {{ (old('branch_id', $group->branch_id) == $branch->id) ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="1" {{ (old('status', $group->status) == '1') ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ (old('status', $group->status) == '0') ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $group->description) }}</textarea>
                        </div>

                        <!-- Meeting Information -->
                        <h6 class="mt-4 mb-3">Meeting Information</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="meeting_frequency" class="form-label">Meeting Frequency (days) <span class="text-danger">*</span></label>
                                    <select class="form-select" id="meeting_frequency" name="meeting_frequency" required>
                                        <option value="">Select Frequency</option>
                                        <option value="7" {{ (old('meeting_frequency', $group->meeting_frequency) == '7') ? 'selected' : '' }}>Weekly (7 days)</option>
                                        <option value="14" {{ (old('meeting_frequency', $group->meeting_frequency) == '14') ? 'selected' : '' }}>Bi-weekly (14 days)</option>
                                        <option value="30" {{ (old('meeting_frequency', $group->meeting_frequency) == '30') ? 'selected' : '' }}>Monthly (30 days)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="meeting_day" class="form-label">Meeting Day <span class="text-danger">*</span></label>
                                    <select class="form-select" id="meeting_day" name="meeting_day" required>
                                        <option value="">Select Day</option>
                                        <option value="1" {{ (old('meeting_day', $group->meeting_day) == '1') ? 'selected' : '' }}>Monday</option>
                                        <option value="2" {{ (old('meeting_day', $group->meeting_day) == '2') ? 'selected' : '' }}>Tuesday</option>
                                        <option value="3" {{ (old('meeting_day', $group->meeting_day) == '3') ? 'selected' : '' }}>Wednesday</option>
                                        <option value="4" {{ (old('meeting_day', $group->meeting_day) == '4') ? 'selected' : '' }}>Thursday</option>
                                        <option value="5" {{ (old('meeting_day', $group->meeting_day) == '5') ? 'selected' : '' }}>Friday</option>
                                        <option value="6" {{ (old('meeting_day', $group->meeting_day) == '6') ? 'selected' : '' }}>Saturday</option>
                                        <option value="7" {{ (old('meeting_day', $group->meeting_day) == '7') ? 'selected' : '' }}>Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="meeting_time" class="form-label">Meeting Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="meeting_time" name="meeting_time" 
                                           value="{{ old('meeting_time', $group->meeting_time) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="meeting_venue" class="form-label">Meeting Venue</label>
                            <input type="text" class="form-control" id="meeting_venue" name="meeting_venue" 
                                   value="{{ old('meeting_venue', $group->meeting_venue) }}" placeholder="Enter meeting venue">
                        </div>

                        <!-- Current Members -->
                        <h6 class="mt-4 mb-3">Current Members ({{ $group->members->count() }}/5)</h6>
                        @if($group->members->count() > 0)
                            <div class="row mb-3">
                                @foreach($group->members as $member)
                                    <div class="col-md-6 mb-2">
                                        <div class="card border-success">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $member->first_name }} {{ $member->last_name }}</strong><br>
                                                        <small class="text-muted">{{ $member->member_id }} | {{ $member->phone }}</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-member" 
                                                            data-member-id="{{ $member->id }}" data-member-name="{{ $member->first_name }} {{ $member->last_name }}">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="mdi mdi-alert"></i> This group has no members yet.
                            </div>
                        @endif

                        <!-- Add New Members -->
                        @if($group->members->count() < 5)
                            <h6 class="mt-4 mb-3">Add New Members</h6>
                            <div class="alert alert-info">
                                <i class="mdi mdi-information"></i>
                                You can add {{ 5 - $group->members->count() }} more member(s) to this group.
                            </div>

                            <div class="mb-3">
                                <label for="new_member_ids" class="form-label">Select Members to Add</label>
                                <select class="form-select" id="new_member_ids" name="new_member_ids[]" multiple size="6">
                                    @foreach($availableMembers as $member)
                                        <option value="{{ $member->id }}">
                                            {{ $member->first_name }} {{ $member->last_name }} 
                                            ({{ $member->member_id }}) - {{ $member->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple members</small>
                            </div>
                        @endif

                        <!-- Hidden field for members to remove -->
                        <input type="hidden" id="remove_member_ids" name="remove_member_ids" value="">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.groups.show', $group->id) }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i> Back to Group Details
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check"></i> Update Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Remove Member Modal -->
<div class="modal fade" id="removeMemberModal" tabindex="-1" aria-labelledby="removeMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeMemberModalLabel">Remove Member from Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove <strong id="memberNameToRemove"></strong> from this group?</p>
                <div class="alert alert-warning">
                    <i class="mdi mdi-alert"></i> This action cannot be undone. The member will be removed from the group immediately.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveMember">Remove Member</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let membersToRemove = [];

    // Handle member removal
    $('.remove-member').on('click', function() {
        let memberId = $(this).data('member-id');
        let memberName = $(this).data('member-name');
        
        $('#memberNameToRemove').text(memberName);
        $('#removeMemberModal').data('member-id', memberId);
        $('#removeMemberModal').modal('show');
    });

    $('#confirmRemoveMember').on('click', function() {
        let memberId = $('#removeMemberModal').data('member-id');
        
        // Add to removal list
        if (!membersToRemove.includes(memberId)) {
            membersToRemove.push(memberId);
        }
        
        // Update hidden field
        $('#remove_member_ids').val(membersToRemove.join(','));
        
        // Hide the member card
        $(`.remove-member[data-member-id="${memberId}"]`).closest('.col-md-6').hide();
        
        $('#removeMemberModal').modal('hide');
        
        // Show success message
        showAlert('Member will be removed when you save the group.', 'warning');
    });

    // Handle new member selection
    $('#new_member_ids').on('change', function() {
        let selectedOptions = $(this).find('option:selected');
        let currentMembers = {{ $group->members->count() }} - membersToRemove.length;
        let newSelections = selectedOptions.length;
        let totalMembers = currentMembers + newSelections;
        
        if (totalMembers > 5) {
            alert('Total members cannot exceed 5. Please adjust your selection.');
            // Deselect the last selected option
            selectedOptions.last().prop('selected', false);
            return;
        }
    });

    // Form validation
    $('#groupEditForm').on('submit', function(e) {
        let currentMembers = {{ $group->members->count() }} - membersToRemove.length;
        let newMembers = $('#new_member_ids').find('option:selected').length;
        let totalMembers = currentMembers + newMembers;
        
        if (totalMembers > 5) {
            e.preventDefault();
            alert('Total members cannot exceed 5. Please adjust your selection.');
            return false;
        }
    });

    function showAlert(message, type = 'info') {
        let alertClass = type === 'warning' ? 'alert-warning' : 'alert-info';
        let alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="mdi mdi-information"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insert after the card header
        $('.card-body').prepend(alertHtml);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush