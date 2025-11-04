@extends('layouts.admin')

@section('title', 'Manage Staff')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-account-tie me-2"></i>Manage Staff
                    </h2>
                    <p class="text-muted mb-0">View and manage all staff members</p>
                </div>
                <div>
                    <a href="{{ route('school.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-arrow-left me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('school.staff.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i>Add Staff
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, ID, position..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="staff_type" class="form-select">
                            <option value="">All Staff Types</option>
                            <option value="Teaching" {{ request('staff_type') == 'Teaching' ? 'selected' : '' }}>Teaching</option>
                            <option value="Non-Teaching" {{ request('staff_type') == 'Non-Teaching' ? 'selected' : '' }}>Non-Teaching</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0" style="color: #000000;">
                <i class="mdi mdi-view-list me-2"></i>All Staff ({{ $staff->total() }})
            </h5>
        </div>
        <div class="card-body">
            @if($staff->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="color: #000000;">Staff ID</th>
                                <th style="color: #000000;">Name</th>
                                <th style="color: #000000;">Type</th>
                                <th style="color: #000000;">Position</th>
                                <th style="color: #000000;">Contact</th>
                                <th style="color: #000000;">Salary</th>
                                <th style="color: #000000;">Status</th>
                                <th style="color: #000000;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $member)
                                <tr>
                                    <td><code>{{ $member->staff_id }}</code></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($member->id_photo_path)
                                                <img src="{{ Storage::url($member->id_photo_path) }}" class="rounded-circle me-2" width="40" height="40">
                                            @else
                                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                    <span class="text-white fw-bold">{{ substr($member->first_name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <strong style="color: #000000;">{{ $member->full_name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $member->staff_type === 'Teaching' ? 'primary' : 'info' }}">
                                            {{ $member->staff_type }}
                                        </span>
                                    </td>
                                    <td>{{ $member->position }}</td>
                                    <td>
                                        <small>{{ $member->phone_number }}</small>
                                        @if($member->email)
                                            <br><small class="text-muted">{{ $member->email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong style="color: #000000;">UGX {{ number_format($member->total_salary) }}</strong>
                                        <br><small class="text-muted">{{ $member->payment_frequency }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = ['active' => 'success', 'on_leave' => 'warning', 'suspended' => 'danger', 'terminated' => 'secondary'];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$member->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $member->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('school.staff.show', $member) }}" class="btn btn-sm btn-outline-info">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('school.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $member->id }})">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $member->id }}" action="{{ route('school.staff.destroy', $member) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $staff->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="mdi mdi-account-off mdi-48px text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No staff members found</h5>
                    <a href="{{ route('school.staff.create') }}" class="btn btn-primary mt-3">
                        <i class="mdi mdi-plus me-1"></i>Add First Staff Member
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this staff member?')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endpush
@endsection
