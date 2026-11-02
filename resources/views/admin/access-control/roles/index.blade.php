@extends('layouts.admin')

@section('title', 'Roles Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h4 mb-1 text-primary">
                                <i class="mdi mdi-shield-account me-2"></i>Roles Management
                            </h2>
                            <p class="text-muted mb-0">Manage system roles and their permissions</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-primary">
                                <i class="mdi mdi-key-chain me-1"></i>Manage Permissions
                            </a>
                            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-1"></i>Create New Role
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-1"></i>{{ session('success') }}
            <a href="{{ route('admin.permissions.index') }}" class="alert-link ms-1">Open Permission Matrix</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search and Filter Bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="mdi mdi-magnify"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="searchRoles" 
                                    placeholder="Search roles by name or permissions..."
                                >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterByUsers">
                                <option value="">All Roles</option>
                                <option value="with-users">With Users</option>
                                <option value="without-users">Without Users</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="mdi mdi-filter-off me-1"></i>Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Table -->
    @if($roles->isEmpty())
    <div class="row">
        <div class="col-12">
            <div class="access-control-table-shell">
                <div class="empty-state text-center">
                    <i class="mdi mdi-shield-account fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No roles found</h5>
                    <p class="text-muted mb-4">Get started by creating your first role</p>
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-2"></i>Create First Role
                    </a>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="access-control-table-shell roles-directory">
                <div class="access-control-table-scroll">
                    <table class="table access-control-table mb-0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th>Users</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td>
                                    <div class="table-primary-text"><i class="mdi mdi-shield-check me-1"></i>{{ $role->name }}</div>
                                    <div class="table-secondary-text">Role access profile</div>
                                </td>
                                <td>
                                    <span class="access-control-badge orange">{{ $role->permissions->count() }} assigned</span>
                                    <div class="mt-2">
                                        @foreach($role->permissions->take(3) as $permission)
                                            <span class="access-control-badge dark me-1 mb-1">{{ $permission->name }}</span>
                                        @endforeach
                                        @if($role->permissions->count() > 3)
                                            <span class="access-control-badge slate">+{{ $role->permissions->count() - 3 }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="access-control-badge blue role-users-count">{{ $role->users_count }} users</span></td>
                                <td class="action-cell">
                                    <div class="access-control-actions">
                                        <a href="{{ route('admin.permissions.index', ['role' => $role->id]) }}" class="access-control-action edit" title="Manage permissions for this role">
                                            <i class="mdi mdi-key-chain"></i><span>Manage permissions</span>
                                        </a>
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="access-control-action">
                                            <i class="mdi mdi-pencil"></i><span>Edit role</span>
                                        </a>
                                        @if($role->name !== 'Super Administrator')
                                        <form action="{{ route('admin.roles.delete', $role->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="access-control-action delete" onclick="return confirm('Are you sure you want to delete this role?')">
                                                <i class="mdi mdi-delete"></i><span>Delete</span>
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
                <div id="noResultsMessage" class="empty-state d-none">
                    <i class="mdi mdi-magnify fa-2x mb-2"></i>
                    <div class="table-primary-text">No roles match your filters</div>
                    <div class="table-secondary-text">Try adjusting the search or user filter.</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Make all text darker/black for better readability */
.card-body h6,
.card-body p,
.card-body span,
.card-title,
.card-text,
.text-muted {
    color: #000000 !important;
}

.badge.bg-light {
    color: #000000 !important;
    background-color: #f8f9fa !important;
    border: 1px solid #dee2e6;
}

.badge.bg-info {
    background-color: #4299e1 !important;
    color: #ffffff !important;
}

.badge.bg-secondary {
    background-color: #718096 !important;
    color: #ffffff !important;
}

.card-body small.text-muted {
    color: #333333 !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchRoles');
    const filterSelect = document.getElementById('filterByUsers');
    const roleRows = document.querySelectorAll('.roles-directory tbody tr');
    const noResultsDiv = document.getElementById('noResultsMessage');

    function filterRoles() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = filterSelect.value;

        roleRows.forEach(row => {
            const roleName = row.cells[0]?.textContent.toLowerCase() || '';
            const permissions = row.cells[1]?.textContent.toLowerCase() || '';
            const usersCount = parseInt(row.querySelector('.role-users-count')?.textContent || '0', 10);

            // Search filter
            const matchesSearch = roleName.includes(searchTerm) || permissions.includes(searchTerm);

            // User count filter
            let matchesUserFilter = true;
            if (filterValue === 'with-users') {
                matchesUserFilter = usersCount > 0;
            } else if (filterValue === 'without-users') {
                matchesUserFilter = usersCount === 0;
            }

            // Show/hide card
            if (matchesSearch && matchesUserFilter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Show "no results" message if all cards are hidden
        const visibleRows = Array.from(roleRows).filter(row => row.style.display !== 'none');
        if (noResultsDiv) noResultsDiv.classList.toggle('d-none', visibleRows.length !== 0);
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', filterRoles);
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', filterRoles);
    }
});

function clearFilters() {
    document.getElementById('searchRoles').value = '';
    document.getElementById('filterByUsers').value = '';
    
    // Trigger filter to show all cards
    const event = new Event('keyup');
    document.getElementById('searchRoles').dispatchEvent(event);
}
</script>
@endpush
