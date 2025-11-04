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
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus me-1"></i>Create New Role
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Roles Grid -->
    <div class="row">
        @foreach($roles as $role)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="mdi mdi-shield-check me-2"></i>{{ $role->name }}
                        </h6>
                        <span class="badge bg-light text-dark">{{ $role->users_count }} users</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Permissions ({{ $role->permissions->count() }})</small>
                        <div class="mt-2">
                            @foreach($role->permissions->take(5) as $permission)
                                <span class="badge bg-info me-1 mb-1">{{ $permission->name }}</span>
                            @endforeach
                            @if($role->permissions->count() > 5)
                                <span class="badge bg-secondary">+{{ $role->permissions->count() - 5 }} more</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="mdi mdi-pencil me-1"></i>Edit
                        </a>
                        @if($role->name !== 'Super Administrator')
                        <form action="{{ route('admin.roles.delete', $role->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this role?')">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($roles->isEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
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
    const roleCards = document.querySelectorAll('.col-lg-4.col-md-6');

    function filterRoles() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = filterSelect.value;

        roleCards.forEach(card => {
            const roleName = card.querySelector('.card-header h6').textContent.toLowerCase();
            const permissions = Array.from(card.querySelectorAll('.badge.bg-info')).map(b => b.textContent.toLowerCase()).join(' ');
            const usersCount = parseInt(card.querySelector('.badge.bg-light').textContent);

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
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Show "no results" message if all cards are hidden
        const visibleCards = Array.from(roleCards).filter(card => card.style.display !== 'none');
        let noResultsDiv = document.getElementById('noResultsMessage');
        
        if (visibleCards.length === 0) {
            if (!noResultsDiv) {
                noResultsDiv = document.createElement('div');
                noResultsDiv.id = 'noResultsMessage';
                noResultsDiv.className = 'col-12';
                noResultsDiv.innerHTML = `
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="mdi mdi-magnify fa-3x mb-3" style="color: #000;"></i>
                            <h5 style="color: #000;">No roles found</h5>
                            <p style="color: #333;">Try adjusting your search or filters</p>
                        </div>
                    </div>
                `;
                document.querySelector('.row:has(.col-lg-4)').appendChild(noResultsDiv);
            }
            noResultsDiv.style.display = '';
        } else {
            if (noResultsDiv) {
                noResultsDiv.style.display = 'none';
            }
        }
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