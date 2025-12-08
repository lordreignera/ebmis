@extends('layouts.admin')

@section('title', 'Add Field User')

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
                        <li class="breadcrumb-item active">Add Field User</li>
                    </ol>
                </div>
                <h4 class="page-title">Add Field User</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.settings.field-users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Personal Info Section -->
                        <div class="mb-4">
                            <h5 class="text-uppercase text-primary mb-3">
                                <i class="mdi mdi-account-outline me-2"></i>Personal Information
                            </h5>
                            <hr>

                            <div class="alert alert-info">
                                <i class="mdi mdi-information-outline me-2"></i>
                                Please type carefully and fill out the form with the Field user details. Some aspects won't be editable once you have submitted the form.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Account Code</label>
                                    <input type="text" class="form-control" value="FM{{ time() }}" readonly>
                                    <small class="text-muted">Auto-generated on submission</small>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Select Branch <span class="text-danger">*</span></label>
                                    <select class="form-select @error('branch_id') is-invalid @enderror" name="branch_id" required>
                                        <option value="">Choose...</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6"></div>

                                <div class="col-md-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('fname') is-invalid @enderror" name="fname" value="{{ old('fname') }}" required>
                                    @error('fname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control @error('mname') is-invalid @enderror" name="mname" value="{{ old('mname') }}">
                                    @error('mname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Last Name (Surname) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('lname') is-invalid @enderror" name="lname" value="{{ old('lname') }}" required>
                                    @error('lname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">National ID Number (NIN) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nin') is-invalid @enderror" name="nin" value="{{ old('nin') }}" required>
                                    @error('nin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Select Gender <span class="text-danger">*</span></label>
                                    <select class="form-select @error('gender') is-invalid @enderror" name="gender" required>
                                        <option value="">Choose...</option>
                                        <option value="m" {{ old('gender') == 'm' ? 'selected' : '' }}>Male</option>
                                        <option value="f" {{ old('gender') == 'f' ? 'selected' : '' }}>Female</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" value="{{ old('dob') }}">
                                    @error('dob')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Mobile Login Pin <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control @error('mobile_pin') is-invalid @enderror" name="mobile_pin" id="mobile_pin_create" required minlength="4" maxlength="10">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePinVisibility('mobile_pin_create', 'pin_icon_create')">
                                            <i class="mdi mdi-eye" id="pin_icon_create"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">4-10 digits for mobile app login</small>
                                    @error('mobile_pin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3"></div>

                                <div class="col-md-3">
                                    <label class="form-label">Telephone Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">+256</span>
                                        <input type="text" class="form-control @error('contact') is-invalid @enderror" name="contact" value="{{ old('contact') }}" required>
                                    </div>
                                    @error('contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Mobile Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">+256</span>
                                        <input type="text" class="form-control @error('alt_contact') is-invalid @enderror" name="alt_contact" value="{{ old('alt_contact') }}">
                                    </div>
                                    @error('alt_contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Fixed Line</label>
                                    <div class="input-group">
                                        <span class="input-group-text">+256</span>
                                        <input type="text" class="form-control @error('fixed_line') is-invalid @enderror" name="fixed_line" value="{{ old('fixed_line') }}">
                                    </div>
                                    @error('fixed_line')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Residential Address Section -->
                        <div class="mb-4">
                            <h5 class="text-uppercase text-primary mb-3">
                                <i class="mdi mdi-home-outline me-2"></i>Residential Address Information
                            </h5>
                            <hr>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Plot Number</label>
                                    <input type="text" class="form-control @error('plot_no') is-invalid @enderror" name="plot_no" id="plot_no" value="{{ old('plot_no') }}">
                                    @error('plot_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Village <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('village') is-invalid @enderror" name="village" id="village" value="{{ old('village') }}" required>
                                    @error('village')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Parish <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('parish') is-invalid @enderror" name="parish" id="parish" value="{{ old('parish') }}" required>
                                    @error('parish')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Sub County <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subcounty') is-invalid @enderror" name="subcounty" id="subcounty" value="{{ old('subcounty') }}" required>
                                    @error('subcounty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">County <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('county') is-invalid @enderror" name="county" id="county" value="{{ old('county') }}" required>
                                    @error('county')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Select Country <span class="text-danger">*</span></label>
                                    <select class="form-select @error('country_id') is-invalid @enderror" name="country_id" required>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ old('country_id', 1) == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Place of Birth Section -->
                        <div class="mb-4">
                            <h5 class="text-uppercase text-primary mb-3">
                                <i class="mdi mdi-map-marker-outline me-2"></i>Place of Birth Address Information
                            </h5>
                            <hr>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="sameasresidential" onchange="copyResidentialAddress()">
                                    <label class="form-check-label" for="sameasresidential">
                                        Click the switch if the <strong>place of birth address</strong> is the same as the <strong>residential address</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Plot Number</label>
                                    <input type="text" class="form-control @error('pplot_no') is-invalid @enderror" name="pplot_no" id="pplot_no" value="{{ old('pplot_no') }}">
                                    @error('pplot_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Village <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pvillage') is-invalid @enderror" name="pvillage" id="pvillage" value="{{ old('pvillage') }}" required>
                                    @error('pvillage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Parish <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pparish') is-invalid @enderror" name="pparish" id="pparish" value="{{ old('pparish') }}" required>
                                    @error('pparish')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Sub County <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('psubcounty') is-invalid @enderror" name="psubcounty" id="psubcounty" value="{{ old('psubcounty') }}" required>
                                    @error('psubcounty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">County <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pcounty') is-invalid @enderror" name="pcounty" id="pcounty" value="{{ old('pcounty') }}" required>
                                    @error('pcounty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Select Country <span class="text-danger">*</span></label>
                                    <select class="form-select @error('pcountry_id') is-invalid @enderror" name="pcountry_id" required>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ old('pcountry_id', 1) == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('pcountry_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Upload Documents Section -->
                        <div class="mb-4">
                            <h5 class="text-uppercase text-primary mb-3">
                                <i class="mdi mdi-file-document-outline me-2"></i>Upload Supporting Documents
                            </h5>
                            <hr>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Profile Picture <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control @error('pp_file') is-invalid @enderror" name="pp_file" accept="image/*,.pdf" required>
                                    <small class="text-muted">Accepted: JPG, JPEG, PNG, PDF (Max: 2MB)</small>
                                    @error('pp_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Identification Document <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control @error('id_file') is-invalid @enderror" name="id_file" accept="image/*,.pdf" required>
                                    <small class="text-muted">Accepted: JPG, JPEG, PNG, PDF (Max: 2MB)</small>
                                    @error('id_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 d-flex gap-2">
                            <button type="reset" class="btn btn-danger">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check me-1"></i> Submit to Add Field User
                            </button>
                            <a href="{{ route('admin.settings.field-users') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyResidentialAddress() {
    if (document.getElementById('sameasresidential').checked) {
        document.getElementById('pplot_no').value = document.getElementById('plot_no').value;
        document.getElementById('pvillage').value = document.getElementById('village').value;
        document.getElementById('pparish').value = document.getElementById('parish').value;
        document.getElementById('psubcounty').value = document.getElementById('subcounty').value;
        document.getElementById('pcounty').value = document.getElementById('county').value;
    } else {
        document.getElementById('pplot_no').value = '';
        document.getElementById('pvillage').value = '';
        document.getElementById('pparish').value = '';
        document.getElementById('psubcounty').value = '';
        document.getElementById('pcounty').value = '';
    }
}

function togglePinVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('mdi-eye');
        icon.classList.add('mdi-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('mdi-eye-off');
        icon.classList.add('mdi-eye');
    }
}
</script>
@endpush

@endsection
