<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Comprehensive Assessment - EBIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
        }

        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .assessment-header {
            background: var(--primary-gradient);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .progress-indicator {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            z-index: 100;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 10px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f2f5;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 80px;
            height: 3px;
            background: var(--primary-gradient);
        }

        .section-title i {
            margin-right: 10px;
        }

        .section-description {
            color: #6c757d;
            margin-bottom: 25px;
        }

        .subsection-title {
            color: #495057;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-left: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .required::after {
            content: ' *';
            color: #dc3545;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        .checkbox-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .file-upload-box {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload-box:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .btn-submit {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 15px 50px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .school-info-banner {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="assessment-header no-print">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="fas fa-clipboard-list me-3"></i>School Comprehensive Assessment</h1>
                    <p class="mb-0 opacity-75">Complete this detailed assessment to qualify for loans and financial services</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white bg-opacity-25 rounded p-3">
                        <small class="d-block mb-1">Estimated Time</small>
                        <strong class="fs-5">30-45 minutes</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- School Info Banner -->
        <div class="school-info-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1"><i class="fas fa-school me-2"></i>{{ $school->school_name }}</h5>
                    <p class="mb-0 text-muted">
                        <i class="fas fa-envelope me-2"></i>{{ $school->email }}
                        <span class="mx-2">|</span>
                        <i class="fas fa-map-marker-alt me-2"></i>{{ $school->district }}
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                        <i class="fas fa-clock me-2"></i>Assessment Pending
                    </span>
                </div>
            </div>
        </div>

        <form action="{{ route('school.assessment.store') }}" method="POST" enctype="multipart/form-data" id="assessmentForm">
            @csrf

            <!-- Display Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please correct the following errors:
                    </h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Display Success Message -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Display Info Message -->
            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Display Error Message -->
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-times-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Info Box -->
            <div class="info-box mb-4">
                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>About This Assessment</h6>
                <p class="mb-0">You have successfully completed the basic registration for <strong>{{ $school->school_name }}</strong>. 
                This comprehensive assessment will help us better understand your school's needs and qualify you for loans and financial services. 
                Please complete all sections below.</p>
            </div>

            @php
                // Helper variables for preserving checkbox/array inputs on validation error
                $oldSchoolTypes = old('school_types', []);
                $oldTransportAssets = old('transport_assets', []);
                $oldLearningResources = old('learning_resources', []);
                $oldExpenseCategories = old('expense_categories', []);
                $oldAssetTypes = old('asset_types', []);
                $oldAssetQuantities = old('asset_quantities', []);
                $oldLiabilityTypes = old('liability_types', []);
                $oldLiabilityAmounts = old('liability_amounts', []);
            @endphp

            <!-- Section 1: Extended School Identification -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-id-card"></i>Section 1: Additional School Information
                </h3>
                <p class="section-description">Additional details beyond your basic registration</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_of_establishment" class="form-label">Date of Establishment</label>
                        <input type="date" class="form-control" id="date_of_establishment" name="date_of_establishment" value="{{ old('date_of_establishment', $school->date_of_establishment) }}">
                    </div>
                </div>

                <h6 class="subsection-title">Additional School Types (Check all that apply)</h6>
                <div class="info-box">
                    <small><i class="fas fa-info-circle me-2"></i>You registered as <strong>{{ $school->school_type }}</strong>. Select any additional types if applicable.</small>
                </div>
                <div class="checkbox-group">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Nursery" id="type_nursery" name="school_types[]" 
                                    {{ (count($oldSchoolTypes) > 0 && in_array('Nursery', $oldSchoolTypes)) || (count($oldSchoolTypes) == 0 && $school->school_type == 'Nursery') ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_nursery">Nursery</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Primary" id="type_primary" name="school_types[]" 
                                    {{ (count($oldSchoolTypes) > 0 && in_array('Primary', $oldSchoolTypes)) || (count($oldSchoolTypes) == 0 && $school->school_type == 'Primary') ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_primary">Primary</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Secondary" id="type_secondary" name="school_types[]" 
                                    {{ (count($oldSchoolTypes) > 0 && in_array('Secondary', $oldSchoolTypes)) || (count($oldSchoolTypes) == 0 && $school->school_type == 'Secondary') ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_secondary">Secondary</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Vocational" id="type_vocational" name="school_types[]" 
                                    {{ (count($oldSchoolTypes) > 0 && in_array('Vocational', $oldSchoolTypes)) || (count($oldSchoolTypes) == 0 && $school->school_type == 'Vocational') ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_vocational">Vocational</label>
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Other" id="type_other" name="school_types[]" 
                                    {{ (count($oldSchoolTypes) > 0 && in_array('Other', $oldSchoolTypes)) || (count($oldSchoolTypes) == 0 && $school->school_type == 'Other') ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_other">Other</label>
                                <input type="text" class="form-control mt-2" id="school_type_other" name="school_type_other" placeholder="Specify other type" value="{{ old('school_type_other', $school->school_type_other) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ownership_type_other" class="form-label">Ownership Type Details (If needed)</label>
                        <input type="text" class="form-control" id="ownership_type_other" name="ownership_type_other" placeholder="Additional ownership details" value="{{ old('ownership_type_other', $school->ownership_type_other) }}">
                        <small class="text-muted">Current: {{ $school->ownership }}</small>
                    </div>
                </div>
            </div>

            <!-- Section 2: Extended Location Details -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-map-marked-alt"></i>Section 2: Extended Location Details
                </h3>
                <p class="section-description">Additional location information for your school</p>

                <div class="info-box mb-3">
                    <small><i class="fas fa-map-marker-alt me-2"></i>You registered with: <strong>{{ $school->physical_address }}, {{ $school->district }}</strong></small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="district_select" class="form-label">District <span class="text-danger">*</span></label>
                        <select class="form-select" id="district_select" name="district" required onchange="handleDistrictChange(this.value)">
                            <option value="">Select District</option>
                            <option value="other" {{ old('district') == 'other' ? 'selected' : '' }}>Other (Not in list)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="district_other" name="district_other" placeholder="Enter district name" 
                            value="{{ old('district_other', $school->district_other) }}" 
                            style="display: {{ old('district') == 'other' || $school->district_other ? 'block' : 'none' }};">
                        <small class="text-muted">Current: {{ $school->district }}</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="county" class="form-label">County/Sub-county</label>
                        <select class="form-select" id="county" name="county" onchange="handleSubcountyChange(this.value)">
                            <option value="">Select County/Sub-county</option>
                            <option value="other" {{ old('county') == 'other' ? 'selected' : '' }}>Other (Not in list)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="county_other" name="county_other" placeholder="Enter subcounty name" 
                            value="{{ old('county_other', $school->county_other) }}" 
                            style="display: {{ old('county') == 'other' || $school->county_other ? 'block' : 'none' }};">
                        <small class="text-muted">Current: {{ $school->county }}</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="parish" class="form-label">Parish</label>
                        <select class="form-select" id="parish" name="parish" onchange="handleParishChange(this.value)">
                            <option value="">Select Parish</option>
                            <option value="other" {{ old('parish') == 'other' ? 'selected' : '' }}>Other (Not in list)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="parish_other" name="parish_other" placeholder="Enter parish name" 
                            value="{{ old('parish_other', $school->parish_other) }}" 
                            style="display: {{ old('parish') == 'other' || $school->parish_other ? 'block' : 'none' }};">
                        <small class="text-muted">Current: {{ $school->parish }}</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="village" class="form-label">Village/Zone/Cell</label>
                        <select class="form-select" id="village_select" name="village" onchange="handleVillageChange(this.value)">
                            <option value="">Select Village</option>
                            <option value="other" {{ old('village') == 'other' ? 'selected' : '' }}>Other (Not in list)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="village_other" name="village_other" value="{{ old('village_other', $school->village_other) }}" 
                            placeholder="Enter village name" 
                            style="display: {{ old('village') == 'other' || old('village_other') || $school->village ? 'block' : 'none' }};">
                        <small class="text-muted">Current: {{ $school->village }}</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="gps_coordinates" class="form-label">GPS Coordinates (if available)</label>
                        <input type="text" class="form-control" id="gps_coordinates" name="gps_coordinates" placeholder="e.g., 0.3476° N, 32.5825° E" value="{{ old('gps_coordinates', $school->gps_coordinates) }}">
                        <small class="text-muted">Format: Latitude, Longitude</small>
                    </div>
                </div>
            </div>

            <!-- Section 3: Extended Contact Information -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-address-book"></i>Section 3: Extended Contact Information
                </h3>
                <p class="section-description">Complete contact details for school and administrators</p>

                <div class="info-box mb-3">
                    <small><i class="fas fa-envelope me-2"></i>Registered email: <strong>{{ $school->email }}</strong> | 
                    <i class="fas fa-phone me-2 ms-3"></i>Phone: <strong>{{ $school->phone }}</strong></small>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="school_phone_number" class="form-label">Alternative School Phone</label>
                        <input type="tel" class="form-control" id="school_phone_number" name="school_phone_number" value="{{ old('school_phone_number', $school->school_phone_number) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="school_email_address" class="form-label">School Email (Primary)</label>
                        <input type="email" class="form-control" id="school_email_address" name="school_email_address" value="{{ old('email', $school->email) }}" readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="website" class="form-label">Website (if any)</label>
                        <input type="url" class="form-control" id="website" name="website" placeholder="https://" value="{{ old('website', $school->website) }}">
                    </div>
                </div>

                <h6 class="subsection-title">School Administrator / Head Teacher</h6>
                <div class="info-box mb-3">
                    <small><i class="fas fa-user me-2"></i>Registered contact person: <strong>{{ $school->contact_person }}</strong> 
                    @if($school->contact_position)
                    ({{ $school->contact_position }})
                    @endif
                    </small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="administrator_name" class="form-label required">Name of Head Administrator</label>
                        <input type="text" class="form-control" id="administrator_name" name="administrator_name" required value="{{ old('administrator_name', $school->administrator_name ?? $school->contact_person) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="administrator_contact_number" class="form-label required">Administrator's Contact Number</label>
                        <input type="tel" class="form-control" id="administrator_contact_number" name="administrator_contact_number" required value="{{ old('administrator_contact_number', $school->administrator_contact_number ?? $school->phone) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="administrator_email" class="form-label">Administrator's Email Address</label>
                        <input type="email" class="form-control" id="administrator_email" name="administrator_email" value="{{ old('administrator_email', $school->administrator_email ?? $school->email) }}">
                    </div>
                </div>
            </div>

            <!-- Section 4: Staffing & Enrollment -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-users"></i>Section 4: Staffing & Enrollment
                </h3>
                <p class="section-description">Information about your staff and student population</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="total_teaching_staff" class="form-label">Total Number of Teaching Staff</label>
                        <input type="number" class="form-control" id="total_teaching_staff" name="total_teaching_staff" min="0" value="{{ old('total_teaching_staff', $school->total_teaching_staff) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="total_non_teaching_staff" class="form-label">Total Number of Non-teaching Staff</label>
                        <input type="number" class="form-control" id="total_non_teaching_staff" name="total_non_teaching_staff" min="0" value="{{ old('total_non_teaching_staff', $school->total_non_teaching_staff) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="current_student_enrollment" class="form-label">Current Student Enrollment</label>
                        <input type="number" class="form-control" id="current_student_enrollment" name="current_student_enrollment" min="0" value="{{ old('current_student_enrollment', $school->current_student_enrollment) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="maximum_student_capacity" class="form-label">Maximum Student Capacity</label>
                        <input type="number" class="form-control" id="maximum_student_capacity" name="maximum_student_capacity" min="0" value="{{ old('maximum_student_capacity', $school->maximum_student_capacity) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="average_tuition_fees_per_term" class="form-label">Average Tuition Fees per Term (UGX)</label>
                        <input type="text" class="form-control amount-input" id="average_tuition_fees_per_term" name="average_tuition_fees_per_term" placeholder="e.g., 1,000,000" value="{{ old('average_tuition_fees_per_term', number_format($school->average_tuition_fees_per_term ?? 0)) }}">
                        <small class="text-muted">You can use commas (e.g., 1,000,000)</small>
                    </div>
                </div>

                <h6 class="subsection-title">Other Income Sources</h6>
                <div class="info-box mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    List all other sources of income besides tuition fees (e.g., boarding, uniforms, canteen, transport, etc.)
                </div>
                
                <div id="incomeSourcesContainer">
                    @php
                        // Check for old input first (from validation errors), then fall back to database
                        $oldIncomeSources = old('income_sources', []);
                        $oldIncomeAmounts = old('income_amounts', []);
                        
                        // If no old data, use database data
                        if (empty($oldIncomeSources)) {
                            $incomeSources = $school->other_income_sources ? explode(',', $school->other_income_sources) : [];
                        } else {
                            $incomeSources = $oldIncomeSources;
                        }
                    @endphp
                    
                    @if(count($incomeSources) > 0)
                        @foreach($incomeSources as $index => $source)
                            <div class="income-source-row row mb-2" data-index="{{ $index }}">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="income_sources[]" placeholder="Income source (e.g., Boarding)" value="{{ trim($source) }}">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control amount-input" name="income_amounts[]" placeholder="e.g., 500,000" data-allow-decimal="false" value="{{ isset($oldIncomeAmounts[$index]) ? $oldIncomeAmounts[$index] : '' }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeIncomeSource(this)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="income-source-row row mb-2" data-index="0">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="income_sources[]" placeholder="Income source (e.g., Boarding)">
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control amount-input" name="income_amounts[]" placeholder="e.g., 500,000" data-allow-decimal="false">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeIncomeSource(this)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addIncomeSource()">
                    <i class="fas fa-plus"></i> Add Another Income Source
                </button>
            </div>

            <!-- Section 5: Infrastructure & Facilities -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-building"></i>Section 5: Infrastructure & Facilities
                </h3>
                <p class="section-description">Details about your school's physical infrastructure</p>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="number_of_classrooms" class="form-label">Number of Classrooms</label>
                        <input type="number" class="form-control" id="number_of_classrooms" name="number_of_classrooms" min="0" value="{{ old('number_of_classrooms', $school->number_of_classrooms) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="number_of_dormitories" class="form-label">Number of Dormitories (if applicable)</label>
                        <input type="number" class="form-control" id="number_of_dormitories" name="number_of_dormitories" min="0" value="{{ old('number_of_dormitories', $school->number_of_dormitories) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="number_of_toilets" class="form-label">Number of Toilets / Latrines</label>
                        <input type="number" class="form-control" id="number_of_toilets" name="number_of_toilets" min="0" value="{{ old('number_of_toilets', $school->number_of_toilets) }}">
                    </div>
                </div>

                <h6 class="subsection-title">Utilities & Services</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Electricity Connection</label>
                        <select class="form-select" name="has_electricity" id="has_electricity">
                            <option value="0" {{ old('has_electricity', $school->has_electricity) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('has_electricity', $school->has_electricity) == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="electricity_provider" class="form-label">Electricity Provider</label>
                        <select class="form-select" id="electricity_provider" name="electricity_provider" onchange="handleElectricityProviderChange(this.value)">
                            <option value="">Select electricity provider</option>
                            <option value="UMEME" {{ $school->electricity_provider == 'UMEME' ? 'selected' : '' }}>UMEME</option>
                            <option value="UEDCL" {{ $school->electricity_provider == 'UEDCL' ? 'selected' : '' }}>UEDCL (Uganda Electricity Distribution Company Limited)</option>
                            <option value="Solar" {{ $school->electricity_provider == 'Solar' ? 'selected' : '' }}>Solar Power</option>
                            <option value="Generator" {{ $school->electricity_provider == 'Generator' ? 'selected' : '' }}>Generator</option>
                            <option value="Hybrid" {{ $school->electricity_provider == 'Hybrid' ? 'selected' : '' }}>Hybrid (Solar + Grid/Generator)</option>
                            <option value="Other" {{ ($school->electricity_provider && !in_array($school->electricity_provider, ['UMEME', 'UEDCL', 'Solar', 'Generator', 'Hybrid'])) ? 'selected' : '' }}>Other (Specify)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="electricity_provider_other" name="electricity_provider_other" placeholder="Specify other electricity provider" style="display: {{ ($school->electricity_provider && !in_array($school->electricity_provider, ['UMEME', 'UEDCL', 'Solar', 'Generator', 'Hybrid', ''])) ? 'block' : 'none' }};" value="{{ ($school->electricity_provider && !in_array($school->electricity_provider, ['UMEME', 'UEDCL', 'Solar', 'Generator', 'Hybrid', ''])) ? $school->electricity_provider : '' }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="water_source" class="form-label">Water Source</label>
                        <select class="form-select" id="water_source" name="water_source">
                            <option value="">Select water source</option>
                            <option value="Piped" {{ $school->water_source == 'Piped' ? 'selected' : '' }}>Piped Water</option>
                            <option value="Borehole" {{ $school->water_source == 'Borehole' ? 'selected' : '' }}>Borehole</option>
                            <option value="Rainwater" {{ $school->water_source == 'Rainwater' ? 'selected' : '' }}>Rainwater Harvesting</option>
                            <option value="Well" {{ $school->water_source == 'Well' ? 'selected' : '' }}>Well</option>
                            <option value="Other" {{ $school->water_source == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Internet Access</label>
                        <select class="form-select" name="has_internet_access" id="has_internet_access">
                            <option value="0" {{ old('has_internet_access', $school->has_internet_access) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('has_internet_access', $school->has_internet_access) == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="internet_provider" class="form-label">Internet Provider</label>
                        <select class="form-select" id="internet_provider" name="internet_provider" onchange="handleInternetProviderChange(this.value)">
                            <option value="">Select internet provider</option>
                            <option value="MTN Uganda" {{ $school->internet_provider == 'MTN Uganda' ? 'selected' : '' }}>MTN Uganda</option>
                            <option value="Airtel Uganda" {{ $school->internet_provider == 'Airtel Uganda' ? 'selected' : '' }}>Airtel Uganda</option>
                            <option value="Africell" {{ $school->internet_provider == 'Africell' ? 'selected' : '' }}>Africell</option>
                            <option value="Uganda Telecom" {{ $school->internet_provider == 'Uganda Telecom' ? 'selected' : '' }}>Uganda Telecom</option>
                            <option value="Smile Telecom" {{ $school->internet_provider == 'Smile Telecom' ? 'selected' : '' }}>Smile Telecom</option>
                            <option value="Liquid Telecom" {{ $school->internet_provider == 'Liquid Telecom' ? 'selected' : '' }}>Liquid Telecom</option>
                            <option value="Raxio" {{ $school->internet_provider == 'Raxio' ? 'selected' : '' }}>Raxio</option>
                            <option value="Tangerine" {{ $school->internet_provider == 'Tangerine' ? 'selected' : '' }}>Tangerine</option>
                            <option value="Other" {{ ($school->internet_provider && !in_array($school->internet_provider, ['MTN Uganda', 'Airtel Uganda', 'Africell', 'Uganda Telecom', 'Smile Telecom', 'Liquid Telecom', 'Raxio', 'Tangerine'])) ? 'selected' : '' }}>Other (Specify)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="internet_provider_other" name="internet_provider_other" placeholder="Specify other internet provider" style="display: {{ ($school->internet_provider && !in_array($school->internet_provider, ['MTN Uganda', 'Airtel Uganda', 'Africell', 'Uganda Telecom', 'Smile Telecom', 'Liquid Telecom', 'Raxio', 'Tangerine', ''])) ? 'block' : 'none' }};" value="{{ ($school->internet_provider && !in_array($school->internet_provider, ['MTN Uganda', 'Airtel Uganda', 'Africell', 'Uganda Telecom', 'Smile Telecom', 'Liquid Telecom', 'Raxio', 'Tangerine', ''])) ? $school->internet_provider : '' }}">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Transport Assets (Select all that apply)</label>
                        <div class="checkbox-group">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="School Bus" id="transport_bus" name="transport_assets[]" 
                                            {{ in_array('School Bus', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_bus">School Bus</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Van" id="transport_van" name="transport_assets[]" 
                                            {{ in_array('Van', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_van">Van/Minibus</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Pickup Truck" id="transport_pickup" name="transport_assets[]" 
                                            {{ in_array('Pickup Truck', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_pickup">Pickup Truck</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Car" id="transport_car" name="transport_assets[]" 
                                            {{ in_array('Car', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_car">Car/Sedan</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Motorcycle" id="transport_motorcycle" name="transport_assets[]" 
                                            {{ in_array('Motorcycle', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_motorcycle">Motorcycle/Boda</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Bicycle" id="transport_bicycle" name="transport_assets[]" 
                                            {{ in_array('Bicycle', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_bicycle">Bicycle</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="None" id="transport_none" name="transport_assets[]" 
                                            {{ in_array('None', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_none">None</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Other" id="transport_other_check" name="transport_assets[]" 
                                            {{ in_array('Other', $oldTransportAssets) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="transport_other_check">Other</label>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <input type="text" class="form-control" id="transport_assets_other" name="transport_assets_other" placeholder="Specify other transport assets" style="display: none;" value="{{ old('transport_assets_other', $school->transport_assets_other) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Learning Resources Available (Select all that apply)</label>
                        <div class="checkbox-group">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Library" id="resource_library" name="learning_resources[]" {{ in_array('Library', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_library">Library</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Science Laboratory" id="resource_lab" name="learning_resources[]" {{ in_array('Science Laboratory', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_lab">Science Laboratory</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Computer Lab" id="resource_computer" name="learning_resources[]" {{ in_array('Computer Lab', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_computer">Computer Lab</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Textbooks" id="resource_textbooks" name="learning_resources[]" {{ in_array('Textbooks', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_textbooks">Textbooks</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Sports Equipment" id="resource_sports" name="learning_resources[]" {{ in_array('Sports Equipment', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_sports">Sports Equipment</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Projector/Smart Board" id="resource_projector" name="learning_resources[]" {{ in_array('Projector/Smart Board', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_projector">Projector/Smart Board</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Musical Instruments" id="resource_music" name="learning_resources[]" {{ in_array('Musical Instruments', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_music">Musical Instruments</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Art Supplies" id="resource_art" name="learning_resources[]" {{ in_array('Art Supplies', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_art">Art Supplies</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Playground Equipment" id="resource_playground" name="learning_resources[]" {{ in_array('Playground Equipment', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_playground">Playground Equipment</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Teaching Aids" id="resource_teaching" name="learning_resources[]" {{ in_array('Teaching Aids', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_teaching">Teaching Aids</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="None" id="resource_none" name="learning_resources[]" {{ in_array('None', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_none">None</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Other" id="resource_other_check" name="learning_resources[]" {{ in_array('Other', $oldLearningResources) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource_other_check">Other</label>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <input type="text" class="form-control" id="learning_resources_other" name="learning_resources_other" placeholder="Specify other learning resources" 
                                        value="{{ old('learning_resources_other', $school->learning_resources_other) }}" 
                                        style="display: {{ in_array('Other', $oldLearningResources) ? 'block' : 'none' }};">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Financial Projections & Cash Flow Overview -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i>Section 6: Financial Projections & Cash Flow Overview
                </h3>
                <p class="section-description">Current financial status and cash flow information</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_month_revenue" class="form-label">Total Revenue in 1st Month of Term (UGX)</label>
                        <input type="text" class="form-control amount-input" id="first_month_revenue" name="first_month_revenue" placeholder="e.g., 12,500,000" value="{{ old('first_month_revenue', $school->first_month_revenue) }}" data-allow-decimal="false">
                        <small class="text-muted">Include tuition and other fees</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="last_month_expenditure" class="form-label">Last Month's Total Operating Expenditure (UGX)</label>
                        <input type="text" class="form-control amount-input" id="last_month_expenditure" name="last_month_expenditure" placeholder="e.g., 10,800,000" value="{{ old('last_month_expenditure', number_format($school->last_month_expenditure ?? 0)) }}" data-allow-decimal="false">
                    </div>
                </div>

                <h6 class="subsection-title">Breakdown of Major Expense Categories</h6>
                <div class="info-box">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Select all categories that apply and provide amounts based on last month's expenditure</strong>
                </div>
                
                <div class="checkbox-group">
                    <div class="row" id="expenseCategories">
                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Salaries" id="expense_salaries" name="expense_categories[]" 
                                    {{ in_array('Salaries', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_salaries">Salaries</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input" name="expense_amounts[]" placeholder="e.g., 5,000,000" data-allow-decimal="false" 
                                value="{{ in_array('Salaries', $oldExpenseCategories) ? old('expense_amounts')[array_search('Salaries', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Salaries', $oldExpenseCategories) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Utilities" id="expense_utilities" name="expense_categories[]" 
                                    {{ in_array('Utilities', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_utilities">Utilities</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input" name="expense_amounts[]" placeholder="e.g., 1,500,000" data-allow-decimal="false" 
                                value="{{ in_array('Utilities', $oldExpenseCategories) ? old('expense_amounts')[array_search('Utilities', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Utilities', $oldExpenseCategories) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Maintenance" id="expense_maintenance" name="expense_categories[]" 
                                    {{ in_array('Maintenance', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_maintenance">Maintenance</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input" name="expense_amounts[]" placeholder="e.g., 800,000" data-allow-decimal="false" 
                                value="{{ in_array('Maintenance', $oldExpenseCategories) ? old('expense_amounts')[array_search('Maintenance', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Maintenance', $oldExpenseCategories) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Learning Materials" id="expense_materials" name="expense_categories[]" 
                                    {{ in_array('Learning Materials', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_materials">Learning Materials</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input" name="expense_amounts[]" placeholder="e.g., 600,000" data-allow-decimal="false" 
                                value="{{ in_array('Learning Materials', $oldExpenseCategories) ? old('expense_amounts')[array_search('Learning Materials', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Learning Materials', $oldExpenseCategories) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Meals" id="expense_meals" name="expense_categories[]" 
                                    {{ in_array('Meals', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_meals">Meals</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input" name="expense_amounts[]" placeholder="e.g., 3,000,000" data-allow-decimal="false" 
                                value="{{ in_array('Meals', $oldExpenseCategories) ? old('expense_amounts')[array_search('Meals', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Meals', $oldExpenseCategories) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Transport" id="expense_transport" name="expense_categories[]" 
                                    {{ in_array('Transport', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_transport">Transport</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input" name="expense_amounts[]" placeholder="e.g., 2,000,000" data-allow-decimal="false" 
                                value="{{ in_array('Transport', $oldExpenseCategories) ? old('expense_amounts')[array_search('Transport', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Transport', $oldExpenseCategories) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input expense-category" type="checkbox" value="Other" id="expense_other" name="expense_categories[]" 
                                    {{ in_array('Other', $oldExpenseCategories) ? 'checked' : '' }}>
                                <label class="form-check-label" for="expense_other">Other (Specify)</label>
                            </div>
                            <input type="text" class="form-control expense-amount amount-input mb-2" name="expense_amounts[]" placeholder="e.g., 1,000,000" data-allow-decimal="false" 
                                value="{{ in_array('Other', $oldExpenseCategories) ? old('expense_amounts')[array_search('Other', $oldExpenseCategories)] : '' }}" 
                                {{ in_array('Other', $oldExpenseCategories) ? '' : 'disabled' }}>
                            <input type="text" class="form-control" placeholder="Specify other expense" disabled>
                        </div>
                    </div>
                </div>

                <h6 class="subsection-title mt-4">Student Fees Balance Information</h6>
                <div class="info-box mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Upload an Excel file (.xlsx or .xls) with your students' outstanding fees balance. The file should contain columns: <strong>Student Name, Class, Term Fees, Amount Paid, Balance</strong>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="student_fees_file" class="form-label">Import Students with Fees Balance</label>
                        <input type="file" class="form-control" id="student_fees_file" name="student_fees_file" accept=".xlsx,.xls,.csv" onchange="previewStudentFees(this)">
                        <small class="text-muted">
                            <a href="{{ asset('templates/student_fees_template.csv') }}" class="text-primary" download>
                                <i class="fas fa-download"></i> Download Template (CSV/Excel)
                            </a>
                        </small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Upload Summary</label>
                        <div id="uploadSummary" class="alert alert-info" style="display: none;">
                            <i class="fas fa-file-excel"></i>
                            <span id="summaryText">No file selected</span>
                        </div>
                    </div>
                </div>

                <div id="studentFeesPreview" style="display: none;">
                    <h6 class="subsection-title">Preview of Uploaded Data</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Term Fees (UGX)</th>
                                    <th>Amount Paid (UGX)</th>
                                    <th>Balance (UGX)</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Preview rows will be inserted here -->
                            </tbody>
                        </table>
                        <p class="text-muted"><small>Showing first 10 students. Total will be calculated on submission.</small></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="past_two_terms_shortfall" class="form-label">Tuition Shortfall in Past Two Terms (UGX)</label>
                        <input type="text" class="form-control amount-input" id="past_two_terms_shortfall" name="past_two_terms_shortfall" placeholder="e.g., 8,750,000" value="{{ old('past_two_terms_shortfall', number_format($school->past_two_terms_shortfall ?? 0)) }}" data-allow-decimal="false">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="expected_shortfall_this_term" class="form-label">Expected Fee Collection Shortfall This Term (UGX)</label>
                        <input type="text" class="form-control amount-input" id="expected_shortfall_this_term" name="expected_shortfall_this_term" placeholder="e.g., 4,500,000" value="{{ old('expected_shortfall_this_term', number_format($school->expected_shortfall_this_term ?? 0)) }}" data-allow-decimal="false">
                    </div>

                    <div class="col-12 mb-3">
                        <label for="unpaid_students_list" class="form-label">List of Students Who Did Not Pay Full Tuition Last Term</label>
                        
                        <div class="info-box mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            You can either type the list below or upload an Excel file (.xlsx or .xls) with student details.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="unpaid_students_file" class="form-label">Upload Excel File (Optional)</label>
                                <input type="file" class="form-control" id="unpaid_students_file" name="unpaid_students_file" accept=".xlsx,.xls,.csv" onchange="previewUnpaidStudents(this)">
                                <small class="text-muted">
                                    <a href="{{ asset('templates/unpaid_students_template.csv') }}" class="text-primary" download>
                                        <i class="fas fa-download"></i> Download Template (CSV/Excel)
                                    </a>
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Upload Summary</label>
                                <div id="unpaidUploadSummary" class="alert alert-info" style="display: none;">
                                    <i class="fas fa-file-excel"></i>
                                    <span id="unpaidSummaryText">No file selected</span>
                                </div>
                            </div>
                        </div>

                        <div id="unpaidStudentsPreview" style="display: none;" class="mb-3">
                            <h6 class="subsection-title">Preview of Uploaded Data</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Class/Grade</th>
                                            <th>Expected Fees (UGX)</th>
                                            <th>Amount Paid (UGX)</th>
                                            <th>Balance (UGX)</th>
                                            <th>Payment %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="unpaidPreviewTableBody">
                                        <!-- Preview rows will be inserted here -->
                                    </tbody>
                                </table>
                                <p class="text-muted"><small>Showing first 10 students. Total will be calculated on submission.</small></p>
                            </div>
                        </div>

                        <label for="unpaid_students_list" class="form-label">Or Type Student List Below</label>
                        <textarea class="form-control" id="unpaid_students_list" name="unpaid_students_list" rows="4" placeholder="Example: John K., Grade 6 – Paid 60%; Amina N., Grade 4 – Paid 40%; Peter M., Grade 7 – No payment received.">{{ old('unpaid_students_list', $school->unpaid_students_list) }}</textarea>
                        <small class="text-muted">If you uploaded an Excel file above, this field is optional.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="reserve_funds_status" class="form-label">Reserve Funds or Emergency Financing</label>
                        <select class="form-select" id="reserve_funds_status" name="reserve_funds_status">
                            <option value="">Select option</option>
                            <option value="Sufficient for one term" {{ old('reserve_funds_status', $school->reserve_funds_status) == 'Sufficient for one term' ? 'selected' : '' }}>Yes, sufficient for one term</option>
                            <option value="Limited" {{ old('reserve_funds_status', $school->reserve_funds_status) == 'Limited' ? 'selected' : '' }}>Yes, but limited</option>
                            <option value="No reserves" {{ old('reserve_funds_status', $school->reserve_funds_status) == 'No reserves' ? 'selected' : '' }}>No reserves available</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 7: Financial Performance -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-coins"></i>Section 7: Financial Performance
                </h3>
                <p class="section-description">Overall financial health and banking information</p>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="average_monthly_income" class="form-label">Average Monthly Income (UGX)</label>
                        <input type="text" class="form-control amount-input" id="average_monthly_income" name="average_monthly_income" placeholder="e.g., 15,000,000" value="{{ old('average_monthly_income', number_format($school->average_monthly_income ?? 0)) }}" data-allow-decimal="false">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="average_monthly_expenses" class="form-label">Average Monthly Expenses (UGX)</label>
                        <input type="text" class="form-control amount-input" id="average_monthly_expenses" name="average_monthly_expenses" placeholder="e.g., 12,000,000" value="{{ old('average_monthly_expenses', number_format($school->average_monthly_expenses ?? 0)) }}" data-allow-decimal="false">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="profit_or_surplus" class="form-label">Monthly Profit or Surplus (UGX)</label>
                        <input type="text" class="form-control amount-input" id="profit_or_surplus" name="profit_or_surplus" placeholder="e.g., 3,000,000" value="{{ old('profit_or_surplus', number_format($school->profit_or_surplus ?? 0)) }}" data-allow-decimal="true">
                        <small class="text-muted">Can be negative for losses</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="banking_institutions_used" class="form-label">Banking Institution(s) Used</label>
                        <select class="form-select" id="banking_institutions_used" name="banking_institutions_used" onchange="handleBankChange(this.value)">
                            <option value="">Select banking institution</option>
                            <option value="Centenary Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Centenary Bank' ? 'selected' : '' }}>Centenary Bank</option>
                            <option value="Stanbic Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Stanbic Bank' ? 'selected' : '' }}>Stanbic Bank</option>
                            <option value="DFCU Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'DFCU Bank' ? 'selected' : '' }}>DFCU Bank</option>
                            <option value="Equity Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Equity Bank' ? 'selected' : '' }}>Equity Bank</option>
                            <option value="Standard Chartered Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Standard Chartered Bank' ? 'selected' : '' }}>Standard Chartered Bank</option>
                            <option value="Absa Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Absa Bank' ? 'selected' : '' }}>Absa Bank (formerly Barclays)</option>
                            <option value="Bank of Africa" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Bank of Africa' ? 'selected' : '' }}>Bank of Africa (BOA)</option>
                            <option value="Crane Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Crane Bank' ? 'selected' : '' }}>Crane Bank (dfcu acquired)</option>
                            <option value="Cairo Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Cairo Bank' ? 'selected' : '' }}>Cairo International Bank</option>
                            <option value="Ecobank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Ecobank' ? 'selected' : '' }}>Ecobank Uganda</option>
                            <option value="Finance Trust Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Finance Trust Bank' ? 'selected' : '' }}>Finance Trust Bank</option>
                            <option value="GT Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'GT Bank' ? 'selected' : '' }}>GT Bank Uganda</option>
                            <option value="Housing Finance Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Housing Finance Bank' ? 'selected' : '' }}>Housing Finance Bank</option>
                            <option value="KCB Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'KCB Bank' ? 'selected' : '' }}>KCB Bank Uganda</option>
                            <option value="NC Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'NC Bank' ? 'selected' : '' }}>NC Bank Uganda</option>
                            <option value="Opportunity Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Opportunity Bank' ? 'selected' : '' }}>Opportunity Bank</option>
                            <option value="Orient Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Orient Bank' ? 'selected' : '' }}>Orient Bank</option>
                            <option value="Post Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Post Bank' ? 'selected' : '' }}>Post Bank Uganda</option>
                            <option value="Pride Microfinance" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Pride Microfinance' ? 'selected' : '' }}>Pride Microfinance</option>
                            <option value="Tropical Bank" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Tropical Bank' ? 'selected' : '' }}>Tropical Bank</option>
                            <option value="United Bank for Africa" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'United Bank for Africa' ? 'selected' : '' }}>United Bank for Africa (UBA)</option>
                            <option value="Other" {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Other' ? 'selected' : '' }}>Other (Specify)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="banking_institutions_other" name="banking_institutions_other" placeholder="Specify other banking institution" 
                            value="{{ old('banking_institutions_other', $school->banking_institutions_other) }}" 
                            style="display: {{ old('banking_institutions_used', $school->banking_institutions_used) == 'Other' ? 'block' : 'none' }};">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Does the school have audited financial statements?</label>
                        <select class="form-select" name="has_audited_statements" id="has_audited_statements">
                            <option value="0" {{ $school->has_audited_statements == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $school->has_audited_statements == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 8: Loan Request Details -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-hand-holding-usd"></i>Section 8: Loan Request Details
                </h3>
                <p class="section-description">Information about loan requirements and repayment capacity</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="loan_amount_requested" class="form-label">Loan Amount Requested (UGX)</label>
                        <input type="text" class="form-control amount-input" id="loan_amount_requested" name="loan_amount_requested" value="{{ old('loan_amount_requested', $school->loan_amount_requested) }}" placeholder="e.g., 50,000,000" data-allow-decimal="false">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="preferred_repayment_period" class="form-label">Preferred Repayment Frequency</label>
                        <select class="form-select" name="preferred_repayment_period" id="preferred_repayment_period">
                            <option value="">Select repayment frequency</option>
                            <option value="Daily" {{ old('preferred_repayment_period', $school->preferred_repayment_period) == 'Daily' ? 'selected' : '' }}>Daily</option>
                            <option value="Weekly" {{ old('preferred_repayment_period', $school->preferred_repayment_period) == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="Monthly" {{ old('preferred_repayment_period', $school->preferred_repayment_period) == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                        <small class="text-muted">The repayment amount will be calculated by the system</small>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="loan_purpose" class="form-label">Purpose of Loan</label>
                        <textarea class="form-control" id="loan_purpose" name="loan_purpose" rows="3" placeholder="Describe how the loan will be used (e.g., infrastructure development, equipment purchase, working capital)">{{ old('loan_purpose', $school->loan_purpose) }}</textarea>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Have you received a loan before?</label>
                        <select class="form-select" name="has_received_loan_before" id="has_received_loan_before">
                            <option value="0" {{ $school->has_received_loan_before == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $school->has_received_loan_before == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3" id="previous_loan_details_wrapper" style="display: {{ $school->has_received_loan_before ? 'block' : 'none' }};">
                        <label for="previous_loan_details" class="form-label">Previous Loan Details</label>
                        <textarea class="form-control" id="previous_loan_details" name="previous_loan_details" rows="3" placeholder="Provide details of previous loans (lender, amount, repayment status)">{{ old('previous_loan_details', $school->previous_loan_details) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 9: Supporting Documents -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-file-upload"></i>Section 9: Supporting Documents
                </h3>
                <p class="section-description">Upload relevant documents (PDF, JPG, PNG - Max 5MB each)</p>

                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> Please ensure all documents are clear and legible. Accepted formats: PDF, JPG, JPEG, PNG (Max 5MB)
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">School Registration Certificate</label>
                        @if($school->registration_certificate_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->registration_certificate_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="registration_certificate" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->registration_certificate_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">School License</label>
                        @if($school->school_license_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->school_license_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="school_license" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->school_license_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Audited Financial Statements</label>
                        @if($school->audited_statements_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->audited_statements_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="audited_statements" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->audited_statements_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Bank Statements (Last 6 months)</label>
                        @if($school->bank_statements_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->bank_statements_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="bank_statements" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->bank_statements_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">National ID of Owner(s)</label>
                        @if($school->owner_national_id_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->owner_national_id_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="owner_national_id" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->owner_national_id_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Land Title or Lease Agreement</label>
                        @if($school->land_title_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->land_title_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="land_title" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->land_title_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Existing Loan Agreements (if any)</label>
                        @if($school->existing_loan_agreements_path)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-check-circle me-2"></i>Already uploaded
                                <a href="{{ Storage::url($school->existing_loan_agreements_path) }}" target="_blank" class="ms-2">View</a>
                            </div>
                        @endif
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="mb-2">Click to upload or drag and drop</p>
                            <input type="file" class="form-control" name="existing_loan_agreements" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">{{ $school->existing_loan_agreements_path ? 'Upload new to replace' : 'Max 5MB' }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 10: Institutional Financial Standing & Ownership -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-university"></i>Section 10: Institutional Financial Standing & Ownership
                </h3>
                <p class="section-description">Detailed information about assets, liabilities, and ownership</p>

                <!-- Assets Section -->
                <h6 class="subsection-title">Current School Assets</h6>
                <div class="info-box mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Check all assets your school owns and specify the quantity/number
                </div>
                
                <div class="checkbox-group">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="School Buildings" id="asset_buildings" name="asset_types[]" 
                                    {{ in_array('School Buildings', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_buildings">School Buildings</label>
                            </div>
                            <input type="number" class="form-control" name="asset_quantities[]" placeholder="Number of buildings" min="0" 
                                value="{{ in_array('School Buildings', $oldAssetTypes) ? $oldAssetQuantities[array_search('School Buildings', $oldAssetTypes)] : '' }}" 
                                {{ in_array('School Buildings', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="School Buses" id="asset_buses" name="asset_types[]" 
                                    {{ in_array('School Buses', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_buses">School Buses</label>
                            </div>
                            <input type="number" class="form-control" name="asset_quantities[]" placeholder="Number of buses" min="0" 
                                value="{{ in_array('School Buses', $oldAssetTypes) ? $oldAssetQuantities[array_search('School Buses', $oldAssetTypes)] : '' }}" 
                                {{ in_array('School Buses', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Vehicles (Cars/Vans)" id="asset_vehicles" name="asset_types[]" 
                                    {{ in_array('Vehicles (Cars/Vans)', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_vehicles">Vehicles (Cars/Vans)</label>
                            </div>
                            <input type="number" class="form-control" name="asset_quantities[]" placeholder="Number of vehicles" min="0" 
                                value="{{ in_array('Vehicles (Cars/Vans)', $oldAssetTypes) ? $oldAssetQuantities[array_search('Vehicles (Cars/Vans)', $oldAssetTypes)] : '' }}" 
                                {{ in_array('Vehicles (Cars/Vans)', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Computers" id="asset_computers" name="asset_types[]" 
                                    {{ in_array('Computers', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_computers">Computers</label>
                            </div>
                            <input type="number" class="form-control" name="asset_quantities[]" placeholder="Number of computers" min="0" 
                                value="{{ in_array('Computers', $oldAssetTypes) ? $oldAssetQuantities[array_search('Computers', $oldAssetTypes)] : '' }}" 
                                {{ in_array('Computers', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Library Books" id="asset_books" name="asset_types[]" 
                                    {{ in_array('Library Books', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_books">Library Books</label>
                            </div>
                            <input type="number" class="form-control" name="asset_quantities[]" placeholder="Number of books" min="0" 
                                value="{{ in_array('Library Books', $oldAssetTypes) ? $oldAssetQuantities[array_search('Library Books', $oldAssetTypes)] : '' }}" 
                                {{ in_array('Library Books', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Sports Equipment" id="asset_sports" name="asset_types[]" 
                                    {{ in_array('Sports Equipment', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_sports">Sports Equipment</label>
                            </div>
                            <input type="text" class="form-control" name="asset_quantities[]" placeholder="e.g., Football, Netball sets" 
                                value="{{ in_array('Sports Equipment', $oldAssetTypes) ? $oldAssetQuantities[array_search('Sports Equipment', $oldAssetTypes)] : '' }}" 
                                {{ in_array('Sports Equipment', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Furniture (Desks/Chairs)" id="asset_furniture" name="asset_types[]" 
                                    {{ in_array('Furniture (Desks/Chairs)', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_furniture">Furniture (Desks/Chairs)</label>
                            </div>
                            <input type="number" class="form-control" name="asset_quantities[]" placeholder="Number of sets" min="0" 
                                value="{{ in_array('Furniture (Desks/Chairs)', $oldAssetTypes) ? $oldAssetQuantities[array_search('Furniture (Desks/Chairs)', $oldAssetTypes)] : '' }}" 
                                {{ in_array('Furniture (Desks/Chairs)', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Land/Property" id="asset_land" name="asset_types[]" 
                                    {{ in_array('Land/Property', $oldAssetTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset_land">Land/Property</label>
                            </div>
                            <input type="text" class="form-control" name="asset_quantities[]" placeholder="e.g., 5 acres" 
                                value="{{ in_array('Land/Property', $oldAssetTypes) ? $oldAssetQuantities[array_search('Land/Property', $oldAssetTypes)] : '' }}" 
                                {{ in_array('Land/Property', $oldAssetTypes) ? '' : 'disabled' }}>
                        </div>
                    </div>
                </div>

                <div id="otherAssetsContainer" class="mb-4">
                    <label class="form-label fw-bold">Other Assets</label>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-2" onclick="addAsset()">
                        <i class="fas fa-plus"></i> Add Another Asset
                    </button>
                    <div id="assetsList">
                        <!-- Dynamic asset rows will be added here -->
                    </div>
                </div>

                <!-- Liabilities Section -->
                <h6 class="subsection-title">Current School Liabilities</h6>
                <div class="info-box mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Check all liabilities and specify the amount owed
                </div>
                
                <div class="checkbox-group">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Outstanding Salaries" id="liability_salaries" name="liability_types[]" 
                                    {{ in_array('Outstanding Salaries', $oldLiabilityTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="liability_salaries">Outstanding Salaries</label>
                            </div>
                            <input type="text" class="form-control amount-input" name="liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false" 
                                value="{{ in_array('Outstanding Salaries', $oldLiabilityTypes) ? $oldLiabilityAmounts[array_search('Outstanding Salaries', $oldLiabilityTypes)] : '' }}" 
                                {{ in_array('Outstanding Salaries', $oldLiabilityTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Supplier Debts" id="liability_suppliers" name="liability_types[]" 
                                    {{ in_array('Supplier Debts', $oldLiabilityTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="liability_suppliers">Supplier Debts</label>
                            </div>
                            <input type="text" class="form-control amount-input" name="liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false" 
                                value="{{ in_array('Supplier Debts', $oldLiabilityTypes) ? $oldLiabilityAmounts[array_search('Supplier Debts', $oldLiabilityTypes)] : '' }}" 
                                {{ in_array('Supplier Debts', $oldLiabilityTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Loan Repayments" id="liability_loans" name="liability_types[]" 
                                    {{ in_array('Loan Repayments', $oldLiabilityTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="liability_loans">Loan Repayments</label>
                            </div>
                            <input type="text" class="form-control amount-input" name="liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false" 
                                value="{{ in_array('Loan Repayments', $oldLiabilityTypes) ? $oldLiabilityAmounts[array_search('Loan Repayments', $oldLiabilityTypes)] : '' }}" 
                                {{ in_array('Loan Repayments', $oldLiabilityTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Utility Bills" id="liability_utilities" name="liability_types[]" 
                                    {{ in_array('Utility Bills', $oldLiabilityTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="liability_utilities">Utility Bills (Electricity, Water)</label>
                            </div>
                            <input type="text" class="form-control amount-input" name="liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false" 
                                value="{{ in_array('Utility Bills', $oldLiabilityTypes) ? $oldLiabilityAmounts[array_search('Utility Bills', $oldLiabilityTypes)] : '' }}" 
                                {{ in_array('Utility Bills', $oldLiabilityTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Rent Arrears" id="liability_rent" name="liability_types[]" 
                                    {{ in_array('Rent Arrears', $oldLiabilityTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="liability_rent">Rent Arrears</label>
                            </div>
                            <input type="text" class="form-control amount-input" name="liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false" 
                                value="{{ in_array('Rent Arrears', $oldLiabilityTypes) ? $oldLiabilityAmounts[array_search('Rent Arrears', $oldLiabilityTypes)] : '' }}" 
                                {{ in_array('Rent Arrears', $oldLiabilityTypes) ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="Tax Obligations" id="liability_tax" name="liability_types[]" 
                                    {{ in_array('Tax Obligations', $oldLiabilityTypes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="liability_tax">Tax Obligations</label>
                            </div>
                            <input type="text" class="form-control amount-input" name="liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false" 
                                value="{{ in_array('Tax Obligations', $oldLiabilityTypes) ? $oldLiabilityAmounts[array_search('Tax Obligations', $oldLiabilityTypes)] : '' }}" 
                                {{ in_array('Tax Obligations', $oldLiabilityTypes) ? '' : 'disabled' }}>
                        </div>
                    </div>
                </div>

                <div id="otherLiabilitiesContainer" class="mb-4">
                    <label class="form-label fw-bold">Other Liabilities</label>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-2" onclick="addLiability()">
                        <i class="fas fa-plus"></i> Add Another Liability
                    </button>
                    <div id="liabilitiesList">
                        <!-- Dynamic liability rows will be added here -->
                    </div>
                </div>

                <!-- Debtors and Creditors Section -->
                <h6 class="subsection-title">School Debtors and Creditors</h6>
                <div class="info-box mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Add debtors (those who owe the school) and creditors (those the school owes)
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Debtors (Money Owed to School)</label>
                        <button type="button" class="btn btn-outline-success btn-sm mb-2 w-100" onclick="addDebtor()">
                            <i class="fas fa-plus"></i> Add Debtor
                        </button>
                        <div id="debtorsList">
                            @php
                                $oldDebtorNames = old('debtor_names', []);
                                $oldDebtorAmounts = old('debtor_amounts', []);
                            @endphp
                            
                            @if(count($oldDebtorNames) > 0)
                                @foreach($oldDebtorNames as $index => $debtorName)
                                    <div class="debtor-row mb-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="debtor_names[]" placeholder="Debtor name" value="{{ $debtorName }}">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control amount-input" name="debtor_amounts[]" placeholder="Amount owed" data-allow-decimal="false" value="{{ $oldDebtorAmounts[$index] ?? '' }}">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDebtor(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Creditors (Money School Owes)</label>
                        <button type="button" class="btn btn-outline-danger btn-sm mb-2 w-100" onclick="addCreditor()">
                            <i class="fas fa-plus"></i> Add Creditor
                        </button>
                        <div id="creditorsList">
                            @php
                                $oldCreditorNames = old('creditor_names', []);
                                $oldCreditorAmounts = old('creditor_amounts', []);
                            @endphp
                            
                            @if(count($oldCreditorNames) > 0)
                                @foreach($oldCreditorNames as $index => $creditorName)
                                    <div class="creditor-row mb-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="creditor_names[]" placeholder="Creditor name" value="{{ $creditorName }}">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control amount-input" name="creditor_amounts[]" placeholder="Amount owed" data-allow-decimal="false" value="{{ $oldCreditorAmounts[$index] ?? '' }}">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeCreditor(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <h6 class="subsection-title">Ministry of Education Status</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ministry_of_education_standing" class="form-label">School's Standing with Ministry of Education</label>
                        <select class="form-select" id="ministry_of_education_standing" name="ministry_of_education_standing">
                            <option value="">Select status</option>
                            <option value="Fully registered and compliant" {{ $school->ministry_of_education_standing == 'Fully registered and compliant' ? 'selected' : '' }}>Fully registered and compliant</option>
                            <option value="Provisionally registered" {{ $school->ministry_of_education_standing == 'Provisionally registered' ? 'selected' : '' }}>Provisionally registered</option>
                            <option value="Pending renewal" {{ $school->ministry_of_education_standing == 'Pending renewal' ? 'selected' : '' }}>Pending renewal</option>
                            <option value="Not yet registered" {{ $school->ministry_of_education_standing == 'Not yet registered' ? 'selected' : '' }}>Not yet registered</option>
                            <option value="Other" {{ $school->ministry_of_education_standing == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="license_validity_status" class="form-label">Valid License from Ministry of Education?</label>
                        <select class="form-select" id="license_validity_status" name="license_validity_status">
                            <option value="">Select status</option>
                            <option value="Valid and current" {{ $school->license_validity_status == 'Valid and current' ? 'selected' : '' }}>Yes, valid and current</option>
                            <option value="Expired" {{ $school->license_validity_status == 'Expired' ? 'selected' : '' }}>Yes, but expired</option>
                            <option value="No license" {{ $school->license_validity_status == 'No license' ? 'selected' : '' }}>No license available</option>
                            <option value="Other" {{ $school->license_validity_status == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">School Ownership Details and Percentage Shares</label>
                        <p class="text-muted small mb-2">Add all owners and their respective ownership percentages. Total must equal 100%.</p>
                        
                        <div id="ownershipContainer">
                            @php
                                $ownershipList = [];
                                if ($school->ownership_details) {
                                    // Try to parse as JSON first
                                    $ownershipList = json_decode($school->ownership_details, true);
                                    // If not JSON, it's old format - create single entry
                                    if (!is_array($ownershipList)) {
                                        $ownershipList = [];
                                    }
                                }
                            @endphp
                            
                            @if(count($ownershipList) > 0)
                                @foreach($ownershipList as $index => $owner)
                                    <div class="ownership-row row mb-2" data-index="{{ $index }}">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="owner_names[]" placeholder="Owner name (e.g., John Doe)" value="{{ $owner['name'] ?? '' }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control ownership-percentage" name="owner_percentages[]" placeholder="Percentage" min="0" max="100" step="0.01" value="{{ $owner['percentage'] ?? '' }}" required>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeOwner(this)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="ownership-row row mb-2" data-index="0">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="owner_names[]" placeholder="Owner name (e.g., John Doe)" required>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control ownership-percentage" name="owner_percentages[]" placeholder="Percentage" min="0" max="100" step="0.01" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeOwner(this)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addOwner()">
                            <i class="fas fa-plus"></i> Add Another Owner
                        </button>
                        
                        <div class="mt-2">
                            <small class="text-muted">Total Ownership: <span id="totalOwnership" class="fw-bold">0</span>%</small>
                            <span id="ownershipWarning" class="text-danger ms-2" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i> Total must equal 100%
                            </span>
                        </div>
                    </div>
                </div>

                <h6 class="subsection-title">Outstanding Loans & Collateral</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Outstanding Loans with Financial Institutions?</label>
                        <select class="form-select" name="has_outstanding_loans" id="has_outstanding_loans">
                            <option value="0" {{ $school->has_outstanding_loans == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $school->has_outstanding_loans == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Any Assets Pledged as Collateral?</label>
                        <select class="form-select" name="has_assets_as_collateral" id="has_assets_as_collateral">
                            <option value="0" {{ $school->has_assets_as_collateral == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $school->has_assets_as_collateral == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="outstanding_loans_details" class="form-label">If yes, indicate institution, amount, and loan maturity date</label>
                        <textarea class="form-control" id="outstanding_loans_details" name="outstanding_loans_details" rows="3" placeholder="e.g., Centenary Bank - UGX 50,000,000 - Matures Dec 2025">{{ old('outstanding_loans_details', $school->outstanding_loans_details) }}</textarea>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="collateral_assets_details" class="form-label">If yes, specify which assets and to which institution</label>
                        <textarea class="form-control" id="collateral_assets_details" name="collateral_assets_details" rows="3" placeholder="e.g., School building - Centenary Bank, School land - DFCU Bank">{{ old('collateral_assets_details', $school->collateral_assets_details) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 11: Declarations & Consent -->
            <div class="section-card">
                <h3 class="section-title">
                    <i class="fas fa-file-signature"></i>Section 11: Declarations & Consent
                </h3>
                <p class="section-description">Declaration by school owner/administrator and consent to share information</p>

                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> By completing this section, you confirm that all information provided is accurate and complete.
                </div>

                <h6 class="subsection-title">Declaration by School Owner / Administrator</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="declaration_name" class="form-label required">Full Name</label>
                        <input type="text" class="form-control" id="declaration_name" name="declaration_name" required value="{{ $school->declaration_name ?? $school->contact_person }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="declaration_date" class="form-label required">Date</label>
                        <input type="date" class="form-control" id="declaration_date" name="declaration_date" required value="{{ $school->declaration_date ?? date('Y-m-d') }}">
                    </div>

                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration_confirm" required>
                            <label class="form-check-label fw-bold" for="declaration_confirm">
                                I confirm that the information provided is accurate to the best of my knowledge.
                            </label>
                        </div>
                    </div>
                </div>

                <h6 class="subsection-title">Consent to Share Information</h6>
                <div class="info-box">
                    <i class="fas fa-shield-alt me-2"></i>
                    Your information will be shared securely with relevant financial institutions for the purpose of processing your loan application. We maintain strict data protection standards.
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="consent_to_share_information" name="consent_to_share_information" value="1" {{ $school->consent_to_share_information ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="consent_to_share_information">
                        I consent to share this information with relevant financial institutions for loan processing purposes.
                    </label>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="section-card text-center">
                <h4 class="mb-4">Ready to Submit Your Assessment?</h4>
                <p class="text-muted mb-4">Please review all information before submitting. Once submitted, your application will be reviewed by our team.</p>
                
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-paper-plane me-2"></i>Submit Comprehensive Assessment
                </button>

                <p class="text-muted mt-3 mb-0">
                    <small><i class="fas fa-lock me-2"></i>Your information is secure and encrypted</small>
                </p>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Location data cache
        let districtsData = [];
        let subcountiesData = {};
        let parishesData = {};
        let villagesData = {};
        
        // Store selected values for pre-population (prefer old values from validation errors)
        let selectedDistrict = "{{ old('district') && old('district') != 'other' ? old('district') : ($school->district ?? '') }}";
        let selectedCounty = "{{ old('county') && old('county') != 'other' ? old('county') : ($school->county ?? '') }}";
        let selectedParish = "{{ old('parish') && old('parish') != 'other' ? old('parish') : ($school->parish ?? '') }}";
        let selectedVillage = "{{ old('village') && old('village') != 'other' ? old('village') : ($school->village ?? '') }}";
        
        // Check if "other" option was selected
        let districtIsOther = "{{ old('district') == 'other' ? 'true' : 'false' }}" === 'true';
        let countyIsOther = "{{ old('county') == 'other' ? 'true' : 'false' }}" === 'true';
        let parishIsOther = "{{ old('parish') == 'other' ? 'true' : 'false' }}" === 'true';
        let villageIsOther = "{{ old('village') == 'other' ? 'true' : 'false' }}" === 'true';

        // Load districts on page load
        document.addEventListener('DOMContentLoaded', async function() {
            // Auto-scroll to first error if validation failed
            @if ($errors->any())
                const firstError = document.querySelector('.is-invalid, .alert-danger');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Highlight all invalid fields
                    const form = document.querySelector('form');
                    if (form) {
                        @foreach ($errors->keys() as $field)
                            const field_{{ str_replace(['.', '[', ']'], '_', $field) }} = form.querySelector('[name="{{ $field }}"]');
                            if (field_{{ str_replace(['.', '[', ']'], '_', $field) }}) {
                                field_{{ str_replace(['.', '[', ']'], '_', $field) }}.classList.add('is-invalid');
                            }
                        @endforeach
                    }
                }
            @endif
            
            await loadDistricts();
        });

        // Fetch districts from API
        async function loadDistricts() {
            try {
                const response = await fetch('/api/locations/districts');
                const result = await response.json();
                
                if (result.success) {
                    districtsData = result.data;
                    const districtSelect = document.getElementById('district_select');
                    
                    // Clear and populate districts
                    districtSelect.innerHTML = '<option value="">Select District</option>';
                    
                    result.data.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.name;
                        option.dataset.name = district.name;
                        
                        // Check if this district matches the selected one (by name, not ID)
                        if (district.name === selectedDistrict) {
                            option.selected = true;
                        }
                        
                        districtSelect.appendChild(option);
                    });
                    
                    // Add "Other" option
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    if (districtIsOther) {
                        otherOption.selected = true;
                    }
                    districtSelect.appendChild(otherOption);
                    
                    // If "other" was selected, show the text input
                    if (districtIsOther) {
                        document.getElementById('district_other').style.display = 'block';
                        document.getElementById('district_other').required = true;
                    }
                    
                    // If there's a pre-selected district (not "other"), load its subcounties
                    if (selectedDistrict && !districtIsOther) {
                        const selectedOption = Array.from(districtSelect.options).find(opt => opt.dataset.name === selectedDistrict);
                        if (selectedOption) {
                            await loadSubcounties(selectedOption.value);
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading districts:', error);
                alert('Failed to load districts. Please refresh the page.');
            }
        }

        // Handle district change
        function handleDistrictChange(value) {
            const districtOther = document.getElementById('district_other');
            if (value === 'other') {
                districtOther.style.display = 'block';
                districtOther.required = true;
                // Clear cascading dropdowns
                document.getElementById('county').innerHTML = '<option value="">Select Subcounty/County</option><option value="other">Other (Not in list)</option>';
                document.getElementById('parish').innerHTML = '<option value="">Select Parish</option><option value="other">Other (Not in list)</option>';
                document.getElementById('village_select').innerHTML = '<option value="">Select Village</option><option value="other">Other (Not in list)</option>';
            } else {
                districtOther.style.display = 'none';
                districtOther.required = false;
                loadSubcounties(value);
            }
        }

        // Load subcounties when district is selected
        async function loadSubcounties(districtId) {
            if (!districtId || districtId === 'other') {
                return;
            }

            try {
                const response = await fetch(`/api/locations/subcounties/${districtId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    subcountiesData[districtId] = result.data;
                    const countySelect = document.getElementById('county');
                    const parishSelect = document.getElementById('parish');
                    const villageSelect = document.getElementById('village_select');
                    
                    // Clear subcounties, parishes and villages
                    countySelect.innerHTML = '<option value="">Select Subcounty/County</option>';
                    parishSelect.innerHTML = '<option value="">Select Parish</option>';
                    villageSelect.innerHTML = '<option value="">Select Village</option>';
                    
                    result.data.forEach(subcounty => {
                        const option = document.createElement('option');
                        option.value = subcounty.id;
                        option.textContent = subcounty.name;
                        option.dataset.name = subcounty.name;
                        
                        if (subcounty.name === selectedCounty) {
                            option.selected = true;
                        }
                        
                        countySelect.appendChild(option);
                    });
                    
                    // Add "Other" option
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    if (countyIsOther) {
                        otherOption.selected = true;
                    }
                    countySelect.appendChild(otherOption);
                    
                    // If "other" was selected, show the text input
                    if (countyIsOther) {
                        document.getElementById('county_other').style.display = 'block';
                    }
                    
                    // If there's a pre-selected subcounty (not "other"), load its parishes
                    if (selectedCounty && !countyIsOther) {
                        const selectedOption = Array.from(countySelect.options).find(opt => opt.dataset.name === selectedCounty);
                        if (selectedOption) {
                            await loadParishes(selectedOption.value);
                        }
                    }
                } else {
                    // No subcounties found, show "Other" option and text input
                    console.log('No subcounties found for this district');
                    const countySelect = document.getElementById('county');
                    countySelect.innerHTML = '<option value="">Select Subcounty/County</option>';
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    otherOption.selected = true;
                    countySelect.appendChild(otherOption);
                    document.getElementById('county_other').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading subcounties:', error);
                // Don't show alert - just enable the "other" option
                const countySelect = document.getElementById('county');
                countySelect.innerHTML = '<option value="">Select Subcounty/County</option>';
                const otherOption = document.createElement('option');
                otherOption.value = 'other';
                otherOption.textContent = 'Other (Not in list)';
                otherOption.selected = true;
                countySelect.appendChild(otherOption);
                document.getElementById('county_other').style.display = 'block';
                console.log('Subcounty data not available. Please use "Other" option.');
            }
        }

        // Handle subcounty change
        function handleSubcountyChange(value) {
            const countyOther = document.getElementById('county_other');
            if (value === 'other') {
                countyOther.style.display = 'block';
                document.getElementById('parish').innerHTML = '<option value="">Select Parish</option><option value="other">Other (Not in list)</option>';
                document.getElementById('village_select').innerHTML = '<option value="">Select Village</option><option value="other">Other (Not in list)</option>';
            } else {
                countyOther.style.display = 'none';
                loadParishes(value);
            }
        }

        // Load parishes when subcounty is selected
        async function loadParishes(subcountyId) {
            if (!subcountyId || subcountyId === 'other') {
                return;
            }

            try {
                const response = await fetch(`/api/locations/parishes/${subcountyId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    parishesData[subcountyId] = result.data;
                    const parishSelect = document.getElementById('parish');
                    const villageSelect = document.getElementById('village_select');
                    
                    // Clear parishes and villages
                    parishSelect.innerHTML = '<option value="">Select Parish</option>';
                    villageSelect.innerHTML = '<option value="">Select Village</option>';
                    
                    result.data.forEach(parish => {
                        const option = document.createElement('option');
                        option.value = parish.id;
                        option.textContent = parish.name;
                        option.dataset.name = parish.name;
                        
                        if (parish.name === selectedParish) {
                            option.selected = true;
                        }
                        
                        parishSelect.appendChild(option);
                    });
                    
                    // Add "Other" option
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    if (parishIsOther) {
                        otherOption.selected = true;
                    }
                    parishSelect.appendChild(otherOption);

                    // If "other" was selected, show the text input
                    if (parishIsOther) {
                        document.getElementById('parish_other').style.display = 'block';
                    }

                    // If there's a pre-selected parish (not "other"), load its villages
                    if (selectedParish && !parishIsOther) {
                        const selectedOption = Array.from(parishSelect.options).find(opt => opt.dataset.name === selectedParish);
                        if (selectedOption) {
                            await loadVillages(selectedOption.value);
                        }
                    }
                } else {
                    // No parishes found, show "Other" option and text input
                    console.log('No parishes found for this subcounty');
                    const parishSelect = document.getElementById('parish');
                    parishSelect.innerHTML = '<option value="">Select Parish</option>';
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    otherOption.selected = true;
                    parishSelect.appendChild(otherOption);
                    document.getElementById('parish_other').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading parishes:', error);
                // Don't show alert - just enable the "other" option
                const parishSelect = document.getElementById('parish');
                parishSelect.innerHTML = '<option value="">Select Parish</option>';
                const otherOption = document.createElement('option');
                otherOption.value = 'other';
                otherOption.textContent = 'Other (Not in list)';
                otherOption.selected = true;
                parishSelect.appendChild(otherOption);
                document.getElementById('parish_other').style.display = 'block';
                console.log('Parish data not available. Please use "Other" option.');
            }
        }

        // Handle parish change
        function handleParishChange(value) {
            const parishOther = document.getElementById('parish_other');
            if (value === 'other') {
                parishOther.style.display = 'block';
                document.getElementById('village_select').innerHTML = '<option value="">Select Village</option><option value="other">Other (Not in list)</option>';
            } else {
                parishOther.style.display = 'none';
                loadVillages(value);
            }
        }

        // Load villages when parish is selected
        async function loadVillages(parishId) {
            if (!parishId || parishId === 'other') {
                return;
            }

            try {
                const response = await fetch(`/api/locations/villages/${parishId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    villagesData[parishId] = result.data;
                    const villageSelect = document.getElementById('village_select');
                    
                    // Clear villages
                    villageSelect.innerHTML = '<option value="">Select Village</option>';
                    
                    if (result.data.length > 0) {
                        result.data.forEach(village => {
                            const option = document.createElement('option');
                            option.value = village.id;
                            option.textContent = village.name;
                            option.dataset.name = village.name;
                            
                            if (village.name === selectedVillage) {
                                option.selected = true;
                            }
                            
                            villageSelect.appendChild(option);
                        });
                    }
                    
                    // Add "Other" option
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    if (villageIsOther) {
                        otherOption.selected = true;
                    }
                    villageSelect.appendChild(otherOption);

                    // If "other" was selected, show the text input
                    if (villageIsOther) {
                        document.getElementById('village_other').style.display = 'block';
                    }

                    // If no villages found, show the text input
                    if (result.data.length === 0) {
                        document.getElementById('village_other').style.display = 'block';
                        console.log('No villages found for this parish');
                    }
                } else {
                    // No villages found, show "Other" option and text input
                    console.log('No villages found for this parish');
                    const villageSelect = document.getElementById('village_select');
                    villageSelect.innerHTML = '<option value="">Select Village</option>';
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other (Not in list)';
                    otherOption.selected = true;
                    villageSelect.appendChild(otherOption);
                    document.getElementById('village_other').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading villages:', error);
                // Don't show alert - just enable the "other" option
                const villageSelect = document.getElementById('village_select');
                villageSelect.innerHTML = '<option value="">Select Village</option>';
                const otherOption = document.createElement('option');
                otherOption.value = 'other';
                otherOption.textContent = 'Other (Not in list)';
                otherOption.selected = true;
                villageSelect.appendChild(otherOption);
                document.getElementById('village_other').style.display = 'block';
                console.log('Village data not available. Please use "Other" option or text input.');
            }
        }

        // Handle village change
        function handleVillageChange(value) {
            const villageOther = document.getElementById('village_other');
            if (value === 'other' || value === '') {
                villageOther.style.display = 'block';
            } else {
                villageOther.style.display = 'none';
            }
        }

        // Handle electricity provider change
        function handleElectricityProviderChange(value) {
            const electricityProviderOther = document.getElementById('electricity_provider_other');
            if (value === 'Other') {
                electricityProviderOther.style.display = 'block';
                electricityProviderOther.setAttribute('required', 'required');
            } else {
                electricityProviderOther.style.display = 'none';
                electricityProviderOther.removeAttribute('required');
                electricityProviderOther.value = '';
            }
        }

        // Handle internet provider change
        function handleInternetProviderChange(value) {
            const internetProviderOther = document.getElementById('internet_provider_other');
            if (value === 'Other') {
                internetProviderOther.style.display = 'block';
                internetProviderOther.setAttribute('required', 'required');
            } else {
                internetProviderOther.style.display = 'none';
                internetProviderOther.removeAttribute('required');
                internetProviderOther.value = '';
            }
        }

        // Handle banking institution change
        function handleBankChange(value) {
            const bankOther = document.getElementById('banking_institutions_other');
            if (value === 'Other') {
                bankOther.style.display = 'block';
                bankOther.setAttribute('required', 'required');
            } else {
                bankOther.style.display = 'none';
                bankOther.removeAttribute('required');
                bankOther.value = '';
            }
        }

        // Handle transport assets "Other" checkbox
        document.getElementById('transport_other_check')?.addEventListener('change', function() {
            const transportOtherInput = document.getElementById('transport_assets_other');
            if (this.checked) {
                transportOtherInput.style.display = 'block';
                transportOtherInput.setAttribute('required', 'required');
            } else {
                transportOtherInput.style.display = 'none';
                transportOtherInput.removeAttribute('required');
                transportOtherInput.value = '';
            }
        });

        // Handle learning resources "Other" checkbox
        document.getElementById('resource_other_check')?.addEventListener('change', function() {
            const resourceOtherInput = document.getElementById('learning_resources_other');
            if (this.checked) {
                resourceOtherInput.style.display = 'block';
                resourceOtherInput.setAttribute('required', 'required');
            } else {
                resourceOtherInput.style.display = 'none';
                resourceOtherInput.removeAttribute('required');
                resourceOtherInput.value = '';
            }
        });

        // Store actual names in hidden fields before form submission
        document.getElementById('assessmentForm').addEventListener('submit', function(e) {
            const districtSelect = document.getElementById('district_select');
            const countySelect = document.getElementById('county');
            const parishSelect = document.getElementById('parish');
            const villageSelect = document.getElementById('village_select');
            
            // Handle "Other" inputs
            if (districtSelect.value === 'other') {
                const otherValue = document.getElementById('district_other').value;
                if (!otherValue) {
                    e.preventDefault();
                    alert('Please enter the district name');
                    return false;
                }
                districtSelect.value = otherValue;
            } else {
                // Replace IDs with names for storage
                const districtOption = districtSelect.options[districtSelect.selectedIndex];
                if (districtOption && districtOption.dataset.name) {
                    districtSelect.value = districtOption.dataset.name;
                }
            }

            if (countySelect.value === 'other') {
                const otherValue = document.getElementById('county_other').value;
                if (!otherValue) {
                    e.preventDefault();
                    alert('Please enter the subcounty name');
                    return false;
                }
                countySelect.value = otherValue;
            } else {
                const countyOption = countySelect.options[countySelect.selectedIndex];
                if (countyOption && countyOption.dataset.name) {
                    countySelect.value = countyOption.dataset.name;
                }
            }

            if (parishSelect.value === 'other') {
                const otherValue = document.getElementById('parish_other').value;
                if (!otherValue) {
                    e.preventDefault();
                    alert('Please enter the parish name');
                    return false;
                }
                parishSelect.value = otherValue;
            } else {
                const parishOption = parishSelect.options[parishSelect.selectedIndex];
                if (parishOption && parishOption.dataset.name) {
                    parishSelect.value = parishOption.dataset.name;
                }
            }

            // Handle village
            const villageOther = document.getElementById('village_other');
            if (villageSelect.value === 'other' || villageSelect.value === '') {
                if (villageOther.value) {
                    villageSelect.value = villageOther.value;
                }
            } else {
                const villageOption = villageSelect.options[villageSelect.selectedIndex];
                if (villageOption && villageOption.dataset.name) {
                    villageSelect.value = villageOption.dataset.name;
                }
            }
            
            // Continue with other validations
            const declarationCheck = document.getElementById('declaration_confirm');
            
            if (!declarationCheck.checked) {
                e.preventDefault();
                alert('Please confirm that all information provided is accurate.');
                declarationCheck.focus();
                return false;
            }

            // Confirm submission
            if (!confirm('Are you sure you want to submit this assessment? Please review all information before confirming.')) {
                e.preventDefault();
                return false;
            }
        });

        // Dynamic income sources
        let incomeSourceIndex = {{ count($incomeSources ?? []) }};

        function addIncomeSource() {
            const container = document.getElementById('incomeSourcesContainer');
            const newRow = document.createElement('div');
            newRow.className = 'income-source-row row mb-2';
            newRow.setAttribute('data-index', incomeSourceIndex);
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="income_sources[]" placeholder="Income source (e.g., Boarding)">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control amount-input" name="income_amounts[]" placeholder="e.g., 500,000" data-allow-decimal="false">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeIncomeSource(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Initialize amount formatting for the new input
            const newAmountInput = newRow.querySelector('.amount-input');
            if (newAmountInput) {
                initializeAmountInput(newAmountInput);
            }
            
            incomeSourceIndex++;
        }

        function removeIncomeSource(button) {
            const container = document.getElementById('incomeSourcesContainer');
            const rows = container.querySelectorAll('.income-source-row');
            
            // Don't allow removing the last row
            if (rows.length > 1) {
                button.closest('.income-source-row').remove();
            } else {
                alert('At least one income source row must remain.');
            }
        }

        // Assets, Liabilities, Debtors, Creditors Management
        let assetIndex = 0;
        let liabilityIndex = 0;
        let debtorIndex = 0;
        let creditorIndex = 0;

        // Enable/disable quantity inputs when checkboxes are clicked
        document.querySelectorAll('input[name="asset_types[]"]').forEach((checkbox, index) => {
            checkbox.addEventListener('change', function() {
                const quantityInput = this.closest('.col-md-6').querySelector('input[name="asset_quantities[]"]');
                if (this.checked) {
                    quantityInput.disabled = false;
                    quantityInput.setAttribute('required', 'required');
                } else {
                    quantityInput.disabled = true;
                    quantityInput.removeAttribute('required');
                    quantityInput.value = '';
                }
            });
        });

        document.querySelectorAll('input[name="liability_types[]"]').forEach((checkbox, index) => {
            checkbox.addEventListener('change', function() {
                const amountInput = this.closest('.col-md-6').querySelector('input[name="liability_amounts[]"]');
                if (this.checked) {
                    amountInput.disabled = false;
                    amountInput.setAttribute('required', 'required');
                    // Initialize amount formatting
                    initializeAmountInput(amountInput);
                } else {
                    amountInput.disabled = true;
                    amountInput.removeAttribute('required');
                    amountInput.value = '';
                }
            });
        });

        function addAsset() {
            const container = document.getElementById('assetsList');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 asset-row';
            newRow.setAttribute('data-index', assetIndex);
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="other_asset_types[]" placeholder="Asset name">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="other_asset_quantities[]" placeholder="Quantity/Description">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeAsset(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            assetIndex++;
        }

        function removeAsset(button) {
            button.closest('.asset-row').remove();
        }

        function addLiability() {
            const container = document.getElementById('liabilitiesList');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 liability-row';
            newRow.setAttribute('data-index', liabilityIndex);
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="other_liability_types[]" placeholder="Liability name">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control amount-input" name="other_liability_amounts[]" placeholder="Amount owed (UGX)" data-allow-decimal="false">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeLiability(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Initialize amount formatting for the new input
            const newAmountInput = newRow.querySelector('.amount-input');
            if (newAmountInput) {
                initializeAmountInput(newAmountInput);
            }
            
            liabilityIndex++;
        }

        function removeLiability(button) {
            button.closest('.liability-row').remove();
        }

        function addDebtor() {
            const container = document.getElementById('debtorsList');
            const newRow = document.createElement('div');
            newRow.className = 'mb-2 debtor-row';
            newRow.setAttribute('data-index', debtorIndex);
            newRow.innerHTML = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="debtor_names[]" placeholder="Debtor name">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeDebtor(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <input type="text" class="form-control amount-input mb-2" name="debtor_amounts[]" placeholder="Amount owed to school (UGX)" data-allow-decimal="false">
            `;
            container.appendChild(newRow);
            
            // Initialize amount formatting for the new input
            const newAmountInput = newRow.querySelector('.amount-input');
            if (newAmountInput) {
                initializeAmountInput(newAmountInput);
            }
            
            debtorIndex++;
        }

        function removeDebtor(button) {
            button.closest('.debtor-row').remove();
        }

        function addCreditor() {
            const container = document.getElementById('creditorsList');
            const newRow = document.createElement('div');
            newRow.className = 'mb-2 creditor-row';
            newRow.setAttribute('data-index', creditorIndex);
            newRow.innerHTML = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="creditor_names[]" placeholder="Creditor name">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeCreditor(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <input type="text" class="form-control amount-input mb-2" name="creditor_amounts[]" placeholder="Amount school owes (UGX)" data-allow-decimal="false">
            `;
            container.appendChild(newRow);
            
            // Initialize amount formatting for the new input
            const newAmountInput = newRow.querySelector('.amount-input');
            if (newAmountInput) {
                initializeAmountInput(newAmountInput);
            }
            
            creditorIndex++;
        }

        function removeCreditor(button) {
            button.closest('.creditor-row').remove();
        }

        // Ownership Management
        let ownershipIndex = {{ count($ownershipList ?? []) }};

        function addOwner() {
            const container = document.getElementById('ownershipContainer');
            const newRow = document.createElement('div');
            newRow.className = 'ownership-row row mb-2';
            newRow.setAttribute('data-index', ownershipIndex);
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="owner_names[]" placeholder="Owner name (e.g., John Doe)" required>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="number" class="form-control ownership-percentage" name="owner_percentages[]" placeholder="Percentage" min="0" max="100" step="0.01" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeOwner(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Attach change event to new percentage input
            const percentageInput = newRow.querySelector('.ownership-percentage');
            percentageInput.addEventListener('input', calculateTotalOwnership);
            
            ownershipIndex++;
            calculateTotalOwnership();
        }

        function removeOwner(button) {
            const container = document.getElementById('ownershipContainer');
            const rows = container.querySelectorAll('.ownership-row');
            
            // Don't allow removing the last row
            if (rows.length > 1) {
                button.closest('.ownership-row').remove();
                calculateTotalOwnership();
            } else {
                alert('At least one owner must remain.');
            }
        }

        function calculateTotalOwnership() {
            const percentageInputs = document.querySelectorAll('.ownership-percentage');
            let total = 0;
            
            percentageInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            const totalSpan = document.getElementById('totalOwnership');
            const warningSpan = document.getElementById('ownershipWarning');
            
            totalSpan.textContent = total.toFixed(2);
            
            // Show warning if total doesn't equal 100%
            if (Math.abs(total - 100) > 0.01) {
                totalSpan.classList.add('text-danger');
                totalSpan.classList.remove('text-success');
                warningSpan.style.display = 'inline';
            } else {
                totalSpan.classList.add('text-success');
                totalSpan.classList.remove('text-danger');
                warningSpan.style.display = 'none';
            }
        }

        // Initialize ownership percentage calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Attach change event to existing percentage inputs
            document.querySelectorAll('.ownership-percentage').forEach(input => {
                input.addEventListener('input', calculateTotalOwnership);
            });
            
            // Calculate initial total
            calculateTotalOwnership();
        });

        // Student fees file preview
        function previewStudentFees(input) {
            const file = input.files[0];
            const uploadSummary = document.getElementById('uploadSummary');
            const summaryText = document.getElementById('summaryText');
            const previewSection = document.getElementById('studentFeesPreview');
            const previewTableBody = document.getElementById('previewTableBody');
            
            if (!file) {
                uploadSummary.style.display = 'none';
                previewSection.style.display = 'none';
                return;
            }
            
            // Show file name
            uploadSummary.style.display = 'block';
            summaryText.textContent = `File: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
            
            // Read Excel file
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const jsonData = XLSX.utils.sheet_to_json(firstSheet);
                    
                    // Clear previous preview
                    previewTableBody.innerHTML = '';
                    
                    // Show first 10 rows
                    const previewData = jsonData.slice(0, 10);
                    let totalBalance = 0;
                    
                    previewData.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row['Student Name'] || row['student_name'] || '-'}</td>
                            <td>${row['Class'] || row['class'] || '-'}</td>
                            <td>${formatCurrency(row['Term Fees'] || row['term_fees'] || 0)}</td>
                            <td>${formatCurrency(row['Amount Paid'] || row['amount_paid'] || 0)}</td>
                            <td>${formatCurrency(row['Balance'] || row['balance'] || 0)}</td>
                        `;
                        previewTableBody.appendChild(tr);
                        totalBalance += parseFloat(row['Balance'] || row['balance'] || 0);
                    });
                    
                    // Update summary
                    summaryText.innerHTML = `File: ${file.name} | Students: ${jsonData.length} | Preview Balance: ${formatCurrency(totalBalance)}`;
                    previewSection.style.display = 'block';
                    
                } catch (error) {
                    alert('Error reading Excel file. Please ensure it matches the template format.');
                    console.error(error);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        // Unpaid students file preview
        function previewUnpaidStudents(input) {
            const file = input.files[0];
            const uploadSummary = document.getElementById('unpaidUploadSummary');
            const summaryText = document.getElementById('unpaidSummaryText');
            const previewSection = document.getElementById('unpaidStudentsPreview');
            const previewTableBody = document.getElementById('unpaidPreviewTableBody');
            
            if (!file) {
                uploadSummary.style.display = 'none';
                previewSection.style.display = 'none';
                return;
            }
            
            // Show file name
            uploadSummary.style.display = 'block';
            summaryText.textContent = `File: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
            
            // Read Excel file
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const jsonData = XLSX.utils.sheet_to_json(firstSheet);
                    
                    // Clear previous preview
                    previewTableBody.innerHTML = '';
                    
                    // Show first 10 rows
                    const previewData = jsonData.slice(0, 10);
                    let totalBalance = 0;
                    let totalExpected = 0;
                    let totalPaid = 0;
                    
                    previewData.forEach(row => {
                        const expectedFees = parseFloat(row['Expected Fees'] || row['expected_fees'] || 0);
                        const amountPaid = parseFloat(row['Amount Paid'] || row['amount_paid'] || 0);
                        const balance = parseFloat(row['Balance'] || row['balance'] || expectedFees - amountPaid);
                        const paymentPercentage = expectedFees > 0 ? ((amountPaid / expectedFees) * 100).toFixed(0) : 0;
                        
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row['Student Name'] || row['student_name'] || '-'}</td>
                            <td>${row['Class'] || row['Grade'] || row['class'] || row['grade'] || '-'}</td>
                            <td>${formatCurrency(expectedFees)}</td>
                            <td>${formatCurrency(amountPaid)}</td>
                            <td class="text-danger fw-bold">${formatCurrency(balance)}</td>
                            <td><span class="badge ${paymentPercentage >= 65 ? 'bg-warning' : 'bg-danger'}">${paymentPercentage}%</span></td>
                        `;
                        previewTableBody.appendChild(tr);
                        
                        totalBalance += balance;
                        totalExpected += expectedFees;
                        totalPaid += amountPaid;
                    });
                    
                    // Update summary
                    const overallPercentage = totalExpected > 0 ? ((totalPaid / totalExpected) * 100).toFixed(0) : 0;
                    summaryText.innerHTML = `File: ${file.name} | Students: ${jsonData.length} | Total Balance: ${formatCurrency(totalBalance)} | Avg Payment: ${overallPercentage}%`;
                    previewSection.style.display = 'block';
                    
                } catch (error) {
                    alert('Error reading Excel file. Please ensure it matches the template format.');
                    console.error(error);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function formatCurrency(amount) {
            return 'UGX ' + parseFloat(amount).toLocaleString('en-UG');
        }

        // Enable/disable expense amount inputs based on checkbox
        document.querySelectorAll('.expense-category').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const amountInput = this.closest('.col-md-6, .col-12').querySelector('.expense-amount');
                amountInput.disabled = !this.checked;
                if (!this.checked) {
                    amountInput.value = '';
                }
            });
        });

        // Show/hide previous loan details based on selection
        document.getElementById('has_received_loan_before')?.addEventListener('change', function() {
            const previousLoanDetailsWrapper = document.getElementById('previous_loan_details_wrapper');
            if (this.value == '1') {
                previousLoanDetailsWrapper.style.display = 'block';
            } else {
                previousLoanDetailsWrapper.style.display = 'none';
                document.getElementById('previous_loan_details').value = '';
            }
        });

        // Amount input formatting with commas
        function initializeAmountInput(input) {
            // Format on input
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                
                // Remove all non-numeric characters except decimal point
                value = value.replace(/[^\d.]/g, '');
                
                // Ensure only one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // If decimal not allowed, remove it
                if (input.getAttribute('data-allow-decimal') === 'false') {
                    value = value.replace(/\./g, '');
                }
                
                // Format with commas
                if (value !== '') {
                    const [integerPart, decimalPart] = value.split('.');
                    const formattedInteger = parseInt(integerPart || '0').toLocaleString('en-US');
                    e.target.value = decimalPart !== undefined ? formattedInteger + '.' + decimalPart : formattedInteger;
                } else {
                    e.target.value = '';
                }
            });
            
            // Format existing value on page load
            if (input.value) {
                let value = input.value.replace(/[^\d.]/g, '');
                if (value !== '' && !isNaN(value)) {
                    if (input.getAttribute('data-allow-decimal') === 'false') {
                        value = Math.floor(parseFloat(value)).toString();
                    }
                    const [integerPart, decimalPart] = value.split('.');
                    const formattedInteger = parseInt(integerPart || '0').toLocaleString('en-US');
                    input.value = decimalPart !== undefined ? formattedInteger + '.' + decimalPart : formattedInteger;
                }
            }
        }
        
        // Initialize all existing amount inputs
        document.querySelectorAll('.amount-input').forEach(input => {
            initializeAmountInput(input);
        });
        
        // Strip commas before form submission
        const assessmentForm = document.querySelector('form');
        if (assessmentForm) {
            assessmentForm.addEventListener('submit', function(e) {
                document.querySelectorAll('.amount-input').forEach(input => {
                    if (input.value) {
                        input.value = input.value.replace(/,/g, '');
                    }
                });
            });
        }

        // Smooth scroll to top on page load
        window.scrollTo({ top: 0, behavior: 'smooth' });
    </script>
</body>
</html>
