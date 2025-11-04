<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emuria Micro Finance Limited - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-left img {
            max-width: 200px;
            height: auto;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
        }

        .login-left h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .login-left .subtitle {
            font-size: 1.1rem;
            margin-bottom: 10px;
            opacity: 0.95;
        }

        .login-left p {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.85;
            margin-bottom: 30px;
        }

        .login-left .features {
            margin-top: 40px;
            width: 100%;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            text-align: left;
        }

        .feature-item svg {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .login-right {
            flex: 1;
            padding: 60px 50px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
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
        }
        
        .password-toggle:hover {
            color: #1a237e;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a237e;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }

        .error-message {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .success-message {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            width: auto;
            margin-right: 8px;
            cursor: pointer;
        }

        .remember-me label {
            color: #666;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .forgot-password {
            color: #1a237e;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #0d47a1;
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 35, 126, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                padding: 40px 30px;
            }

            .login-left h1 {
                font-size: 1.5rem;
            }

            .login-right {
                padding: 40px 30px;
            }
        }

        /* Smooth fade-in animation for content */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-left > div {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div>
                <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="Emuria Micro Finance Limited">
                <h1>Emuria Micro Finance Limited</h1>
                <p class="subtitle">Banking & Investment Management System</p>
                <p>Streamline your microfinance operations, manage loans, track savings, and empower your community with our comprehensive financial platform.</p>
                
                <div class="features">
                    <div class="feature-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Secure & Reliable Platform</span>
                    </div>
                    <div class="feature-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Real-time Processing</span>
                    </div>
                    <div class="feature-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Dedicated Support Team</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please login to your account</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    <ul style="list-style: none;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @session('status')
                <div class="success-message">
                    {{ $value }}
                </div>
            @endsession

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Success!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember">
                        <label for="remember_me">Remember me</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-password">Forgot Password?</a>
                    @endif
                </div>

                <button type="submit" class="login-button">
                    Login
                </button>
                
                <!-- School Registration Link -->
                <div class="text-center mt-4">
                    <div class="divider mb-3">
                        <span class="divider-text">New to our platform?</span>
                    </div>
                    <button type="button" class="btn btn-school-join w-100" data-bs-toggle="modal" data-bs-target="#schoolBenefitsModal">
                        🏫 Join us as a School
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- School Benefits Modal -->
    <div class="modal fade" id="schoolBenefitsModal" tabindex="-1" aria-labelledby="schoolBenefitsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient text-white border-0">
                    <h5 class="modal-title" id="schoolBenefitsModalLabel">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Join Emuria Micro Finance - School Partnership Program
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <div class="school-icon mb-3">
                                🎓
                            </div>
                            <h4 class="text-primary mb-2">Transform Your School's Financial Management</h4>
                            <p class="text-muted">Join hundreds of schools already benefiting from our comprehensive financial platform</p>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="benefit-card">
                                    <div class="benefit-icon">💰</div>
                                    <h6>School Advances</h6>
                                    <p>Get instant school advances with <strong>NO INTEREST</strong> for operational expenses and urgent needs</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-card">
                                    <div class="benefit-icon">🏦</div>
                                    <h6>School Loans</h6>
                                    <p>Access competitive school loans for infrastructure development and growth projects</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-card">
                                    <div class="benefit-icon">🎒</div>
                                    <h6>Student Loans</h6>
                                    <p>Help your students access education loans with flexible repayment terms</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-card">
                                    <div class="benefit-icon">📊</div>
                                    <h6>Financial Dashboard</h6>
                                    <p>Comprehensive school financial dashboard with real-time reporting and analytics</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-card">
                                    <div class="benefit-icon">👥</div>
                                    <h6>Staff Loans</h6>
                                    <p>Provide your staff with easy access to personal loans and salary advances</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-card">
                                    <div class="benefit-icon">💼</div>
                                    <h6>Payroll Management</h6>
                                    <p>Automated payroll processing, salary calculations, and staff management system</p>
                                </div>
                            </div>
                        </div>

                        <div class="additional-benefits mt-4 p-3 bg-light rounded">
                            <h6 class="text-primary mb-3">Plus Many More Benefits:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li><i class="fas fa-check text-success me-2"></i>Fee Management System</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Student Registration Portal</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Academic Records Management</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li><i class="fas fa-check text-success me-2"></i>Staff Management System</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Financial Reporting Tools</li>
                                        <li><i class="fas fa-check text-success me-2"></i>24/7 Customer Support</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <div class="w-100 text-center">
                        <a href="{{ route('school.register') }}" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-pen-fancy me-2"></i>Register Your School Now
                        </a>
                        <p class="text-muted mt-2 mb-0 small">Registration is free and takes less than 5 minutes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>

    <style>
        .divider {
            position: relative;
            text-align: center;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider-text {
            background: white;
            padding: 0 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .btn-school-join {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-school-join:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .school-icon {
            font-size: 4rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }
        
        .benefit-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .benefit-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
            transform: translateY(-3px);
        }
        
        .benefit-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .benefit-card h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .benefit-card p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }
        
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .additional-benefits {
            border-left: 4px solid #667eea;
        }
        
        @media (max-width: 768px) {
            .benefit-card {
                margin-bottom: 20px;
            }
            
            .school-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Content here -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Password Toggle Script -->
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>
