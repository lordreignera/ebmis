<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Complete School Assessment - EBIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .complete-assessment-container {
            max-width: 600px;
            width: 100%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header-section i {
            font-size: 60px;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .header-section h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header-section p {
            margin: 10px 0 0;
            opacity: 0.95;
            font-size: 16px;
        }

        .form-section {
            padding: 40px 30px;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .info-box i {
            color: #2196F3;
            margin-right: 10px;
        }

        .info-box ul {
            margin: 10px 0 0;
            padding-left: 25px;
        }

        .info-box li {
            margin: 5px 0;
            color: #555;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .submit-btn i {
            margin-left: 8px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #764ba2;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-size: 15px;
            line-height: 1.6;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
        }

        .alert-warning {
            background: #fff3e0;
            color: #ef6c00;
            border-left: 4px solid #ff9800;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
        }

        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        .alert strong {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .contact-info {
            display: inline-block;
            background: rgba(0,0,0,0.05);
            padding: 5px 12px;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .complete-assessment-container {
                margin: 20px;
            }

            .header-section {
                padding: 30px 20px;
            }

            .form-section {
                padding: 30px 20px;
            }

            .header-section h2 {
                font-size: 24px;
            }

            .header-section i {
                font-size: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="complete-assessment-container">
        <!-- Header -->
        <div class="header-section">
            <i class="fas fa-clipboard-check"></i>
            <h2>Complete Your Assessment</h2>
            <p>Enter your school email to continue</p>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <!-- Display Messages -->
            @if (session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Assessment Already Completed!</strong>
                        {{ session('warning') }}
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    {{ session('info') }}
                </div>
            @endif

            <!-- Info Box -->
            <div class="info-box">
                <h6 class="mb-2"><i class="fas fa-info-circle"></i>Resume Your Assessment</h6>
                <p class="mb-2">If you started registering your school but didn't complete the assessment, you can continue here:</p>
                <ul class="mb-0">
                    <li>Enter the email you used during registration</li>
                    <li>You'll be taken to your incomplete assessment</li>
                    <li>Complete all required sections to submit</li>
                    <li>Your school will be reviewed after completion</li>
                </ul>
            </div>

            <!-- Email Form -->
            <form action="{{ route('school.continue-assessment') }}" method="POST" id="continueForm">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>School Email Address
                    </label>
                    <input type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           id="email" 
                           name="email" 
                           placeholder="Enter your registered school email"
                           value="{{ old('email') }}"
                           required
                           autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>Use the same email you provided during initial registration
                    </small>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span id="btnText">Continue to Assessment</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Back to Login Link -->
            <div class="back-link">
                <a href="{{ route('login') }}">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
                <span class="mx-2">|</span>
                <a href="{{ route('school.register') }}">
                    <i class="fas fa-plus-circle me-2"></i>New Registration
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('continueForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            
            submitBtn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
        });
    </script>
</body>
</html>
