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
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">User</th>
                                    <th>Type</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>School/Branch</th>
                                    <th>Created</th>
                                    <th class="pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                    <span class="text-white fw-bold">{{ substr($user->name, 0, 1) }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                                @if($user->phone)
                                                    <div class="text-muted small">{{ $user->phone }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->user_type === 'super_admin' ? 'danger' : ($user->user_type === 'school' ? 'info' : 'warning') }}">
                                            {{ ucwords(str_replace('_', ' ', $user->user_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @forelse($user->roles as $role)
                                            <span class="badge bg-secondary me-1 mb-1">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-muted small">No roles assigned</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->school)
                                            <small class="text-info">{{ $user->school->school_name ?? 'School #' . $user->school_id }}</small>
                                        @elseif($user->branch_id)
                                            <small class="text-warning">Branch #{{ $user->branch_id }}</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $user->created_at->format('M d, Y') }}</small>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $user->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="Edit User">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.delete', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')" title="Delete User">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
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
                </div>
                
                @if($users->hasPages())
                <div class="card-footer bg-white">
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
</style>
@endpush