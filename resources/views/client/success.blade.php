<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - Emuria Micro Finance Limited</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,.12); border: none; max-width: 600px; width: 100%; }
        .card-header { background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%); border-radius: 20px 20px 0 0 !important; padding: 32px; }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 2.5rem; color: white; }
        .code-box { background: #e8eaf6; border: 2px dashed #1a237e; border-radius: 12px; padding: 20px; text-align: center; }
        .code-box .code { font-family: monospace; font-size: 1.5rem; font-weight: 700; color: #1a237e; letter-spacing: 2px; }
        .traffic-light { display: inline-block; width: 18px; height: 18px; border-radius: 50%; vertical-align: middle; margin-right: 6px; }
        .tl-GREEN  { background: #198754; }
        .tl-YELLOW { background: #ffc107; }
        .tl-RED    { background: #dc3545; }
        .tl-null   { background: #6c757d; }
    </style>
</head>
<body class="p-3">
<div class="card mx-auto">
    <div class="card-header text-center text-white">
        <div class="icon-circle">
            @if($app && $app->traffic_light === 'RED')
                <i class="fas fa-times-circle"></i>
            @else
                <i class="fas fa-check-circle"></i>
            @endif
        </div>
        <h3 class="mb-1 fw-700">
            @if($app && $app->traffic_light === 'RED')
                Application Not Approved
            @else
                Application Submitted!
            @endif
        </h3>
        <p class="opacity-85 mb-0">
            @if($app && $app->traffic_light === 'RED')
                Your application did not meet our current lending criteria.
            @else
                Your loan application has been received and is being processed.
            @endif
        </p>
    </div>

    <div class="card-body p-4">

        @if($app)
        <!-- Application Code -->
        <div class="code-box mb-4">
            <div class="text-muted small mb-1">Your Application Reference Code</div>
            <div class="code">{{ $app->application_code }}</div>
            <div class="text-muted small mt-1">Save this code — you may need it when contacting us.</div>
        </div>

        <!-- System Decision -->
        @if($app->traffic_light)
        <div class="alert alert-{{ $app->trafficLightClass() }} d-flex align-items-center">
            <span class="traffic-light tl-{{ $app->traffic_light }}"></span>
            <div>
                @if($app->traffic_light === 'GREEN')
                    <strong>Pre-Assessment: Strong</strong><br>
                    Your application looks strong! A field officer will contact you within <strong>1–2 business days</strong> to confirm your details.
                @elseif($app->traffic_light === 'YELLOW')
                    <strong>Pre-Assessment: Under Review</strong><br>
                    Your application requires additional verification. A field officer will contact you within <strong>2–3 business days</strong>.
                @else
                    <strong>Pre-Assessment: Not Approved</strong><br>
                    {{ $app->system_notes ?? 'Your application did not meet the minimum requirements at this time.' }}
                @endif
            </div>
        </div>
        @endif

        @if($app->traffic_light !== 'RED')
        <!-- What Happens Next -->
        <h6 class="fw-bold mb-3 mt-3">What happens next?</h6>
        <div class="d-flex gap-3 mb-3">
            <div class="text-primary fs-5 pt-1"><i class="fas fa-phone-alt"></i></div>
            <div>
                <strong>1. Field Officer Call</strong><br>
                <small class="text-muted">Our field officer will call you at <strong>{{ $app->phone }}</strong> to verify your application and arrange a visit.</small>
            </div>
        </div>
        <div class="d-flex gap-3 mb-3">
            <div class="text-primary fs-5 pt-1"><i class="fas fa-search"></i></div>
            <div>
                <strong>2. Verification Visit</strong><br>
                <small class="text-muted">The officer will visit your business and residence to confirm the details you provided.</small>
            </div>
        </div>
        <div class="d-flex gap-3 mb-3">
            <div class="text-primary fs-5 pt-1"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <strong>3. Charge Fees</strong><br>
                <small class="text-muted">Once approved, you will be asked to pay applicable processing fees before disbursement.</small>
            </div>
        </div>
        <div class="d-flex gap-3 mb-3">
            <div class="text-primary fs-5 pt-1"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <strong>4. Disbursement</strong><br>
                <small class="text-muted">The loan will be sent to your preferred mobile money account or bank.</small>
            </div>
        </div>
        @endif

        <!-- Loan Details Summary -->
        <div class="bg-light rounded-3 p-3 mt-2">
            <div class="row g-1 small">
                <div class="col-6 text-muted">Requested Amount:</div>
                <div class="col-6 fw-bold">UGX {{ number_format($app->requested_amount) }}</div>
                <div class="col-6 text-muted">Tenure:</div>
                <div class="col-6 fw-bold">{{ $app->tenure_periods }} {{ ucfirst($app->repayment_frequency) }} periods</div>
                <div class="col-6 text-muted">Applicant:</div>
                <div class="col-6 fw-bold">{{ $app->full_name }}</div>
                <div class="col-6 text-muted">Phone:</div>
                <div class="col-6 fw-bold">{{ $app->phone }}</div>
                <div class="col-6 text-muted">Submitted:</div>
                <div class="col-6 fw-bold">{{ $app->created_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        @else
        <div class="text-center py-3 text-muted">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <p>Your application has been submitted successfully.</p>
            <p>Reference Code: <strong class="text-primary">{{ $code }}</strong></p>
        </div>
        @endif

        <!-- Actions -->
        <div class="mt-4">
            @if($app && !in_array($app->status, ['rejected', 'converted']))
            {{-- Application is still active — block reapplication --}}
            <div class="alert alert-warning d-flex gap-2 align-items-start mb-3">
                <i class="fas fa-exclamation-triangle mt-1"></i>
                <div>
                    <strong>You cannot submit a new application yet.</strong><br>
                    Your application <strong>{{ $app->application_code }}</strong> is currently
                    <strong>{{ str_replace('_', ' ', $app->status) }}</strong>.
                    Please wait for our team to contact you before applying again.
                    If you have questions, visit your nearest branch.
                </div>
            </div>
            <a href="{{ route('client.apply') }}" class="btn btn-outline-secondary w-100">
                <i class="fas fa-home me-1"></i> Go to Homepage
            </a>
            @else
            <div class="d-flex gap-2">
                <a href="{{ route('client.apply') }}" class="btn btn-outline-primary flex-fill">
                    <i class="fas fa-plus me-1"></i> New Application
                </a>
                <a href="{{ route('client.apply') }}" class="btn btn-outline-secondary flex-fill">
                    <i class="fas fa-home me-1"></i> Home
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Clear any saved draft for this applicant's phone so "resume" doesn't offer a completed application
@if($app)
try {
    const phone = '{{ $app->phone }}';
    localStorage.removeItem('emuria_loan_draft_' + phone);
    sessionStorage.removeItem('emuria_draft_session');
    sessionStorage.removeItem('emuria_draft_session_phone');
} catch(e) {}
@endif
</script>
</body>
</html>
