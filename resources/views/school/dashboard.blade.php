@extends('layouts.admin')

@section('title', 'School Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-view-dashboard me-2"></i>{{ $school->school_name }}
                    </h2>
                    <p class="text-muted mb-0">Welcome to your school management dashboard</p>
                </div>
                <div>
                    <span class="badge bg-success">Approved</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3">
                                <i class="mdi mdi-account-multiple text-white mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Students</h6>
                            <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ $stats['total_students'] }}</h3>
                            <a href="{{ route('school.students.index') }}" class="small text-primary">View All →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3">
                                <i class="mdi mdi-account-tie text-white mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Staff</h6>
                            <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ $stats['total_staff'] }}</h3>
                            <small class="text-muted">{{ $stats['teaching_staff'] }} Teaching | {{ $stats['non_teaching_staff'] }} Non-Teaching</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3">
                                <i class="mdi mdi-google-classroom text-white mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Classes</h6>
                            <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ $stats['total_classes'] }}</h3>
                            <a href="{{ route('school.classes.index') }}" class="small text-info">Manage Classes →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-circle p-3">
                                <i class="mdi mdi-currency-usd text-white mdi-24px"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Staff Payroll</h6>
                            <h3 class="mb-0 font-weight-bold" style="color: #000000;">
                                UGX {{ number_format(\App\Models\Staff::where('school_id', $school->id)->where('status', 'active')->sum('total_salary')) }}
                            </h3>
                            <a href="{{ route('school.staff.index') }}" class="small text-warning">View Staff →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-lightning-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('school.students.create') }}" class="btn btn-primary btn-lg w-100">
                                <i class="mdi mdi-account-plus mdi-24px d-block mb-2"></i>
                                Add New Student
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('school.staff.create') }}" class="btn btn-success btn-lg w-100">
                                <i class="mdi mdi-account-tie-voice mdi-24px d-block mb-2"></i>
                                Add New Staff
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('school.classes.create') }}" class="btn btn-info btn-lg w-100">
                                <i class="mdi mdi-google-classroom mdi-24px d-block mb-2"></i>
                                Create New Class
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" class="btn btn-warning btn-lg w-100" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="mdi mdi-file-excel mdi-24px d-block mb-2"></i>
                                Import Students
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Classes Overview -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-google-classroom me-2"></i>Classes Overview</h5>
                        <a href="{{ route('school.classes.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($classes as $class)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-0" style="color: #000000;">{{ $class->class_name }}</h6>
                                <small class="text-muted">
                                    @if($class->classTeacher)
                                        Teacher: {{ $class->classTeacher->full_name }}
                                    @else
                                        <span class="text-warning">No teacher assigned</span>
                                    @endif
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary">{{ $class->students_count }}/{{ $class->capacity }}</span>
                                <small class="d-block text-muted">students</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-3">No classes created yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Students -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-account-multiple me-2"></i>Recently Added Students</h5>
                        <a href="{{ route('school.students.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($recentStudents as $student)
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="avatar me-3">
                                @if($student->photo_path)
                                    <img src="{{ Storage::url($student->photo_path) }}" class="rounded-circle" width="40" height="40">
                                @else
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <span class="text-white fw-bold">{{ substr($student->first_name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0" style="color: #000000;">{{ $student->full_name }}</h6>
                                <small class="text-muted">
                                    {{ $student->class ? $student->class->class_name : 'No class assigned' }} | 
                                    {{ $student->student_id }}
                                </small>
                            </div>
                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-3">No students added yet.</p>
                    @endforelse
                </div>
            </div>
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
                        <strong>Note:</strong> Download the template first, fill in student details, and upload the completed file.
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('school.students.template') }}" class="btn btn-outline-success btn-sm">
                            <i class="mdi mdi-download me-2"></i>Download Excel Template
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #000000;">Upload Excel File</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Accepted formats: .xlsx, .xls, .csv (Max: 10MB)</small>
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
@endsection
