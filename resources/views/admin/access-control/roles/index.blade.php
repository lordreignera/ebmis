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
                            <tr data-permissions="{{ $role->permissions->pluck('name')->implode(' ') }}">
                                <td>
                                    <div class="table-primary-text"><i class="mdi mdi-shield-check me-1"></i>{{ $role->name }}</div>
                                    <div class="table-secondary-text">Role access profile</div>
                                </td>
                                <td>
                                    <div class="role-permission-summary">
                                        <span class="access-control-badge orange">{{ $role->permissions->count() }} assigned</span>
                                        <button type="button"
                                                class="access-control-action permission-view-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rolePermissionsModal{{ $role->id }}"
                                                aria-label="View permissions assigned to {{ $role->name }}">
                                            <i class="mdi mdi-eye-outline"></i><span>View</span>
                                        </button>
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
                @foreach($roles as $role)
                <div class="modal fade role-permissions-modal" id="rolePermissionsModal{{ $role->id }}" tabindex="-1" aria-labelledby="rolePermissionsModalLabel{{ $role->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title" id="rolePermissionsModalLabel{{ $role->id }}">
                                        <i class="mdi mdi-shield-check-outline me-1"></i>{{ $role->name }} permissions
                                    </h5>
                                    <div class="role-permissions-count">{{ $role->permissions->count() }} permission{{ $role->permissions->count() === 1 ? '' : 's' }} assigned</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if($role->permissions->isEmpty())
                                    <div class="role-permissions-empty">
                                        <i class="mdi mdi-key-off-outline"></i>
                                        <span>No permissions assigned to this role.</span>
                                    </div>
                                @else
                                    <div class="role-permissions-list" role="list" aria-label="Permissions assigned to {{ $role->name }}">
                                        @foreach($role->permissions->sortBy('name') as $permission)
                                            <div class="role-permission-item" role="listitem">
                                                <i class="mdi mdi-check-circle-outline"></i>
                                                <span>{{ $permission->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <a href="{{ route('admin.permissions.index', ['role' => $role->id]) }}" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-key-chain me-1"></i>Manage permissions
                                </a>
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
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

.role-permission-summary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.permission-view-button {
    padding: 0.3rem 0.55rem !important;
    font-size: 0.7rem !important;
}

.role-permissions-modal {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 1rem !important;
    overflow-x: hidden;
    overflow-y: auto;
    z-index: 10060 !important;
}

.role-permissions-modal .modal-dialog {
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 980px !important;
    min-height: calc(100% - 2rem);
    margin: 0 auto !important;
}

.role-permissions-modal .modal-content {
    border: 0;
    border-radius: 0.75rem;
    background: #ffffff !important;
    color: #334155 !important;
    box-shadow: 0 1rem 3rem rgba(15, 23, 42, 0.2);
}

.role-permissions-modal .modal-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.25rem;
}

.role-permissions-modal .modal-title {
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.role-permissions-count {
    color: #64748b;
    font-size: 0.78rem;
    margin-top: 0.15rem;
}

.role-permissions-modal .modal-body {
    max-height: min(60vh, 34rem);
    overflow-y: auto;
    padding: 1.25rem;
    background: #ffffff !important;
}

.role-permissions-modal .modal-footer {
    background: #ffffff !important;
    border-top: 1px solid #e2e8f0;
}

.role-permissions-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 0.55rem;
}

.role-permission-item {
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    min-height: 2.25rem;
    padding: 0.55rem 0.65rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #f8fafc;
    color: #334155;
    font-size: 0.78rem;
    line-height: 1.35;
}

.role-permission-item i {
    flex: 0 0 auto;
    color: #16a34a;
    font-size: 1rem;
    line-height: 1.2;
}

.role-permissions-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 8rem;
    color: #64748b;
}

.role-permissions-empty i {
    font-size: 1.25rem;
}

@media (max-width: 576px) {
    .role-permissions-modal {
        padding: 0.5rem !important;
    }

    .role-permissions-modal .modal-dialog {
        max-width: 100% !important;
        min-height: calc(100% - 1rem);
    }
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
            const permissions = (row.dataset.permissions || row.cells[1]?.textContent || '').toLowerCase();
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

    // Keep permission modals above the fixed admin sidebar and its overlay.
    document.querySelectorAll('.role-permissions-modal').forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function() {
            window.setTimeout(function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                    backdrop.style.zIndex = '10050';
                });
            }, 0);
        });
    });
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
