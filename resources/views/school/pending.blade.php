<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Pending - EBIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .pending-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            padding: 3rem;
            text-align: center;
        }
        .pending-icon {
            font-size: 5rem;
            color: #ffc107;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .pending-title {
            color: #333;
            font-weight: 700;
            margin: 1.5rem 0;
        }
        .pending-text {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: left;
        }
        .info-box h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .info-box p {
            color: #666;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="pending-card">
        <i class="fas fa-clock pending-icon"></i>
        <h2 class="pending-title">Application Under Review</h2>
        <p class="pending-text">
            Thank you for registering with EBIMS! Your school application is currently being reviewed by our team.
        </p>

        @if($school)
        <div class="info-box">
            <h6><i class="fas fa-school me-2"></i>School Information</h6>
            <p><strong>School Name:</strong> {{ $school->school_name }}</p>
            <p><strong>Registration Status:</strong> 
                <span class="badge bg-warning text-dark">{{ ucfirst($school->status) }}</span>
            </p>
            <p><strong>Submitted:</strong> {{ $school->created_at->format('F d, Y') }}</p>
            @if($school->assessment_complete)
                <p><strong>Assessment:</strong> 
                    <span class="badge bg-success">Completed</span>
                </p>
            @else
                <p><strong>Assessment:</strong> 
                    <span class="badge bg-danger">Incomplete</span>
                </p>
            @endif
        </div>
        @endif

        <div class="alert alert-info mt-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>What's Next?</strong><br>
            <small>Our team will review your application within 1-2 business days. You will receive an email notification once your school has been approved and assigned to a branch.</small>
        </div>

        <div class="mt-4">
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </button>
            </form>
        </div>

        <div class="mt-4 text-muted">
            <small>
                <i class="fas fa-envelope me-2"></i>
                Need help? Contact us at <a href="mailto:support@ebims.com">support@ebims.com</a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
