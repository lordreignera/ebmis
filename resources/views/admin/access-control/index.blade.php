@extends('layouts.admin')

@section('title', 'Access Control')

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
                                <i class="mdi mdi-shield-account me-2"></i>Access Control Management
                            </h2>
                            <p class="text-muted mb-0">Manage roles, permissions, and system users</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.users.create') }}" class="btn btn-success">
                                <i class="mdi mdi-plus me-1"></i>Add User
                            </a>
                            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-1"></i>Add Role
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h4 class="mb-0">{{ App\Models\User::count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3">
                                <i class="fas fa-user-tag text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Roles</h6>
                            <h4 class="mb-0">{{ Spatie\Permission\Models\Role::count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3">
                                <i class="fas fa-key text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Permissions</h6>
                            <h4 class="mb-0">{{ Spatie\Permission\Models\Permission::count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-circle p-3">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Users</h6>
                            <h4 class="mb-0">{{ App\Models\User::where('status', 'pending')->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>User Management</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Manage system users, assign roles, and control access.</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>View All Users
                        </a>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New User
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-user-tag me-2"></i>Role Management</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Create and manage user roles with specific permissions.</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-list me-2"></i>View All Roles
                        </a>
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create New Role
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-key me-2"></i>Permission Management</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Define system permissions and access controls.</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-list me-2"></i>View All Permissions
                        </a>
                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-info">
                            <i class="fas fa-plus me-2"></i>Add Permission
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Users</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(App\Models\User::with('roles')->latest()->take(10)->get() as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <span class="text-white fw-bold">{{ substr($user->name, 0, 1) }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->user_type === 'super_admin' ? 'danger' : ($user->user_type === 'school' ? 'info' : 'warning') }}">
                                            {{ ucwords(str_replace('_', ' ', $user->user_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-secondary me-1">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
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
</div>
@endsection

@push('styles')
<style>
/* Make all text darker/black for better readability */
.card-body h2,
.card-body h4,
.card-body h5,
.card-body h6,
.card-body p,
.card-title,
.text-muted,
.fw-bold,
.small,
.table td,
.table th {
    color: #000000 !important;
}

.table thead th {
    color: #000000 !important;
    font-weight: 600 !important;
}

/* Icon backgrounds should keep their colors */
.bg-primary,
.bg-success,
.bg-warning,
.bg-info {
    color: #ffffff !important;
}

.badge {
    font-weight: 500;
}
</style>
@endpush