<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us as a School - Emuria Micro Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-container {
            padding: 40px 0;
        }
        
        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px 0;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .header-section::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 20px;
            background: white;
            border-radius: 20px 20px 0 0;
        }
        
        .header-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .form-section {
            padding: 40px;
            background: white;
        }
        
        .section-title {
            color: #667eea;
            font-weight: 600;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
            margin-bottom: 25px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .form-control {
            border: 2px solid #f0f2f5;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
            user-select: none;
            font-size: 1.1rem;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .benefit-alert {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: none;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .benefit-list {
            list-style: none;
            padding: 0;
        }
        
        .benefit-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .benefit-list li:last-child {
            border-bottom: none;
        }
        
        .benefit-list li i {
            color: #667eea;
            margin-right: 12px;
            width: 20px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .info-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .process-step {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .step-number {
            background: #667eea;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .header-section,
            .form-section {
                padding: 30px 20px;
            }
            
            .header-icon {
                font-size: 3rem;
            }
            
            .registration-container {
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="main-card">
                        <!-- Header Section -->
                        <div class="header-section">
                            <div class="header-icon">🏫</div>
                            <h1 class="mb-3">Join Us as a School</h1>
                            <p class="lead mb-0">Partner with Emuria Micro Finance for Complete Educational Financial Solutions</p>
                            
                            <div class="info-card">
                                <h6 class="mb-3"><i class="fas fa-star me-2"></i>Why Schools Choose Us</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                                            <p class="mb-0 small">Interest-Free Advances</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                                            <p class="mb-0 small">Financial Dashboard</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="fas fa-users fa-2x mb-2"></i>
                                            <p class="mb-0 small">Staff Loan Management</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Section -->
                        <div class="form-section">
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

                            <form action="{{ route('school.register.store') }}" method="POST">
                                @csrf
                                
                                <!-- School Information -->
                                <div class="row mb-5">
                                    <div class="col-12">
                                        <h5 class="section-title">
                                            <i class="fas fa-school me-2"></i>School Information
                                        </h5>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="school_name" class="form-label required">School Name</label>
                                        <input type="text" class="form-control @error('school_name') is-invalid @enderror" 
                                               id="school_name" name="school_name" 
                                               value="{{ old('school_name') }}"
                                               placeholder="Enter your school name" required>
                                        @error('school_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="school_type" class="form-label required">School Type</label>
                                        <select class="form-control" id="school_type" name="school_type" required>
                                            <option value="">Select School Type</option>
                                            <option value="Primary">Primary School</option>
                                            <option value="Secondary">Secondary School</option>
                                            <option value="Primary & Secondary">Primary & Secondary</option>
                                            <option value="Nursery">Nursery School</option>
                                            <option value="University">University</option>
                                            <option value="College">College</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="ownership" class="form-label required">Ownership Type</label>
                                        <select class="form-control" id="ownership" name="ownership" required>
                                            <option value="">Select Ownership</option>
                                            <option value="Government">Government</option>
                                            <option value="Private">Private</option>
                                            <option value="Religious">Religious Institution</option>
                                            <option value="Community">Community Based</option>
                                            <option value="NGO">NGO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_number" class="form-label">Registration Number</label>
                                        <input type="text" class="form-control" id="registration_number" 
                                               name="registration_number" placeholder="School registration number">
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="row mb-5">
                                    <div class="col-12">
                                        <h5 class="section-title">
                                            <i class="fas fa-address-book me-2"></i>Contact Information
                                        </h5>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_person" class="form-label required">Contact Person</label>
                                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                               placeholder="Mrs. Smith Joan" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_position" class="form-label">Position/Title</label>
                                        <select class="form-control" id="contact_position" name="contact_position">
                                            <option value="">Select Position</option>
                                            <option value="Head Teacher">Head Teacher</option>
                                            <option value="Principal">Principal</option>
                                            <option value="Deputy Head Teacher">Deputy Head Teacher</option>
                                            <option value="Deputy Principal">Deputy Principal</option>
                                            <option value="Director">Director</option>
                                            <option value="School Administrator">School Administrator</option>
                                            <option value="Proprietor">Proprietor</option>
                                            <option value="Owner">Owner</option>
                                            <option value="Chairperson">Chairperson</option>
                                            <option value="Board Member">Board Member</option>
                                            <option value="Bursar">Bursar</option>
                                            <option value="Finance Officer">Finance Officer</option>
                                            <option value="Other">Other (Please specify)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3" id="other_position_wrapper" style="display: none;">
                                        <label for="other_position" class="form-label">Specify Position</label>
                                        <input type="text" class="form-control" id="other_position" name="other_position" 
                                               placeholder="Enter your position/title">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label required">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="school@example.com" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label required">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="+256 XXX XXX XXX" required>
                                    </div>
                                </div>

                                <!-- Location Information -->
                                <div class="row mb-5">
                                    <div class="col-12">
                                        <h5 class="section-title">
                                            <i class="fas fa-map-marker-alt me-2"></i>Location Information
                                        </h5>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="physical_address" class="form-label required">Physical Address</label>
                                        <textarea class="form-control" id="physical_address" name="physical_address" 
                                                  rows="2" placeholder="Enter complete physical address" required></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="district" class="form-label required">District</label>
                                        <input type="text" class="form-control" id="district" name="district" 
                                               placeholder="Enter district name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="sub_county" class="form-label">Sub County</label>
                                        <input type="text" class="form-control" id="sub_county" name="sub_county" 
                                               placeholder="Enter sub county">
                                    </div>
                                </div>

                                <!-- Account Credentials -->
                                <div class="row mb-5">
                                    <div class="col-12">
                                        <h5 class="section-title">
                                            <i class="fas fa-key me-2"></i>Account Credentials
                                        </h5>
                                        <div class="alert alert-info border-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Important:</strong> These credentials will be used to access your school portal after approval.
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="admin_password" class="form-label required">Admin Password</label>
                                        <div class="password-wrapper">
                                            <input type="password" class="form-control" id="admin_password" 
                                                   name="admin_password" required minlength="8" placeholder="Minimum 8 characters">
                                            <i class="fas fa-eye password-toggle" id="toggleAdminPassword"></i>
                                        </div>
                                        <small class="text-muted">Choose a strong password for security</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="admin_password_confirmation" class="form-label required">Confirm Password</label>
                                        <div class="password-wrapper">
                                            <input type="password" class="form-control" id="admin_password_confirmation" 
                                                   name="admin_password_confirmation" required placeholder="Re-enter password">
                                            <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Benefits & Process Information -->
                                <div class="benefit-alert">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-gift me-2"></i>What You Get After Registration
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="benefit-list">
                                                <li><i class="fas fa-hand-holding-usd"></i>Interest-free school advances</li>
                                                <li><i class="fas fa-university"></i>Competitive school loans</li>
                                                <li><i class="fas fa-graduation-cap"></i>Student loan facilitation</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="benefit-list">
                                                <li><i class="fas fa-chart-dashboard"></i>Financial dashboard access</li>
                                                <li><i class="fas fa-users-cog"></i>Staff loan management</li>
                                                <li><i class="fas fa-calculator"></i>Payroll management system</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Registration Process -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-tasks me-2"></i>Registration Process
                                    </h6>
                                    <div class="process-step">
                                        <span class="step-number">1</span>
                                        <strong>Submit Application:</strong> Fill out and submit this registration form
                                    </div>
                                    <div class="process-step">
                                        <span class="step-number">2</span>
                                        <strong>Admin Review:</strong> Our team reviews your application and assigns you to a branch
                                    </div>
                                    <div class="process-step">
                                        <span class="step-number">3</span>
                                        <strong>Approval:</strong> Once approved, you can login with your credentials
                                    </div>
                                    <div class="process-step">
                                        <span class="step-number">4</span>
                                        <strong>Access Portal:</strong> Start using your school portal and financial services
                                    </div>
                                </div>

                                <!-- Assessment Option -->
                                <div class="mb-4">
                                    <div class="alert alert-info border-0">
                                        <h6 class="mb-3">
                                            <i class="fas fa-clipboard-check me-2"></i>Complete Your School Assessment
                                        </h6>
                                        <p class="mb-2">After submitting this basic registration, you can:</p>
                                        <ul class="mb-2">
                                            <li>Complete a comprehensive school assessment form</li>
                                        <ul>
                                            <li>Apply for school loans and advances</li>
                                            <li>Provide detailed financial information</li>
                                            <li>Upload supporting documents</li>
                                        </ul>
                                        <div class="alert alert-warning border-start border-4 border-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Important:</strong> The comprehensive assessment form is <strong>REQUIRED</strong> for all school registrations. 
                                            This helps us evaluate your school for approval and loan eligibility.
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="complete_assessment_now" name="complete_assessment_now" value="1" checked required>
                                            <label class="form-check-label fw-bold text-danger" for="complete_assessment_now">
                                                <i class="fas fa-check-circle me-2"></i>I understand that I must complete the comprehensive assessment form (Required)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="text-center">
                                    <button type="submit" class="submit-btn" id="submitRegBtn">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        <span id="submitBtnText">Continue to Assessment Form</span>
                                    </button>
                                    <p class="text-muted mt-3 mb-0">
                                        <small>Registration is completely free • Processing time: 1-2 business days</small>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Back to Login -->
                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" class="back-link">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        const toggleAdminPassword = document.getElementById('toggleAdminPassword');
        const adminPasswordInput = document.getElementById('admin_password');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('admin_password_confirmation');
        
        // Toggle admin password visibility
        if (toggleAdminPassword && adminPasswordInput) {
            toggleAdminPassword.addEventListener('click', function() {
                const type = adminPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                adminPasswordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
        
        // Toggle confirm password visibility
        if (toggleConfirmPassword && confirmPasswordInput) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    
        // Password confirmation validation
        const password = document.getElementById('admin_password');
        const confirmPassword = document.getElementById('admin_password_confirmation');

        function validatePassword() {
            if (password.value != confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
                confirmPassword.classList.add('is-invalid');
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('is-invalid');
            }
        }

        password.onchange = validatePassword;
        confirmPassword.onkeyup = validatePassword;

        // Position dropdown - Show/hide "Other" input field
        const contactPositionSelect = document.getElementById('contact_position');
        const otherPositionWrapper = document.getElementById('other_position_wrapper');
        const otherPositionInput = document.getElementById('other_position');
        
        if (contactPositionSelect && otherPositionWrapper) {
            contactPositionSelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    otherPositionWrapper.style.display = 'block';
                    otherPositionInput.setAttribute('required', 'required');
                } else {
                    otherPositionWrapper.style.display = 'none';
                    otherPositionInput.removeAttribute('required');
                    otherPositionInput.value = '';
                }
            });
        }

        // Form validation feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>