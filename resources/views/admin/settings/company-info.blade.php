@extends('layouts.admin')

@section('content')
@push('styles')
<style>
    .modal-content { background-color: #ffffff !important; }
    .modal-body { background-color: #ffffff !important; color: #000000 !important; }
    .modal-header { border-bottom: 1px solid #dee2e6; }
    .modal-footer { border-top: 1px solid #dee2e6; }
    label { color: #000000 !important; }
    input, select, textarea { background-color: #ffffff !important; color: #000000 !important; }
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        color: #666;
    }
    .info-value {
        color: #333;
        text-align: right;
    }
</style>
@endpush

<div class="main-panel">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Company Information</h3>
                        <h6 class="font-weight-normal mb-0">Manage your company details and settings</h6>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="justify-content-end d-flex">
                            <button type="button" class="btn btn-primary" id="editCompanyBtn">
                                <i class="mdi mdi-pencil"></i> Edit Information
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company Information Card -->
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4 class="mb-4">Basic Information</h4>
                    <div class="info-row">
                        <span class="info-label">Company Name:</span>
                        <span class="info-value" id="display_company_name">EBIMS System</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Registration Number:</span>
                        <span class="info-value" id="display_registration">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tax ID:</span>
                        <span class="info-value" id="display_tax_id">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Industry:</span>
                        <span class="info-value" id="display_industry">Financial Services</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-card">
                    <h4 class="mb-4">Contact Information</h4>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value" id="display_email">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value" id="display_phone">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Website:</span>
                        <span class="info-value" id="display_website">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value" id="display_address">Not Set</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4 class="mb-4">System Settings</h4>
                    <div class="info-row">
                        <span class="info-label">Currency:</span>
                        <span class="info-value" id="display_currency">KES</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Timezone:</span>
                        <span class="info-value" id="display_timezone">Africa/Nairobi</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Format:</span>
                        <span class="info-value" id="display_date_format">DD/MM/YYYY</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-card">
                    <h4 class="mb-4">Compliance</h4>
                    <div class="info-row">
                        <span class="info-label">License Number:</span>
                        <span class="info-value" id="display_license">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Regulatory Body:</span>
                        <span class="info-value" id="display_regulatory">Not Set</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">License Expiry:</span>
                        <span class="info-value" id="display_license_expiry">Not Set</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Company Info Modal -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="editCompanyModalLabel">Edit Company Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCompanyForm">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3" style="color: #000;">Basic Information</h5>
                            <div class="form-group mb-3">
                                <label for="company_name" class="form-label" style="color: #000;">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" required style="background-color: white; color: #000;">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="registration" class="form-label" style="color: #000;">Registration Number</label>
                                <input type="text" class="form-control" id="registration" name="registration" style="background-color: white; color: #000;">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="tax_id" class="form-label" style="color: #000;">Tax ID</label>
                                <input type="text" class="form-control" id="tax_id" name="tax_id" style="background-color: white; color: #000;">
                            </div>

                            <div class="form-group mb-3">
                                <label for="industry" class="form-label" style="color: #000;">Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry" style="background-color: white; color: #000;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3" style="color: #000;">Contact Information</h5>
                            <div class="form-group mb-3">
                                <label for="email" class="form-label" style="color: #000;">Email</label>
                                <input type="email" class="form-control" id="email" name="email" style="background-color: white; color: #000;">
                            </div>

                            <div class="form-group mb-3">
                                <label for="phone" class="form-label" style="color: #000;">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" style="background-color: white; color: #000;">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="website" class="form-label" style="color: #000;">Website</label>
                                <input type="url" class="form-control" id="website" name="website" style="background-color: white; color: #000;">
                            </div>

                            <div class="form-group mb-3">
                                <label for="address" class="form-label" style="color: #000;">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" style="background-color: white; color: #000;"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5 class="mb-3" style="color: #000;">System Settings</h5>
                            <div class="form-group mb-3">
                                <label for="currency" class="form-label" style="color: #000;">Currency</label>
                                <select class="form-control" id="currency" name="currency" style="background-color: white; color: #000;">
                                    <option value="KES">KES - Kenyan Shilling</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="timezone" class="form-label" style="color: #000;">Timezone</label>
                                <select class="form-control" id="timezone" name="timezone" style="background-color: white; color: #000;">
                                    <option value="Africa/Nairobi">Africa/Nairobi</option>
                                    <option value="Africa/Lagos">Africa/Lagos</option>
                                    <option value="Africa/Johannesburg">Africa/Johannesburg</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="date_format" class="form-label" style="color: #000;">Date Format</label>
                                <select class="form-control" id="date_format" name="date_format" style="background-color: white; color: #000;">
                                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3" style="color: #000;">Compliance</h5>
                            <div class="form-group mb-3">
                                <label for="license" class="form-label" style="color: #000;">License Number</label>
                                <input type="text" class="form-control" id="license" name="license" style="background-color: white; color: #000;">
                            </div>

                            <div class="form-group mb-3">
                                <label for="regulatory" class="form-label" style="color: #000;">Regulatory Body</label>
                                <input type="text" class="form-control" id="regulatory" name="regulatory" style="background-color: white; color: #000;">
                            </div>

                            <div class="form-group mb-3">
                                <label for="license_expiry" class="form-label" style="color: #000;">License Expiry</label>
                                <input type="date" class="form-control" id="license_expiry" name="license_expiry" style="background-color: white; color: #000;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
console.log('Company info script loading...');

setTimeout(function() {
    console.log('Initializing company info management...');
    
    // Load company info from localStorage (temporary solution)
    const companyInfo = JSON.parse(localStorage.getItem('companyInfo')) || {
        company_name: 'EBIMS System',
        registration: 'Not Set',
        tax_id: 'Not Set',
        industry: 'Financial Services',
        email: 'Not Set',
        phone: 'Not Set',
        website: 'Not Set',
        address: 'Not Set',
        currency: 'KES',
        timezone: 'Africa/Nairobi',
        date_format: 'DD/MM/YYYY',
        license: 'Not Set',
        regulatory: 'Not Set',
        license_expiry: 'Not Set'
    };

    // Display company info
    function displayCompanyInfo(info) {
        document.getElementById('display_company_name').textContent = info.company_name || 'Not Set';
        document.getElementById('display_registration').textContent = info.registration || 'Not Set';
        document.getElementById('display_tax_id').textContent = info.tax_id || 'Not Set';
        document.getElementById('display_industry').textContent = info.industry || 'Not Set';
        document.getElementById('display_email').textContent = info.email || 'Not Set';
        document.getElementById('display_phone').textContent = info.phone || 'Not Set';
        document.getElementById('display_website').textContent = info.website || 'Not Set';
        document.getElementById('display_address').textContent = info.address || 'Not Set';
        document.getElementById('display_currency').textContent = info.currency || 'KES';
        document.getElementById('display_timezone').textContent = info.timezone || 'Africa/Nairobi';
        document.getElementById('display_date_format').textContent = info.date_format || 'DD/MM/YYYY';
        document.getElementById('display_license').textContent = info.license || 'Not Set';
        document.getElementById('display_regulatory').textContent = info.regulatory || 'Not Set';
        document.getElementById('display_license_expiry').textContent = info.license_expiry || 'Not Set';
    }

    displayCompanyInfo(companyInfo);

    // Edit button
    const editBtn = document.getElementById('editCompanyBtn');
    if (editBtn) {
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Populate form with current values
            document.getElementById('company_name').value = companyInfo.company_name || '';
            document.getElementById('registration').value = companyInfo.registration === 'Not Set' ? '' : companyInfo.registration;
            document.getElementById('tax_id').value = companyInfo.tax_id === 'Not Set' ? '' : companyInfo.tax_id;
            document.getElementById('industry').value = companyInfo.industry || '';
            document.getElementById('email').value = companyInfo.email === 'Not Set' ? '' : companyInfo.email;
            document.getElementById('phone').value = companyInfo.phone === 'Not Set' ? '' : companyInfo.phone;
            document.getElementById('website').value = companyInfo.website === 'Not Set' ? '' : companyInfo.website;
            document.getElementById('address').value = companyInfo.address === 'Not Set' ? '' : companyInfo.address;
            document.getElementById('currency').value = companyInfo.currency || 'KES';
            document.getElementById('timezone').value = companyInfo.timezone || 'Africa/Nairobi';
            document.getElementById('date_format').value = companyInfo.date_format || 'DD/MM/YYYY';
            document.getElementById('license').value = companyInfo.license === 'Not Set' ? '' : companyInfo.license;
            document.getElementById('regulatory').value = companyInfo.regulatory === 'Not Set' ? '' : companyInfo.regulatory;
            document.getElementById('license_expiry').value = companyInfo.license_expiry === 'Not Set' ? '' : companyInfo.license_expiry;
            
            new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
        });
    }

    // Edit form submission
    const editForm = document.getElementById('editCompanyForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value || 'Not Set';
            });
            
            // Save to localStorage (temporary solution - in production, save to database)
            localStorage.setItem('companyInfo', JSON.stringify(data));
            
            // Update display
            displayCompanyInfo(data);
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editCompanyModal')).hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Company information updated successfully!',
                showConfirmButton: false,
                timer: 1500
            });
        });
    }
}, 500);
</script>
@endpush
