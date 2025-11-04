@extends('layouts.admin')

@section('title', 'Student Profile')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-account me-2"></i>{{ $student->full_name }}
                    </h2>
                    <p class="text-muted mb-0">{{ $student->student_id }}</p>
                </div>
                <div>
                    <a href="{{ route('school.students.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-arrow-left me-1"></i>Back
                    </a>
                    <a href="{{ route('school.students.edit', $student) }}" class="btn btn-primary">
                        <i class="mdi mdi-pencil me-1"></i>Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-4 mb-4">
            <!-- Student Photo & Basic Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    @if($student->photo_path)
                        <img src="{{ Storage::url($student->photo_path) }}" class="rounded-circle mb-3" width="150" height="150">
                    @else
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                            <span class="text-white h1 mb-0">{{ substr($student->first_name, 0, 1) }}</span>
                        </div>
                    @endif
                    <h4 style="color: #000000;">{{ $student->full_name }}</h4>
                    <p class="text-muted"><code>{{ $student->student_id }}</code></p>
                    <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }} mb-3">
                        {{ ucfirst($student->status) }}
                    </span>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0" style="color: #000000;"><i class="mdi mdi-information me-2"></i>Quick Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="color: #000000;">Class:</th>
                            <td>
                                @if($student->class)
                                    <a href="{{ route('school.classes.show', $student->class) }}">{{ $student->class->class_name }}</a>
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Gender:</th>
                            <td>{{ $student->gender }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Age:</th>
                            <td>{{ $student->age }} years</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">DOB:</th>
                            <td>{{ $student->date_of_birth?->format('d M, Y') }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Nationality:</th>
                            <td>{{ $student->nationality ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Religion:</th>
                            <td>{{ $student->religion ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Admission:</th>
                            <td>{{ $student->admission_date?->format('d M, Y') }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Boarding:</th>
                            <td>{{ $student->boarding_status }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-8">
            <!-- Contact Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-map-marker me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Address:</strong><br>{{ $student->address ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <p><strong style="color: #000000;">District:</strong><br>{{ $student->district ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <p><strong style="color: #000000;">Village:</strong><br>{{ $student->village ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Phone:</strong><br>{{ $student->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Email:</strong><br>{{ $student->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent/Guardian Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-account-supervisor me-2"></i>Parent/Guardian Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Name:</strong><br>{{ $student->parent_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Relationship:</strong><br>{{ $student->relationship ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Phone:</strong><br>{{ $student->parent_phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Email:</strong><br>{{ $student->parent_email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Occupation:</strong><br>{{ $student->parent_occupation ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Address:</strong><br>{{ $student->parent_address ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-phone-alert me-2"></i>Emergency Contact</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Name:</strong><br>{{ $student->emergency_contact_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Phone:</strong><br>{{ $student->emergency_contact_phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-medical-bag me-2"></i>Medical Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Blood Group:</strong><br>{{ $student->blood_group ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Allergies:</strong><br>{{ $student->allergies ?? 'None' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Medical Conditions:</strong><br>{{ $student->medical_conditions ?? 'None' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-school me-2"></i>Academic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Admission Number:</strong><br>{{ $student->admission_number ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Academic Year:</strong><br>{{ $student->academic_year ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-12">
                            <p><strong style="color: #000000;">Previous School:</strong><br>{{ $student->previous_school ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
