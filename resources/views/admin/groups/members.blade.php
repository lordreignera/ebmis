@extends('layouts.admin')

@section('title', 'Group Members - ' . $group->name)

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Group Members Management</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.groups.index') }}">Groups</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.groups.show', $group->id) }}">{{ $group->name }}</a></li>
                        <li class="breadcrumb-item active">Members</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.groups.show', $group->id) }}" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to Group
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Group Info -->
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6 class="text-muted">Group Name</h6>
                        <h5>{{ $group->name }}</h5>
                    </div>
                    <div class="col-md-2">
                        <h6 class="text-muted">Group Code</h6>
                        <h5>{{ $group->code }}</h5>
                    </div>
                    <div class="col-md-2">
                        <h6 class="text-muted">Current Members</h6>
                        <h5 class="text-primary">{{ $group->members->count() }}</h5>
                    </div>
                    <div class="col-md-2">
                        <h6 class="text-muted">Branch</h6>
                        <h5>{{ $group->branch->name ?? 'N/A' }}</h5>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted">Status</h6>
                        @switch($group->status)
                            @case('active')
                                <span class="badge badge-success badge-pill">Active</span>
                                @break
                            @case('pending')
                                <span class="badge badge-warning badge-pill">Pending</span>
                                @break
                            @case('suspended')
                                <span class="badge badge-danger badge-pill">Suspended</span>
                                @break
                            @default
                                <span class="badge badge-secondary badge-pill">{{ ucfirst($group->status) }}</span>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Current Members -->
<div class="row">
    <div class="col-md-7 grid-margin">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Current Members</h4>
                    <span class="text-muted">{{ $group->members->count() }} members</span>
                </div>
                
                @if($group->members->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Code</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group->members as $member)
                            <tr>
                                <td>
                                    <div>
                                        <span class="font-weight-bold">{{ $member->fname }} {{ $member->lname }}</span>
                                        <br><small class="text-muted">{{ $member->branch->name ?? 'No Branch' }}</small>
                                    </div>
                                </td>
                                <td>{{ $member->code }}</td>
                                <td>{{ $member->contact }}</td>
                                <td>
                                    @if($group->leader_id == $member->id)
                                        <span class="badge badge-primary">Leader</span>
                                    @else
                                        <span class="badge badge-info">Member</span>
                                    @endif
                                </td>
                                <td>
                                    @if($member->pivot && $member->pivot->joined_at)
                                        {{ \Carbon\Carbon::parse($member->pivot->joined_at)->format('M d, Y') }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.members.show', $member->id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if($group->leader_id != $member->id && $group->members->count() > 1)
                                        <form action="{{ route('admin.groups.remove-member', [$group->id, $member->id]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to remove this member from the group?')"
                                                    title="Remove from group">
                                                <i class="mdi mdi-account-minus"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-account-group mdi-48px text-muted"></i>
                    <p class="text-muted mt-2">No members in this group yet.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add New Member -->
    <div class="col-md-5 grid-margin">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Add New Member</h4>
                <p class="text-muted">Add verified members who are not currently in any group.</p>
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($availableMembers->count() > 0)
                <form action="{{ route('admin.groups.add-member', $group->id) }}" method="POST" id="addMemberForm">
                    @csrf
                    <div class="mb-3">
                        <label for="member_id" class="form-label">Select Member</label>
                        <select class="form-select" id="member_id" name="member_id" required>
                            <option value="">Choose a member...</option>
                            @foreach($availableMembers as $member)
                                <option value="{{ $member->id }}">
                                    {{ $member->fname }} {{ $member->lname }} ({{ $member->code }}) - {{ $member->branch->name ?? 'No Branch' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="make_leader" name="make_leader" value="1">
                            <label class="form-check-label" for="make_leader">
                                Make this member the group leader
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            @if($group->leader_id)
                                Current leader will be replaced if checked.
                            @else
                                This group currently has no leader.
                            @endif
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-account-plus"></i> Add Member
                    </button>
                </form>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-account-off mdi-48px text-muted"></i>
                    <p class="text-muted mt-2">No available members to add.</p>
                    <small class="text-muted">All verified members are already assigned to groups.</small>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Member Statistics</h5>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h3 class="text-primary">{{ $group->members->count() }}</h3>
                            <small class="text-muted">Current Members</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h3 class="text-info">{{ $availableMembers->count() }}</h3>
                            <small class="text-muted">Available to Add</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize select2 for better member selection
    $('#member_id').select2({
        placeholder: 'Search and select a member...',
        allowClear: true,
        width: '100%'
    });
    
    // Form validation
    $('#addMemberForm').on('submit', function(e) {
        if (!$('#member_id').val()) {
            e.preventDefault();
            alert('Please select a member to add.');
            return false;
        }
    });
});
</script>
@endsection
@endsection