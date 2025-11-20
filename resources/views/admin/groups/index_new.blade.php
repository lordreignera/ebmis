@extends('layouts.admin')

@section('title', 'Groups Management')

@section('content')
<link rel="stylesheet" href="{{ asset('css/modern-tables.css') }}">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-account-group"></i> Groups Management
                    </h3>
                    <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create New Group
                    </a>
                </div>
                
                <div class="card-body">
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

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ number_format($stats['total_groups']) }}</h4>
                                            <small>Total Groups</small>
                                        </div>
                                        <i class="mdi mdi-account-group mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ number_format($stats['verified_groups']) }}</h4>
                                            <small>Verified Groups</small>
                                        </div>
                                        <i class="mdi mdi-check-circle mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ number_format($stats['total_members']) }}</h4>
                                            <small>Total Members</small>
                                        </div>
                                        <i class="mdi mdi-account-multiple mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ number_format($stats['average_group_size'], 1) }}</h4>
                                            <small>Avg Group Size</small>
                                        </div>
                                        <i class="mdi mdi-chart-bar mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" action="{{ route('admin.groups.index') }}" class="row g-3">
                                        <div class="col-md-3">
                                            <select name="verified" class="form-select">
                                                <option value="">All Groups</option>
                                                <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>Verified</option>
                                                <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>Unverified</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="branch_id" class="form-select">
                                                <option value="">All Branches</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" 
                                                       placeholder="Search groups..." value="{{ request('search') }}">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="mdi mdi-magnify"></i>
                                                </button>
                                                <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">
                                                    <i class="mdi mdi-refresh"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Groups Table -->
                    <div class="table-container">
                        <!-- Table Header with Search and Controls -->
                        <div class="table-header">
                            <div class="table-search">
                                <form method="GET" action="{{ route('admin.groups.index') }}" id="tableSearchForm">
                                    @if(request('verified'))
                                        <input type="hidden" name="verified" value="{{ request('verified') }}">
                                    @endif
                                    @if(request('branch_id'))
                                        <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                                    @endif
                                    <input type="text" name="search" placeholder="Search groups..." id="groupSearch" value="{{ request('search') }}">
                                    <button type="submit" style="display: none;"></button>
                                </form>
                            </div>
                            <div class="table-actions">
                                <a href="{{ route('admin.groups.create') }}" class="export-btn">
                                    <i class="mdi mdi-plus"></i>
                                    Create Group
                                </a>
                                <div class="table-show-entries">
                                    Show
                                    <select id="entriesPerPage" onchange="changePerPage(this.value)">
                                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    entries
                                </div>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="table-responsive">
                            <table class="modern-table" id="groupsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Group Name</th>
                                        <th>Branch</th>
                                        <th>Members</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($groups as $group)
                                        <tr data-group-id="{{ $group->id }}">
                                            <td>{{ $group->id }}</td>
                                            <td>
                                                <span class="account-number">{{ $group->code ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $group->name }}</div>
                                                <small class="text-muted">{{ Str::limit($group->address, 40) }}</small>
                                            </td>
                                            <td>{{ $group->branch->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="status-badge status-individual">
                                                    <i class="mdi mdi-account-multiple"></i>
                                                    {{ $group->members_count ?? 0 }} members
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $group->type == 1 ? 'status-approved' : 'status-pending' }}">
                                                    {{ $group->type == 1 ? 'Open' : 'Closed' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($group->verified)
                                                    <span class="status-badge status-verified">
                                                        <i class="mdi mdi-check-circle"></i> Verified
                                                    </span>
                                                @else
                                                    <span class="status-badge status-not-verified">
                                                        <i class="mdi mdi-alert-circle"></i> Unverified
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $dateCreated = $group->created_at ?? $group->datecreated;
                                                @endphp
                                                @if($dateCreated)
                                                    <div class="fw-medium">{{ $dateCreated->format('M d, Y') }}</div>
                                                    <small class="text-muted">{{ $dateCreated->diffForHumans() }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.groups.show', $group->id) }}" 
                                                       class="btn btn-sm btn-view" title="View Group">
                                                        <i class="mdi mdi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.groups.edit', $group->id) }}" 
                                                       class="btn btn-sm btn-warning" title="Edit Group">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('admin.groups.destroy', $group->id) }}" 
                                                          method="POST" style="display: inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this group?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-delete" title="Delete Group">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="mdi mdi-account-group-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">No groups found</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Modern Pagination -->
                        @if($groups->hasPages())
                            <div class="modern-pagination">
                                <div class="pagination-info">
                                    Showing {{ $groups->firstItem() }} to {{ $groups->lastItem() }} of {{ $groups->total() }} groups
                                </div>
                                <div class="pagination-controls">
                                    {{-- Previous Button --}}
                                    @if ($groups->onFirstPage())
                                        <span class="pagination-btn" disabled>
                                            <i class="mdi mdi-chevron-left"></i> Previous
                                        </span>
                                    @else
                                        <a href="{{ $groups->previousPageUrl() }}" class="pagination-btn">
                                            <i class="mdi mdi-chevron-left"></i> Previous
                                        </a>
                                    @endif

                                    {{-- Page Numbers --}}
                                    <div class="pagination-numbers">
                                        @foreach ($groups->getUrlRange(1, $groups->lastPage()) as $page => $url)
                                            @if ($page == $groups->currentPage())
                                                <span class="pagination-btn active">{{ $page }}</span>
                                            @else
                                                <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Next Button --}}
                                    @if ($groups->hasMorePages())
                                        <a href="{{ $groups->nextPageUrl() }}" class="pagination-btn">
                                            Next <i class="mdi mdi-chevron-right"></i>
                                        </a>
                                    @else
                                        <span class="pagination-btn" disabled>
                                            Next <i class="mdi mdi-chevron-right"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}

// Enhanced Table Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Make table search input submit on Enter
    const groupSearchInput = document.getElementById('groupSearch');
    if (groupSearchInput) {
        groupSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('tableSearchForm').submit();
            }
        });
    }
    
    // Add row click functionality
    const tableRows = document.querySelectorAll('#groupsTable tbody tr');
    tableRows.forEach(row => {
        if (!row.querySelector('td[colspan]')) { // Skip empty state row
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Don't trigger row click if clicking on buttons
                if (!e.target.closest('.btn-group') && !e.target.closest('button') && !e.target.closest('a') && !e.target.closest('form')) {
                    const groupId = this.dataset.groupId;
                    if (groupId) {
                        window.location.href = `/admin/groups/${groupId}`;
                    }
                }
            });
        }
    });
});
</script>
@endpush
