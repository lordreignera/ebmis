@extends('layouts.admin')

@section('title', 'Manage Students')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-account-multiple me-2"></i>Manage Students
                    </h2>
                    <p class="text-muted mb-0">View and manage all students</p>
                </div>
                <div>
                    <a href="{{ route('school.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-arrow-left me-1"></i>Dashboard
                    </a>
                    <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="mdi mdi-file-excel me-1"></i>Import Excel
                    </button>
                    <a href="{{ route('school.students.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i>Add Student
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
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

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('school.students.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small" style="color: #000000;">Search</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Name, ID, Admission No..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small" style="color: #000000;">Class</label>
                        <select name="class_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small" style="color: #000000;">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                            <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                            <option value="expelled" {{ request('status') == 'expelled' ? 'selected' : '' }}>Expelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-magnify me-1"></i>Filter
                        </button>
                        <a href="{{ route('school.students.index') }}" class="btn btn-light">
                            <i class="mdi mdi-refresh"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Students List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0" style="color: #000000;">
                <i class="mdi mdi-view-list me-2"></i>All Students ({{ $students->total() }})
            </h5>
        </div>
        <div class="card-body">
            @if($students->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="color: #000000;">Photo</th>
                                <th style="color: #000000;">Student ID</th>
                                <th style="color: #000000;">Name</th>
                                <th style="color: #000000;">Class</th>
                                <th style="color: #000000;">Gender</th>
                                <th style="color: #000000;">Parent Contact</th>
                                <th style="color: #000000;">Status</th>
                                <th style="color: #000000;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>
                                        @if($student->photo_path)
                                            <img src="{{ Storage::url($student->photo_path) }}" 
                                                 class="rounded-circle" 
                                                 width="40" 
                                                 height="40">
                                        @else
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ substr($student->first_name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td><code>{{ $student->student_id }}</code></td>
                                    <td>
                                        <strong style="color: #000000;">{{ $student->full_name }}</strong>
                                        @if($student->admission_number)
                                            <br><small class="text-muted">Adm: {{ $student->admission_number }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->class)
                                            <span class="badge bg-info">{{ $student->class->class_name }}</span>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $student->gender }}</td>
                                    <td>
                                        @if($student->parent_name)
                                            <div>
                                                <small><strong>{{ $student->parent_name }}</strong></small>
                                                @if($student->parent_phone)
                                                    <br><small class="text-muted">{{ $student->parent_phone }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'active' => 'success',
                                                'suspended' => 'warning',
                                                'transferred' => 'info',
                                                'graduated' => 'primary',
                                                'expelled' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$student->status] ?? 'secondary' }}">
                                            {{ ucfirst($student->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('school.students.show', $student) }}" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('school.students.edit', $student) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    title="Delete"
                                                    onclick="confirmDelete({{ $student->id }})">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $student->id }}" 
                                              action="{{ route('school.students.destroy', $student) }}" 
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
                    {{ $students->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="mdi mdi-account-off mdi-48px text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No students found</h5>
                    <p class="text-muted">Add your first student or import from Excel</p>
                    <a href="{{ route('school.students.create') }}" class="btn btn-primary me-2">
                        <i class="mdi mdi-plus me-1"></i>Add Student
                    </a>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="mdi mdi-file-excel me-1"></i>Import Excel
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Import Students Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="color: #000000;">Import Students from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('school.students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Instructions:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Download the Excel template</li>
                            <li>Fill in student details (8 required columns)</li>
                            <li>Save and upload the completed file</li>
                        </ol>
                    </div>
                    
                    <div class="mb-3">
                        <a href="{{ route('school.students.template') }}" class="btn btn-outline-success btn-sm w-100">
                            <i class="mdi mdi-download me-2"></i>Download Excel Template
                        </a>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #000000;">Select Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Choose class for imported students</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->class_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">All imported students will be assigned to this class</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #000000;">Upload Excel File <span class="text-danger">*</span></label>
                        <input type="file" 
                               name="excel_file" 
                               class="form-control" 
                               accept=".xlsx,.xls,.csv" 
                               required>
                        <small class="text-muted">Accepted: .xlsx, .xls, .csv (Max: 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-upload me-2"></i>Import Students
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(studentId) {
    if (confirm('Are you sure you want to delete this student? This will also update the class enrollment count.')) {
        document.getElementById('delete-form-' + studentId).submit();
    }
}
</script>
@endpush
@endsection
