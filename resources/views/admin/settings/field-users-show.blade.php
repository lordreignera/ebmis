@extends('layouts.admin')

@section('title', 'View Field User')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.field-users') }}">Field Users</a></li>
                        <li class="breadcrumb-item active">View Details</li>
                    </ol>
                </div>
                <h4 class="page-title">Field User Details</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    @if($fieldUser->pp_file)
                        <img src="{{ Storage::url($fieldUser->pp_file) }}" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px; font-size: 3rem;">
                            {{ strtoupper(substr($fieldUser->fname, 0, 1)) }}{{ strtoupper(substr($fieldUser->lname, 0, 1)) }}
                        </div>
                    @endif
                    
                    <h4>{{ $fieldUser->fname }} {{ $fieldUser->mname }} {{ $fieldUser->lname }}</h4>
                    <p class="text-muted mb-2">
                        <span class="badge bg-primary fs-6">{{ $fieldUser->code }}</span>
                    </p>
                    <p class="text-muted">Field User / Loan Officer</p>
                    
                    <div class="mt-3">
                        @if($fieldUser->verified == 1)
                            <span class="badge bg-success fs-6">
                                <i class="mdi mdi-check-circle me-1"></i>Verified
                            </span>
                        @else
                            <span class="badge bg-warning fs-6">
                                <i class="mdi mdi-alert me-1"></i>Not Verified
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <a href="{{ route('admin.settings.field-users.edit', $fieldUser->id) }}" class="btn btn-primary">
                            <i class="mdi mdi-pencil me-1"></i> Edit Details
                        </a>
                        <a href="{{ route('admin.settings.field-users') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills nav-justified mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#personal" role="tab">
                                <i class="mdi mdi-account me-1"></i> Personal Info
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#contact" role="tab">
                                <i class="mdi mdi-phone me-1"></i> Contact Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#address" role="tab">
                                <i class="mdi mdi-map-marker me-1"></i> Address
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#documents" role="tab">
                                <i class="mdi mdi-file-document me-1"></i> Documents
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Personal Info Tab -->
                        <div class="tab-pane show active" id="personal" role="tabpanel">
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">First Name</label>
                                    <p class="fw-semibold">{{ $fieldUser->fname }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Middle Name</label>
                                    <p class="fw-semibold">{{ $fieldUser->mname ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Last Name</label>
                                    <p class="fw-semibold">{{ $fieldUser->lname }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">National ID (NIN)</label>
                                    <p class="fw-semibold">{{ $fieldUser->nin }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Gender</label>
                                    <p class="fw-semibold">{{ $fieldUser->gender == 'm' ? 'Male' : 'Female' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Date of Birth</label>
                                    <p class="fw-semibold">{{ $fieldUser->dob ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Branch</label>
                                    <p class="fw-semibold">{{ $fieldUser->branch->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Mobile PIN</label>
                                    <p class="fw-semibold">
                                        <span id="pin_display">••••••</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="togglePinDisplay()">
                                            <i class="mdi mdi-eye" id="pin_icon"></i>
                                        </button>
                                        <input type="hidden" id="actual_pin" value="{{ $fieldUser->mobile_pin }}">
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Details Tab -->
                        <div class="tab-pane" id="contact" role="tabpanel">
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Primary Contact</label>
                                    <p class="fw-semibold">
                                        <i class="mdi mdi-phone text-primary me-1"></i>
                                        +256{{ $fieldUser->contact }}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Alternative Contact</label>
                                    <p class="fw-semibold">
                                        <i class="mdi mdi-phone text-success me-1"></i>
                                        {{ $fieldUser->alt_contact ? '+256' . $fieldUser->alt_contact : 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Fixed Line</label>
                                    <p class="fw-semibold">
                                        <i class="mdi mdi-phone-classic text-info me-1"></i>
                                        {{ $fieldUser->fixed_line ? '+256' . $fieldUser->fixed_line : 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Email Address</label>
                                    <p class="fw-semibold">
                                        <i class="mdi mdi-email text-warning me-1"></i>
                                        {{ $fieldUser->email ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Address Tab -->
                        <div class="tab-pane" id="address" role="tabpanel">
                            <h5 class="mb-3">Residential Address</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Plot Number</label>
                                    <p class="fw-semibold">{{ $fieldUser->plot_no ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Village</label>
                                    <p class="fw-semibold">{{ $fieldUser->village }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Parish</label>
                                    <p class="fw-semibold">{{ $fieldUser->parish }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Sub County</label>
                                    <p class="fw-semibold">{{ $fieldUser->subcounty }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">County</label>
                                    <p class="fw-semibold">{{ $fieldUser->county }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Country</label>
                                    <p class="fw-semibold">{{ $fieldUser->country->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane" id="documents" role="tabpanel">
                            <h5 class="mb-3">Uploaded Documents</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Profile Picture</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            @if($fieldUser->pp_file)
                                                <img src="{{ Storage::url($fieldUser->pp_file) }}" alt="Profile" class="img-fluid rounded" style="max-height: 300px;">
                                                <div class="mt-2">
                                                    <a href="{{ Storage::url($fieldUser->pp_file) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download me-1"></i> Download
                                                    </a>
                                                </div>
                                            @else
                                                <p class="text-muted">No profile picture uploaded</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Identification Document</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            @if($fieldUser->id_file)
                                                <img src="{{ Storage::url($fieldUser->id_file) }}" alt="ID" class="img-fluid rounded" style="max-height: 300px;">
                                                <div class="mt-2">
                                                    <a href="{{ Storage::url($fieldUser->id_file) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download me-1"></i> Download
                                                    </a>
                                                </div>
                                            @else
                                                <p class="text-muted">No ID document uploaded</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>System Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="text-muted">Added By</label>
                                        <p class="fw-semibold">{{ $fieldUser->addedBy->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted">Date Added</label>
                                        <p class="fw-semibold">{{ \Carbon\Carbon::parse($fieldUser->datecreated)->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePinDisplay() {
    const pinDisplay = document.getElementById('pin_display');
    const pinIcon = document.getElementById('pin_icon');
    const actualPin = document.getElementById('actual_pin').value;
    
    if (pinDisplay.textContent === '••••••') {
        pinDisplay.textContent = actualPin;
        pinIcon.classList.remove('mdi-eye');
        pinIcon.classList.add('mdi-eye-off');
    } else {
        pinDisplay.textContent = '••••••';
        pinIcon.classList.remove('mdi-eye-off');
        pinIcon.classList.add('mdi-eye');
    }
}
</script>
@endpush
@endsection
