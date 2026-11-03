@extends('layouts.admin')

@section('title', 'Users Management')

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
                                <i class="mdi mdi-account-multiple me-2"></i>Users Management
                            </h2>
                            <p class="text-muted mb-0">Manage system users and their access</p>
                        </div>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus me-1"></i>Add New User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary {{ request('filter') ? '' : 'active' }}">
                                    All Users ({{ $users->total() }})
                                </a>
                                <a href="{{ route('admin.users.index') }}?filter=pending" class="btn btn-outline-warning {{ request('filter') === 'pending' ? 'active' : '' }}">
                                    Pending ({{ App\Models\User::where('status', 'pending')->count() }})
                                </a>
                                <a href="{{ route('admin.users.index') }}?filter=active" class="btn btn-outline-success {{ request('filter') === 'active' ? 'active' : '' }}">
                                    Active ({{ App\Models\User::where('status', 'active')->count() }})
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="d-flex">
                                <input type="hidden" name="filter" value="{{ request('filter') }}">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control me-2" placeholder="Search by name, email, phone...">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="access-control-table-shell users-directory">
                <div class="access-control-table-scroll">
                        <table class="table access-control-table mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>School/Branch</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                    <span class="text-white fw-bold">{{ substr($user->name, 0, 1) }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="table-primary-text">{{ $user->name }}</div>
                                                <div class="table-secondary-text">{{ $user->email }}</div>
                                                @if($user->phone)
                                                    <div class="table-secondary-text">{{ $user->phone }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="access-control-badge {{ $user->user_type === 'super_admin' ? 'red' : ($user->user_type === 'school' ? 'blue' : 'orange') }}">
                                            {{ ucwords(str_replace('_', ' ', $user->user_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @forelse($user->roles as $role)
                                            <span class="access-control-badge dark me-1 mb-1">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-muted small">No roles assigned</span>
                                        @endforelse
                                    </td>
                                    <td>
                                            <span class="access-control-badge {{ $user->status === 'active' ? 'green' : ($user->status === 'pending' ? 'orange' : 'red') }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->school)
                                            <span class="table-secondary-text">{{ $user->school->school_name ?? 'School #' . $user->school_id }}</span>
                                        @elseif($user->branches->isNotEmpty())
                                            @foreach($user->branches as $assignedBranch)
                                                <span class="access-control-badge slate me-1 mb-1">{{ $assignedBranch->name }}</span>
                                            @endforeach
                                        @elseif($user->branch)
                                            <span class="table-secondary-text">{{ $user->branch->name }}</span>
                                        @else
                                            <span class="table-secondary-text">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="table-primary-text" style="font-size: 0.75rem;">{{ $user->created_at->format('M d, Y') }}</div>
                                        <div class="table-secondary-text">{{ $user->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <div class="table-primary-text" style="font-size: 0.75rem;">{{ $user->last_login_at->format('M d, Y H:i') }}</div>
                                            <div class="table-secondary-text">{{ $user->last_login_at->diffForHumans() }}</div>
                                        @else
                                            <span class="table-secondary-text">Never logged in</span>
                                        @endif
                                    </td>
                                    <td class="action-cell">
                                        <div class="access-control-actions">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="access-control-action edit" title="Edit User">
                                                <i class="mdi mdi-pencil"></i><span>Edit</span>
                                            </a>
                                            @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.delete', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="access-control-action delete" onclick="return confirm('Are you sure you want to delete this user?')" title="Delete User">
                                                    <i class="mdi mdi-delete"></i><span>Delete</span>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="mdi mdi-account-multiple fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No users found</h5>
                                        <p class="text-muted mb-4">{{ request('search') ? 'No users match your search criteria' : 'Get started by creating your first user' }}</p>
                                        @if(!request('search'))
                                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                            <i class="mdi mdi-plus me-2"></i>Create First User
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
                
                @if($users->hasPages())
                <div class="access-control-table-footer">
                    {{ $users->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Override Bootstrap table CSS variables */
.table {
    --bs-table-hover-bg: #f8f9fa !important;
    --bs-table-hover-color: #000000 !important;
}

/* Make all text darker/black for better readability */
.card-body h2,
.card-body h5,
.card-body p,
.table td,
.table th,
.fw-bold,
.text-muted,
.small {
    color: #000000 !important;
}

.table thead th {
    color: #000000 !important;
    font-weight: 600 !important;
    background-color: #f8f9fa !important;
}

.table tbody td {
    color: #000000 !important;
}

.avatar .text-white {
    color: #ffffff !important;
}

/* Badge text should remain white for contrast */
.badge {
    font-weight: 500;
}

/* Fix table hover - light gray background instead of black */
.table tbody tr:hover {
    background-color: #f8f9fa !important;
}

.table tbody tr:hover td {
    color: #000000 !important;
}

.table tbody tr:hover .badge {
    opacity: 1 !important;
}

/* Override Bootstrap table-hover with even more specificity */
.table-hover tbody tr:hover {
    background-color: #f8f9fa !important;
    --bs-table-hover-bg: #f8f9fa !important;
    --bs-table-bg: #f8f9fa !important;
}

.table-hover tbody tr:hover > * {
    background-color: #f8f9fa !important;
    color: #000000 !important;
}

.table-hover tbody tr:hover td,
.table-hover tbody tr:hover th {
    background-color: #f8f9fa !important;
    color: #000000 !important;
}

.users-directory .badge,
.users-directory .badge:hover,
.users-directory .badge:focus {
    opacity: 1 !important;
}

.users-directory .badge.bg-secondary {
    background-color: #111827 !important;
    color: #ffffff !important;
}

.users-directory .badge.bg-success {
    background-color: #dcfce7 !important;
    color: #166534 !important;
    border: 1px solid #bbf7d0;
}

.users-directory .badge.bg-warning {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a;
}

.users-directory .badge.bg-danger {
    background-color: #fee2e2 !important;
    color: #991b1b !important;
    border: 1px solid #fecaca;
}

.users-directory .badge.bg-info {
    background-color: #e0f2fe !important;
    color: #075985 !important;
    border: 1px solid #bae6fd;
}

.users-directory .table-hover tbody tr:hover .badge.bg-secondary,
.users-directory .table-hover tbody tr:hover .badge.bg-secondary * {
    color: #ffffff !important;
}
</style>
@endpush
