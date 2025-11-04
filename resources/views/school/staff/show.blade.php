@extends('layouts.admin')

@section('title', 'Staff Profile')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-account-tie me-2"></i>{{ $staff->full_name }}
                    </h2>
                    <p class="text-muted mb-0">{{ $staff->staff_id }} - {{ $staff->position }}</p>
                </div>
                <div>
                    <a href="{{ route('school.staff.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-arrow-left me-1"></i>Back
                    </a>
                    <a href="{{ route('school.staff.edit', $staff) }}" class="btn btn-primary">
                        <i class="mdi mdi-pencil me-1"></i>Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-4 mb-4">
            <!-- Staff Photo & Basic Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    @if($staff->id_photo_path)
                        <img src="{{ Storage::url($staff->id_photo_path) }}" class="rounded-circle mb-3" width="150" height="150">
                    @else
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                            <span class="text-white h1 mb-0">{{ substr($staff->first_name, 0, 1) }}</span>
                        </div>
                    @endif
                    <h4 style="color: #000000;">{{ $staff->full_name }}</h4>
                    <p class="text-muted"><code>{{ $staff->staff_id }}</code></p>
                    <span class="badge bg-{{ $staff->staff_type === 'Teaching' ? 'primary' : 'info' }} mb-2">
                        {{ $staff->staff_type }} Staff
                    </span>
                    @php
                        $statusColors = ['active' => 'success', 'on_leave' => 'warning', 'suspended' => 'danger', 'terminated' => 'secondary', 'resigned' => 'secondary'];
                    @endphp
                    <br>
                    <span class="badge bg-{{ $statusColors[$staff->status] ?? 'secondary' }}">
                        {{ ucfirst(str_replace('_', ' ', $staff->status)) }}
                    </span>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0" style="color: #000000;"><i class="mdi mdi-information me-2"></i>Quick Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="color: #000000;">Position:</th>
                            <td>{{ $staff->position }}</td>
                        </tr>
                        @if($staff->department)
                        <tr>
                            <th style="color: #000000;">Department:</th>
                            <td>{{ $staff->department }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th style="color: #000000;">Employment:</th>
                            <td>{{ $staff->employment_type }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Date Joined:</th>
                            <td>{{ $staff->date_joined?->format('d M, Y') }}</td>
                        </tr>
                        @if($staff->employee_number)
                        <tr>
                            <th style="color: #000000;">Emp. Number:</th>
                            <td>{{ $staff->employee_number }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Salary Information -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="mdi mdi-cash-multiple me-2"></i>Salary Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-3">
                        <tr>
                            <th style="color: #000000;">Basic Salary:</th>
                            <td class="text-end"><strong>UGX {{ number_format($staff->basic_salary, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Allowances:</th>
                            <td class="text-end"><strong>UGX {{ number_format($staff->allowances, 2) }}</strong></td>
                        </tr>
                        <tr class="border-top">
                            <th style="color: #000000;"><strong>Total Salary:</strong></th>
                            <td class="text-end">
                                <h5 class="mb-0 text-success">UGX {{ number_format($staff->total_salary, 2) }}</h5>
                            </td>
                        </tr>
                    </table>
                    <div class="text-center">
                        <span class="badge bg-secondary">{{ $staff->payment_frequency }}</span>
                    </div>
                    
                    @if($staff->bank_name || $staff->mobile_money_number)
                        <hr>
                        <h6 style="color: #000000;" class="mt-3 mb-2">Payment Details:</h6>
                        @if($staff->bank_name)
                            <small class="d-block"><strong>Bank:</strong> {{ $staff->bank_name }}</small>
                            @if($staff->bank_account_number)
                                <small class="d-block"><strong>Account:</strong> {{ $staff->bank_account_number }}</small>
                            @endif
                        @endif
                        @if($staff->mobile_money_number)
                            <small class="d-block"><strong>Mobile Money:</strong> {{ $staff->mobile_money_number }}</small>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-account me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Gender:</strong><br>{{ $staff->gender }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Date of Birth:</strong><br>{{ $staff->date_of_birth?->format('d M, Y') }}</p>
                        </div>
                        @if($staff->national_id)
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">National ID:</strong><br>{{ $staff->national_id }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Nationality:</strong><br>{{ $staff->nationality ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Phone:</strong><br>{{ $staff->phone_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Email:</strong><br>{{ $staff->email ?? 'N/A' }}</p>
                        </div>
                        @if($staff->address)
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Address:</strong><br>{{ $staff->address }}</p>
                        </div>
                        @endif
                        @if($staff->district)
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">District:</strong><br>{{ $staff->district }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Next of Kin -->
            @if($staff->next_of_kin_name)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-account-supervisor me-2"></i>Next of Kin</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Name:</strong><br>{{ $staff->next_of_kin_name }}</p>
                        </div>
                        @if($staff->next_of_kin_phone)
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Phone:</strong><br>{{ $staff->next_of_kin_phone }}</p>
                        </div>
                        @endif
                        @if($staff->next_of_kin_relationship)
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Relationship:</strong><br>{{ $staff->next_of_kin_relationship }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Employment Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-briefcase me-2"></i>Employment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Staff Type:</strong><br>
                                <span class="badge bg-{{ $staff->staff_type === 'Teaching' ? 'primary' : 'info' }}">
                                    {{ $staff->staff_type }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Position:</strong><br>{{ $staff->position }}</p>
                        </div>
                        @if($staff->department)
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Department:</strong><br>{{ $staff->department }}</p>
                        </div>
                        @endif
                        @if($staff->subjects_taught && $staff->is_teacher)
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Subjects Taught:</strong><br>{{ $staff->subjects_taught }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Employment Type:</strong><br>{{ $staff->employment_type }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong style="color: #000000;">Date Joined:</strong><br>{{ $staff->date_joined?->format('d M, Y') }}</p>
                        </div>
                    </div>

                    @if($staff->is_teacher && $staff->classes->count() > 0)
                        <hr>
                        <h6 style="color: #000000;">Assigned Classes:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($staff->classes as $class)
                                <a href="{{ route('school.classes.show', $class) }}" class="badge bg-primary">
                                    {{ $class->class_name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Qualifications -->
            @if($staff->highest_qualification || $staff->institution_attended)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-school me-2"></i>Qualifications</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($staff->highest_qualification)
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Highest Qualification:</strong><br>{{ $staff->highest_qualification }}</p>
                        </div>
                        @endif
                        @if($staff->institution_attended)
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Institution:</strong><br>{{ $staff->institution_attended }}</p>
                        </div>
                        @endif
                        @if($staff->year_of_graduation)
                        <div class="col-md-4">
                            <p><strong style="color: #000000;">Year of Graduation:</strong><br>{{ $staff->year_of_graduation }}</p>
                        </div>
                        @endif
                        @if($staff->certifications)
                        <div class="col-md-12">
                            <p><strong style="color: #000000;">Additional Certifications:</strong><br>{{ $staff->certifications }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Documents -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #000000;"><i class="mdi mdi-file-document me-2"></i>Documents</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($staff->cv_path)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <i class="mdi mdi-file-pdf mdi-48px text-danger mb-2 d-block"></i>
                                <h6 style="color: #000000;">CV/Resume</h6>
                                <a href="{{ Storage::url($staff->cv_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-download me-1"></i>Download
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($staff->certificate_path)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <i class="mdi mdi-certificate mdi-48px text-success mb-2 d-block"></i>
                                <h6 style="color: #000000;">Certificates</h6>
                                <a href="{{ Storage::url($staff->certificate_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-download me-1"></i>Download
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($staff->id_photo_path)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <img src="{{ Storage::url($staff->id_photo_path) }}" class="img-fluid rounded mb-2" style="max-height: 100px;">
                                <h6 style="color: #000000;">ID Photo</h6>
                                <a href="{{ Storage::url($staff->id_photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-eye me-1"></i>View
                                </a>
                            </div>
                        </div>
                        @endif

                        @if(!$staff->cv_path && !$staff->certificate_path && !$staff->id_photo_path)
                        <div class="col-12">
                            <p class="text-muted text-center">No documents uploaded</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
