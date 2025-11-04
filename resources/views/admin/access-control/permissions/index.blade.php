@extends('layouts.admin')

@section('title', 'Permissions Management')

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
                                <i class="mdi mdi-key-variant me-2"></i>Permissions Management
                            </h2>
                            <p class="text-muted mb-0">Manage system permissions and access controls</p>
                        </div>
                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus me-1"></i>Create New Permission
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
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="mdi mdi-magnify"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="searchPermissions" 
                                    placeholder="Search permissions by name..."
                                >
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-secondary w-100" onclick="clearSearch()">
                                <i class="mdi mdi-filter-off me-1"></i>Clear Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions by Category -->
    @if($permissions->isEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="mdi mdi-key-variant fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No permissions found</h5>
                        <p class="text-muted mb-4">Get started by creating your first permission</p>
                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus me-2"></i>Create First Permission
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        @foreach($permissions as $category => $categoryPermissions)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="mdi mdi-folder-key me-2"></i>{{ $category }}
                            </h5>
                            <span class="badge bg-light text-dark">{{ $categoryPermissions->count() }} permissions</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Permission Name</th>
                                        <th>Display Name</th>
                                        <th>Roles Using</th>
                                        <th style="width: 150px;">Created</th>
                                        <th style="width: 100px;" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryPermissions as $index => $permission)
                                    <tr>
                                        <td class="text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <code class="text-primary">{{ $permission->name }}</code>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ ucwords(str_replace(['-', '_'], ' ', $permission->name)) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $rolesCount = $permission->roles()->count();
                                            @endphp
                                            @if($rolesCount > 0)
                                                <span class="badge bg-info">{{ $rolesCount }} {{ Str::plural('role', $rolesCount) }}</span>
                                                <button 
                                                    class="btn btn-sm btn-link p-0 ms-1" 
                                                    type="button"
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#roles-{{ $permission->id }}" 
                                                    aria-expanded="false">
                                                    <i class="mdi mdi-chevron-down"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">No roles</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $permission->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('admin.permissions.delete', $permission->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this permission? This will remove it from all roles.')">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @if($rolesCount > 0)
                                    <tr class="collapse" id="roles-{{ $permission->id }}">
                                        <td colspan="6" class="bg-light">
                                            <div class="p-2">
                                                <small class="text-muted fw-bold">Roles with this permission:</small>
                                                <div class="mt-2">
                                                    @foreach($permission->roles as $role)
                                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="badge bg-primary me-1 text-decoration-none">
                                                            {{ $role->name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @endif

    <!-- Statistics -->
    @if(!$permissions->isEmpty())
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="icon-box bg-primary text-white rounded-circle p-3">
                                <i class="mdi mdi-key-variant mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Permissions</h6>
                            <h4 class="mb-0">{{ $permissions->flatten()->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="icon-box bg-info text-white rounded-circle p-3">
                                <i class="mdi mdi-folder-key mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Categories</h6>
                            <h4 class="mb-0">{{ $permissions->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="icon-box bg-success text-white rounded-circle p-3">
                                <i class="mdi mdi-shield-check mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">In Use</h6>
                            <h4 class="mb-0">{{ $permissions->flatten()->filter(fn($p) => $p->roles()->count() > 0)->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="icon-box bg-warning text-white rounded-circle p-3">
                                <i class="mdi mdi-key-remove mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Unused</h6>
                            <h4 class="mb-0">{{ $permissions->flatten()->filter(fn($p) => $p->roles()->count() == 0)->count() }}</h4>
                        </div>
                    </div>
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

.icon-box {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

code {
    font-size: 0.875rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    background-color: rgba(79, 209, 199, 0.1);
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa !important;
}

.table tbody tr:hover td {
    color: #000000 !important;
}

.table tbody tr:hover .badge {
    opacity: 1 !important;
}

.badge {
    font-weight: 500;
}

/* Make all text darker/black for better readability */
.card-body h5,
.card-body h6,
.card-body p,
.card-body span,
.card-title,
.text-muted,
.table td,
.table th,
.small {
    color: #000000 !important;
}

.table thead th {
    color: #000000 !important;
    font-weight: 600 !important;
}

code {
    color: #000000 !important;
}

.fw-medium {
    color: #000000 !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchPermissions');

    function filterPermissions() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryCards = document.querySelectorAll('.card.border-0.shadow-sm');

        categoryCards.forEach(card => {
            const header = card.querySelector('.card-header');
            if (!header || !header.classList.contains('bg-gradient-primary')) return;

            const rows = card.querySelectorAll('tbody tr:not(.collapse)');
            let visibleCount = 0;

            rows.forEach(row => {
                const permissionName = row.querySelector('code')?.textContent.toLowerCase() || '';
                const displayName = row.querySelector('.fw-medium')?.textContent.toLowerCase() || '';

                if (permissionName.includes(searchTerm) || displayName.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                    // Also hide the collapse row if it exists
                    const collapseRow = row.nextElementSibling;
                    if (collapseRow && collapseRow.classList.contains('collapse')) {
                        collapseRow.style.display = 'none';
                    }
                }
            });

            // Hide the entire category if no permissions match
            const cardContainer = card.closest('.row');
            if (visibleCount === 0) {
                cardContainer.style.display = 'none';
            } else {
                cardContainer.style.display = '';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', filterPermissions);
    }
});

function clearSearch() {
    const searchInput = document.getElementById('searchPermissions');
    searchInput.value = '';
    
    // Show all rows and categories
    document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = '';
    });
    document.querySelectorAll('.row.mb-4').forEach(row => {
        row.style.display = '';
    });
}
</script>
@endpush
