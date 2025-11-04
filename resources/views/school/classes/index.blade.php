@extends('layouts.admin')

@section('title', 'Manage Classes')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-google-classroom me-2"></i>Manage Classes
                    </h2>
                    <p class="text-muted mb-0">Organize and manage your school classes</p>
                </div>
                <div>
                    <a href="{{ route('school.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                    <a href="{{ route('school.classes.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i>Add New Class
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

    <!-- Classes List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0" style="color: #000000;">
                <i class="mdi mdi-view-list me-2"></i>All Classes ({{ $classes->total() }})
            </h5>
        </div>
        <div class="card-body">
            @if($classes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="color: #000000;">Class Name</th>
                                <th style="color: #000000;">Code</th>
                                <th style="color: #000000;">Level</th>
                                <th style="color: #000000;">Class Teacher</th>
                                <th style="color: #000000;">Enrollment</th>
                                <th style="color: #000000;">Status</th>
                                <th style="color: #000000;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-gradient rounded p-2 me-3">
                                                <i class="mdi mdi-google-classroom text-white mdi-18px"></i>
                                            </div>
                                            <div>
                                                <strong style="color: #000000;">{{ $class->class_name }}</strong>
                                                @if($class->stream)
                                                    <br><small class="text-muted">Stream: {{ $class->stream }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><code>{{ $class->class_code }}</code></td>
                                    <td>
                                        <span class="badge bg-info">{{ $class->level }}</span>
                                    </td>
                                    <td>
                                        @if($class->classTeacher)
                                            <div>
                                                <strong style="color: #000000;">{{ $class->classTeacher->full_name }}</strong>
                                                <br><small class="text-muted">{{ $class->classTeacher->staff_id }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                @php
                                                    $percentage = $class->capacity > 0 ? ($class->students_count / $class->capacity * 100) : 0;
                                                    $colorClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $colorClass }}" role="progressbar" style="width: {{ $percentage }}%">
                                                    {{ $class->students_count }}/{{ $class->capacity }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($class->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif($class->status === 'inactive')
                                            <span class="badge bg-secondary">Inactive</span>
                                        @else
                                            <span class="badge bg-danger">Archived</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('school.classes.show', $class) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('school.classes.edit', $class) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" 
                                                    onclick="confirmDelete({{ $class->id }})" 
                                                    @if($class->students_count > 0) disabled @endif>
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $class->id }}" 
                                              action="{{ route('school.classes.destroy', $class) }}" 
                                              method="POST" 
                                              class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $classes->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="mdi mdi-google-classroom mdi-48px text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No classes created yet</h5>
                    <p class="text-muted">Start by creating your first class</p>
                    <a href="{{ route('school.classes.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i>Create First Class
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(classId) {
    if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
        document.getElementById('delete-form-' + classId).submit();
    }
}
</script>
@endpush
@endsection
