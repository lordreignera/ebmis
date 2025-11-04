@extends('layouts.admin')

@section('title', 'Class Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-google-classroom me-2"></i>{{ $class->class_name }}
                    </h2>
                    <p class="text-muted mb-0">Class Details and Student List</p>
                </div>
                <div>
                    <a href="{{ route('school.classes.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Classes
                    </a>
                    <a href="{{ route('school.classes.edit', $class) }}" class="btn btn-primary">
                        <i class="mdi mdi-pencil me-1"></i>Edit Class
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Class Information Card -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="mdi mdi-information me-2"></i>Class Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="color: #000000;">Class Code:</th>
                                <td><code>{{ $class->class_code ?? 'N/A' }}</code></td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Level:</th>
                                <td><span class="badge bg-info">{{ $class->level }}</span></td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Stream:</th>
                                <td>{{ $class->stream ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Capacity:</th>
                                <td>
                                    <strong style="color: #000000;">{{ $class->current_enrollment }}/{{ $class->capacity }}</strong>
                                    <div class="progress mt-1" style="height: 10px;">
                                        @php
                                            $percentage = $class->capacity > 0 ? ($class->current_enrollment / $class->capacity * 100) : 0;
                                            $colorClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                        @endphp
                                        <div class="progress-bar {{ $colorClass }}" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Available Slots:</th>
                                <td>{{ $class->available_slots }}</td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Academic Year:</th>
                                <td>{{ $class->academic_year ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Status:</th>
                                <td>
                                    @if($class->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif($class->status === 'inactive')
                                        <span class="badge bg-secondary">Inactive</span>
                                    @else
                                        <span class="badge bg-danger">Archived</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Class Teacher Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="mdi mdi-account-tie me-2"></i>Class Teacher</h5>
                </div>
                <div class="card-body">
                    @if($class->classTeacher)
                        <div class="text-center mb-3">
                            @if($class->classTeacher->id_photo_path)
                                <img src="{{ Storage::url($class->classTeacher->id_photo_path) }}" 
                                     class="rounded-circle mb-2" 
                                     width="80" 
                                     height="80">
                            @else
                                <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 80px; height: 80px;">
                                    <span class="text-white h3 mb-0">{{ substr($class->classTeacher->first_name, 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <h6 class="text-center mb-1" style="color: #000000;">{{ $class->classTeacher->full_name }}</h6>
                        <p class="text-center text-muted small mb-2">{{ $class->classTeacher->staff_id }}</p>
                        <table class="table table-sm">
                            <tr>
                                <th style="color: #000000;">Position:</th>
                                <td>{{ $class->classTeacher->position }}</td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Email:</th>
                                <td><small>{{ $class->classTeacher->email ?? 'N/A' }}</small></td>
                            </tr>
                            <tr>
                                <th style="color: #000000;">Phone:</th>
                                <td>{{ $class->classTeacher->phone_number }}</td>
                            </tr>
                        </table>
                        <a href="{{ route('school.staff.show', $class->classTeacher) }}" class="btn btn-sm btn-outline-success w-100">
                            View Full Profile
                        </a>
                    @else
                        <p class="text-muted text-center mb-3">No teacher assigned to this class</p>
                        <a href="{{ route('school.classes.edit', $class) }}" class="btn btn-sm btn-primary w-100">
                            Assign Teacher
                        </a>
                    @endif
                </div>
            </div>

            @if($class->description)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0" style="color: #000000;"><i class="mdi mdi-text me-2"></i>Description</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $class->description }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Students List -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: #000000;">
                            <i class="mdi mdi-account-multiple me-2"></i>Students ({{ $students->total() }})
                        </h5>
                        <a href="{{ route('school.students.create') }}?class_id={{ $class->id }}" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-plus me-1"></i>Add Student
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="color: #000000;">Student ID</th>
                                        <th style="color: #000000;">Name</th>
                                        <th style="color: #000000;">Gender</th>
                                        <th style="color: #000000;">Status</th>
                                        <th style="color: #000000;" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        <tr>
                                            <td><code>{{ $student->student_id }}</code></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($student->photo_path)
                                                        <img src="{{ Storage::url($student->photo_path) }}" 
                                                             class="rounded-circle me-2" 
                                                             width="32" 
                                                             height="32">
                                                    @else
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 32px; height: 32px;">
                                                            <span class="text-white small">{{ substr($student->first_name, 0, 1) }}</span>
                                                        </div>
                                                    @endif
                                                    <strong style="color: #000000;">{{ $student->full_name }}</strong>
                                                </div>
                                            </td>
                                            <td>{{ $student->gender }}</td>
                                            <td>
                                                <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($student->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('school.students.show', $student) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <a href="{{ route('school.students.edit', $student) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $students->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="mdi mdi-account-off mdi-48px text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No students in this class yet</h5>
                            <a href="{{ route('school.students.create') }}?class_id={{ $class->id }}" class="btn btn-primary mt-3">
                                <i class="mdi mdi-plus me-1"></i>Add First Student
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
