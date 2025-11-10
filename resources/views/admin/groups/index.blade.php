@extends('layouts.admin')

@section('title', 'Groups Management')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Groups Management</h4>
            </div>
            <div>
                <a href="{{ route('admin.groups.create') }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-plus"></i> Create Group
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Total Groups</h4>
                        <h2 class="text-primary mb-2">{{ number_format($stats['total_groups']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-account-group icon-lg text-primary"></i>
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
                        <h4 class="card-title mb-2">Active Groups</h4>
                        <h2 class="text-success mb-2">{{ number_format($stats['active_groups']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-check-circle icon-lg text-success"></i>
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
                        <h4 class="card-title mb-2">Total Members</h4>
                        <h2 class="text-info mb-2">{{ number_format($stats['total_members']) }}</h2>
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
                        <h4 class="card-title mb-2">Avg Group Size</h4>
                        <h2 class="text-warning mb-2">{{ number_format($stats['average_group_size'], 1) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-chart-bar icon-lg text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filter Groups</h4>
                <form method="GET" action="{{ route('admin.groups.index') }}" class="row">
                    <div class="col-md-3">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search groups..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="branch_id" class="form-control">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="start_date" 
                               class="form-control" 
                               placeholder="Start Date"
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="end_date" 
                               class="form-control" 
                               placeholder="End Date"
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Groups Table -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Groups List</h4>
                    <span class="text-muted">{{ $groups->total() }} total groups</span>
                </div>
                
                @if($groups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="groupsTable">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="code">
                                    <i class="mdi mdi-barcode"></i> Group Code
                                </th>
                                <th class="sortable" data-sort="name">
                                    <i class="mdi mdi-account-group"></i> Group Name
                                </th>
                                <th>
                                    <i class="mdi mdi-account-star"></i> Leader
                                </th>
                                <th class="sortable" data-sort="branch">
                                    <i class="mdi mdi-office-building"></i> Branch
                                </th>
                                <th>
                                    <i class="mdi mdi-account-multiple"></i> Members
                                </th>
                                <th>
                                    <i class="mdi mdi-information"></i> Type & Date
                                </th>
                                <th class="sortable" data-sort="status">
                                    <i class="mdi mdi-check-circle"></i> Status
                                </th>
                                <th class="sortable" data-sort="created">
                                    <i class="mdi mdi-calendar"></i> Created
                                </th>
                                <th>
                                    <i class="mdi mdi-cog"></i> Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $group)
                            <tr data-group-id="{{ $group->id }}">
                                <td>
                                    <span class="badge bg-primary">{{ $group->code }}</span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $group->name }}</div>
                                        <small class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            {{ Str::limit($group->address, 50) }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="mdi mdi-account-off me-1"></i>
                                        No Leader System
                                    </span>
                                </td>
                                <td>
                                    @if($group->branch)
                                        <span class="badge bg-light text-dark">
                                            <i class="mdi mdi-office-building me-1"></i>
                                            {{ $group->branch->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="mdi mdi-help-circle me-1"></i>
                                            No Branch
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-info">
                                            <i class="mdi mdi-account-multiple me-1"></i>
                                            {{ $group->members_count ?? 0 }} members
                                        </span>
                                        <small class="text-muted mt-1">
                                            <i class="mdi mdi-factory me-1"></i>
                                            {{ ucfirst($group->sector) }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge {{ $group->type == 1 ? 'bg-success' : 'bg-warning' }}">
                                            <i class="mdi mdi-{{ $group->type == 1 ? 'lock-open' : 'lock' }} me-1"></i>
                                            {{ $group->type == 1 ? 'Open' : 'Closed' }}
                                        </span>
                                        <br><small class="text-muted">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($group->inception_date)->format('M d, Y') }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    @switch($group->status)
                                        @case('active')
                                            <span class="status-indicator status-active"></span>
                                            <span class="badge bg-success">Active</span>
                                            @break
                                        @case('pending')
                                            <span class="status-indicator status-pending"></span>
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case('suspended')
                                            <span class="status-indicator status-rejected"></span>
                                            <span class="badge bg-danger">Suspended</span>
                                            @break
                                        @case('inactive')
                                            <span class="status-indicator status-inactive"></span>
                                            <span class="badge bg-secondary">Inactive</span>
                                            @break
                                        @default
                                            <span class="status-indicator status-inactive"></span>
                                            <span class="badge bg-secondary">{{ ucfirst($group->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $group->created_at->format('M d, Y') }}</span>
                                        <small class="text-muted">
                                            <i class="mdi mdi-clock me-1"></i>
                                            {{ $group->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.groups.show', $group->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Group">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.groups.edit', $group->id) }}" 
                                           class="btn btn-sm btn-outline-warning" title="Edit Group">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <a href="{{ route('admin.groups.members', $group->id) }}" 
                                           class="btn btn-sm btn-outline-info" title="View Members">
                                            <i class="mdi mdi-account-group"></i>
                                        </a>
                                    </div>
                                            </a>
                                            <a class="dropdown-item" href="{{ route('admin.groups.members', $group->id) }}">
                                                <i class="mdi mdi-account-multiple-plus"></i> Add Member
                                            </a>
                                            @if($group->status === 'pending')
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.groups.approve', $group->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success" 
                                                            onclick="return confirm('Are you sure you want to approve this group?')">
                                                        <i class="mdi mdi-check-circle"></i> Approve Group
                                                    </button>
                                                </form>
                                            @elseif($group->status === 'active')
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.groups.suspend', $group->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning" 
                                                            onclick="return confirm('Are you sure you want to suspend this group?')">
                                                        <i class="mdi mdi-pause"></i> Suspend
                                                    </button>
                                                </form>
                                            @elseif($group->status === 'suspended')
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.groups.activate', $group->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="mdi mdi-play"></i> Activate
                                                    </button>
                                                </form>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('admin.groups.destroy', $group->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this group? This action cannot be undone.')">
                                                    <i class="mdi mdi-delete"></i> Delete
                                                </button>
                                            </form>
                                        </div>
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
                        <span class="text-muted">
                            Showing {{ $groups->firstItem() }} to {{ $groups->lastItem() }} of {{ $groups->total() }} results
                        </span>
                    </div>
                    <div>
                        {{ $groups->appends(request()->query())->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-account-group-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No groups found</h5>
                    <p class="text-muted">Start by creating your first group</p>
                    <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create Group
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('select[name="status"], select[name="branch_id"]').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
@endsection