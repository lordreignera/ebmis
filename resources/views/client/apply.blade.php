<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Apply for a Loan - Emuria Micro Finance Limited</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .page-header {
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: white;
            padding: 28px 0 16px;
        }
        .page-header img { max-height: 52px; background: white; padding: 6px 12px; border-radius: 10px; }
        .page-header h2 { font-weight: 700; font-size: 1.5rem; }

        /* Step wizard */
        .step-wizard { display: flex; justify-content: center; gap: 0; margin: 22px 0 8px; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; }
        .step-item { display: flex; flex-direction: column; align-items: center; width: 64px; flex-shrink: 0; position: relative; }
        .step-item + .step-item::before {
            content: ''; position: absolute; top: 17px; right: 50%; width: 100%;
            height: 2px; background: #dee2e6; z-index: 0;
        }
        .step-circle {
            width: 34px; height: 34px; border-radius: 50%; border: 2px solid #dee2e6;
            background: white; display: flex; align-items: center; justify-content: center;
            font-size: .78rem; font-weight: 700; color: #6c757d; z-index: 1;
            position: relative; transition: all .25s;
        }
        .step-item.active .step-circle  { background: #0d47a1; border-color: #0d47a1; color: white; }
        .step-item.done .step-circle    { background: #198754; border-color: #198754; color: white; }
        .step-label { font-size: .58rem; color: rgba(255,255,255,.75); margin-top: 3px; text-align: center; line-height: 1.2; }
        .step-item.active .step-label { color: white; font-weight: 600; }

        /* Step counter badge on mobile */
        .step-counter { display: none; color: rgba(255,255,255,.85); font-size: .78rem; text-align: center; margin-bottom: 6px; }

        /* Form sections */
        .form-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 28px; margin-bottom: 20px; }
        .form-card .section-title { font-weight: 700; color: #1a237e; border-bottom: 2px solid #e8eaf6; padding-bottom: 10px; margin-bottom: 20px; font-size: 1rem; }
        .section-icon { background: #e8eaf6; color: #1a237e; border-radius: 50%; width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; }

        .step-section { display: none; }
        .step-section.active { display: block; }

        .btn-primary { background: #0d47a1; border-color: #0d47a1; }
        .btn-primary:hover { background: #1565c0; border-color: #1565c0; }
        .btn-next, .btn-back { min-width: 110px; }

        .traffic-preview { border-radius: 10px; padding: 14px 20px; font-weight: 600; }
        .required-star { color: #dc3545; }

        /* ── Landing page ──────────────────────────────────────── */
        .landing-hero {
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: white; padding: 56px 0 48px; text-align: center;
        }
        .landing-hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: .5rem; }
        .landing-hero p  { font-size: 1.05rem; opacity: .88; max-width: 560px; margin: 0 auto 28px; }
        .hero-badge { display: inline-block; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
            border-radius: 50px; padding: 4px 16px; font-size: .8rem; margin-bottom: 16px; }
        .btn-start { background: #ffb300; color: #1a237e; font-weight: 700; font-size: 1.05rem;
            padding: 14px 36px; border-radius: 50px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,.2);
            transition: transform .15s, box-shadow .15s; }
        .btn-start:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.25); color: #1a237e; }
        .steps-strip { background: rgba(255,255,255,.1); border-top: 1px solid rgba(255,255,255,.15);
            padding: 14px 0; margin-top: 36px; }
        .steps-strip .step-pill { display: inline-flex; align-items: center; gap: 6px;
            font-size: .78rem; opacity: .9; margin: 2px 8px; }
        .steps-strip .step-pill .pill-num { background: rgba(255,255,255,.25); border-radius: 50%;
            width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .72rem; }

        /* Product cards */
        .product-card { border: 2px solid #e8eaf6; border-radius: 16px; background: white;
            padding: 22px 20px; cursor: pointer; transition: border-color .2s, box-shadow .2s;
            height: 100%; }
        .product-card:hover { border-color: #0d47a1; box-shadow: 0 4px 20px rgba(13,71,161,.12); }
        .product-card .freq-badge { font-size: .7rem; font-weight: 700; border-radius: 50px;
            padding: 2px 10px; text-transform: uppercase; letter-spacing: .04em; }
        .product-card .prod-name { font-size: .95rem; font-weight: 700; color: #1a237e; margin: 10px 0 4px; }
        .product-card .prod-max { font-size: 1.25rem; font-weight: 800; color: #0d47a1; }
        .product-card .prod-rate { font-size: .82rem; color: #555; }
        .product-card .prod-apply { margin-top: 14px; font-size: .82rem; color: #0d47a1; font-weight: 600; }
        .freq-weekly  { background: #e3f2fd; color: #0d47a1; }
        .freq-monthly { background: #e8f5e9; color: #1b5e20; }
        .freq-daily   { background: #fff3e0; color: #e65100; }

        /* Why choose us */
        .why-card { text-align: center; padding: 20px 12px; }
        .why-icon { width: 56px; height: 56px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; margin: 0 auto 12px;
            font-size: 1.4rem; }

        /* ── Mobile Responsive ─────────────────────────────────── */
        @media (max-width: 576px) {
            .page-header { padding: 16px 0 10px; }
            .page-header img { max-height: 40px; padding: 5px 10px; }
            .page-header h2 { font-size: 1.1rem; }
            .page-header .small { font-size: .72rem; }

            /* Compact wizard: show only dots, no labels under 420px */
            .step-item { width: 40px; }
            .step-circle { width: 28px; height: 28px; font-size: .68rem; }
            .step-item + .step-item::before { top: 14px; }
            .step-label { font-size: .5rem; }

            /* Show "Step X of 8" badge instead */
            .step-counter { display: block; }

            .form-card { padding: 18px 14px; border-radius: 12px; }

            /* Stack all columns to full-width on mobile */
            .row.g-3 > [class*="col-md-"],
            .row.g-3 > [class*="col-lg-"] { width: 100%; }

            /* Bigger tap targets */
            .form-control, .form-select { font-size: .9rem; padding: .5rem .75rem; min-height: 44px; }
            .form-check-input { width: 1.1em; height: 1.1em; }
            .form-check-label { font-size: .88rem; }

            /* Full-width nav buttons */
            .d-flex.justify-content-between.align-items-center { flex-direction: column-reverse; gap: 10px; }
            .btn-back, .btn-next, #btnSubmit { width: 100%; min-width: 0; }
            .ms-auto.d-flex { width: 100%; margin-left: 0 !important; }
            .ms-auto.d-flex .btn { flex: 1; }

            /* Reduce container padding */
            .container.py-4 { padding-left: 10px !important; padding-right: 10px !important; }
        }

        @media (max-width: 768px) {
            /* On tablets, 3-col becomes 2-col */
            .row.g-3 > .col-md-4 { width: 50%; }
            .row.g-3 > .col-md-6 { width: 100%; }
        }
    </style>
</head>
<body>

<!-- Header (shown only during form, hidden on landing) -->
<div class="page-header" id="formHeader" style="display:none">
    <div class="container">
        <div class="d-flex align-items-center gap-3 mb-3">
            <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS" onerror="this.style.display='none'">
            <div>
                <div class="opacity-75 small">Emuria Micro Finance Limited</div>
                <h2 class="mb-0">Self-Service Loan Application</h2>
                <div class="opacity-85 small mt-1">Fill in the form below. Our team will contact you within 1–2 business days.</div>
            </div>
        </div>

        <!-- Step wizard  -->
        <div class="step-counter" id="stepCounter">Step 1 of 8 — Personal Info</div>
        <div class="step-wizard" id="stepWizard">
            @foreach([
                'Personal Info','Loan Details','Residence','Business',
                'Financials','Collateral','Guarantors','Declarations'
            ] as $i => $label)
            <div class="step-item {{ $i === 0 ? 'active' : '' }}" id="wizard-step-{{ $i }}">
                <div class="step-circle">{{ $i + 1 }}</div>
                <div class="step-label">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        <!-- Draft save indicator -->
        <div id="draftSaveIndicator" style="opacity:0; transition:opacity .4s; font-size:.78rem; color:rgba(255,255,255,.85); margin-top:6px; text-align:right">
            <i class="fas fa-check-circle me-1"></i>Draft saved
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     LANDING PAGE (Step 0 — Welcome)
═══════════════════════════════════════════════════════════ -->
<div id="landingPage">

    <!-- Hero -->
    <div class="landing-hero">
        <div class="container">
            <div class="d-flex justify-content-center mb-4">
                <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="Emuria"
                     style="max-height:64px; background:white; padding:8px 16px; border-radius:12px;"
                     onerror="this.style.display='none'">
            </div>
            <div class="hero-badge"><i class="fas fa-shield-alt me-1"></i> Trusted · Fast · Transparent</div>
            <h1>Need a Personal Loan?<br>We've Got You Covered.</h1>
            <p>Apply in minutes. No account needed. Our team will contact you within 1–2 business days after reviewing your application.</p>
            <button class="btn btn-start" onclick="startApplication()">
                <i class="fas fa-file-alt me-2"></i>Start My Application
            </button>
        </div>

        <!-- Step strip -->
        <div class="steps-strip">
            <div class="container text-center">
                @foreach(['Personal Info','Loan Details','Residence','Business','Financials','Collateral','Guarantors','Declarations'] as $n => $lbl)
                <span class="step-pill">
                    <span class="pill-num">{{ $n+1 }}</span> {{ $lbl }}
                    @if($n < 7)<i class="fas fa-chevron-right" style="font-size:.55rem;opacity:.5"></i>@endif
                </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Product Cards -->
    <div class="container py-5" style="max-width:1000px">
        <div class="text-center mb-4">
            <h3 style="color:#1a237e; font-weight:800">Our Personal Loan Products</h3>
            <p class="text-muted">Choose the product that suits you — your selection will be pre-filled in the form.</p>
        </div>

        <div class="row g-3 justify-content-center">
            @php
            $freqMap = [1 => ['label'=>'Weekly','class'=>'freq-weekly'], 2 => ['label'=>'Monthly','class'=>'freq-monthly'], 3 => ['label'=>'Daily','class'=>'freq-daily']];
            @endphp
            @foreach($products as $p)
            @php $fm = $freqMap[$p->period_type] ?? ['label'=>'Flexible','class'=>'freq-weekly']; @endphp
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card" onclick="selectProductAndStart({{ $p->id }})">
                    <span class="freq-badge {{ $fm['class'] }}">{{ $fm['label'] }}</span>
                    <div class="prod-name">{{ $p->name }}</div>
                    <div class="prod-max">UGX {{ number_format($p->max_amt) }}</div>
                    <div class="prod-rate"><i class="fas fa-percent me-1" style="font-size:.7rem"></i>{{ number_format($p->interest,1) }}% per period</div>
                    <div class="prod-apply"><i class="fas fa-arrow-right me-1"></i>Apply now</div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Why choose us strip -->
        <div class="row g-3 mt-4 pt-2 border-top">
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="why-icon" style="background:#e3f2fd; color:#0d47a1"><i class="fas fa-bolt"></i></div>
                    <div class="fw-700" style="font-size:.9rem; color:#1a237e; font-weight:700">Fast Processing</div>
                    <div class="text-muted" style="font-size:.78rem">Decision within 1–2 business days</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="why-icon" style="background:#e8f5e9; color:#1b5e20"><i class="fas fa-lock"></i></div>
                    <div class="fw-700" style="font-size:.9rem; color:#1a237e; font-weight:700">Secure & Private</div>
                    <div class="text-muted" style="font-size:.78rem">Your data is protected and confidential</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="why-icon" style="background:#fff3e0; color:#e65100"><i class="fas fa-mobile-alt"></i></div>
                    <div class="fw-700" style="font-size:.9rem; color:#1a237e; font-weight:700">Mobile Money</div>
                    <div class="text-muted" style="font-size:.78rem">Receive funds via MTN or Airtel</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="why-icon" style="background:#fce4ec; color:#c62828"><i class="fas fa-headset"></i></div>
                    <div class="fw-700" style="font-size:.9rem; color:#1a237e; font-weight:700">Dedicated Support</div>
                    <div class="text-muted" style="font-size:.78rem">A field officer guides you through</div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <button class="btn btn-primary btn-lg px-5" onclick="startApplication()" style="border-radius:50px">
                <i class="fas fa-file-alt me-2"></i>Start My Application
            </button>
            <div class="text-muted small mt-2">Takes about 10–15 minutes. No account required.</div>
        </div>

        <!-- ── Resume Application ──────────────────────────────────── -->
        <div class="mt-5 pt-4 border-top">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center" style="background:#f0f4ff">
                        <div style="font-size:2rem; color:#1a237e; margin-bottom:.5rem"><i class="fas fa-history"></i></div>
                        <h5 class="fw-bold mb-1" style="color:#1a237e">Resuming an application?</h5>
                        <p class="text-muted small mb-3">Enter your phone number to continue where you left off. Your previously saved information will be restored automatically.</p>
                        <div class="d-flex gap-2">
                            <input type="tel" id="resumePhoneInput" class="form-control" placeholder="e.g. 0772 123 456" style="border-radius:50px; padding-left:1rem">
                            <button class="btn btn-primary px-4" id="resumeBtn" onclick="resumeFromLanding()" style="border-radius:50px; white-space:nowrap">
                                <i class="fas fa-arrow-right me-1"></i>Continue
                            </button>
                        </div>
                        {{-- Shown while checking --}}
                        <div id="resumeChecking" class="text-muted small mt-3" style="display:none">
                            <i class="fas fa-spinner fa-spin me-1"></i>Checking...
                        </div>
                        {{-- Pending application found --}}
                        <div id="resumePending" class="alert alert-info mt-3 mb-0 text-start py-3" style="display:none; border-radius:12px">
                            <div class="fw-bold mb-1"><i class="fas fa-clock me-1"></i>Application Already Submitted</div>
                            <div class="small mb-2">Your application <strong id="resumePendingCode"></strong> was submitted on <strong id="resumePendingDate"></strong> and is currently <strong id="resumePendingStatus"></strong>.</div>
                            <div class="small text-muted">Our team will contact you soon. Please <strong>do not submit a new application</strong> &mdash; it will be blocked. If you need help, visit your nearest branch.</div>
                        </div>
                        {{-- No draft & no pending app --}}
                        <div id="resumeNotFound" class="alert alert-warning mt-3 mb-0 py-2 small" style="display:none; border-radius:12px">
                            <i class="fas fa-exclamation-triangle me-1"></i>No saved draft found for that number. <a href="#" onclick="startApplication();return false">Start a new application</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Check Loan Status (Existing Members) ────────────────── -->
        <div class="mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4" style="background:#f0fdf4">
                        <div class="text-center mb-3">
                            <div style="font-size:2rem; color:#15803d; margin-bottom:.5rem"><i class="fas fa-search-dollar"></i></div>
                            <h5 class="fw-bold mb-1" style="color:#14532d">Already a Member? Check Your Loan Status</h5>
                            <p class="text-muted small mb-0">Enter the phone number registered on your account to view your active loan and repayment schedule.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="tel" id="lsPhoneInput" class="form-control" placeholder="e.g. 0772 123 456"
                                   style="border-radius:50px; padding-left:1rem"
                                   onkeydown="if(event.key==='Enter'){checkLoanStatus();return false;}">
                            <button class="btn btn-success px-4" id="lsBtn" onclick="checkLoanStatus()" style="border-radius:50px; white-space:nowrap">
                                <i class="fas fa-search me-1"></i>Check Status
                            </button>
                        </div>

                        {{-- Spinner --}}
                        <div id="lsChecking" class="text-muted small text-center mt-3" style="display:none">
                            <i class="fas fa-spinner fa-spin me-1"></i>Looking up your loan…
                        </div>

                        {{-- Not found --}}
                        <div id="lsNotFound" class="alert alert-warning mt-3 mb-0 py-2 small" style="display:none; border-radius:12px">
                            <i class="fas fa-info-circle me-1"></i><span id="lsNotFoundMsg">No active loan found for that phone number.</span>
                        </div>

                        {{-- Result panel --}}
                        <div id="lsResult" style="display:none" class="mt-3">
                            {{-- Loan summary --}}
                            <div class="rounded-3 p-3 mb-3" style="background:white; border:1px solid #d1fae5">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <div class="fw-bold" style="color:#14532d; font-size:1.05rem"><i class="fas fa-user-circle me-1"></i><span id="lsMemberName"></span></div>
                                        <div class="text-muted small mt-1">Loan: <strong id="lsLoanCode"></strong> &nbsp;|&nbsp; <span id="lsProduct"></span></div>
                                    </div>
                                    <span id="lsStatusBadge" class="badge fs-6 px-3 py-2"></span>
                                </div>
                                <hr class="my-2">
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <div class="small text-muted">Principal</div>
                                        <div class="fw-bold" style="color:#1a237e">UGX&nbsp;<span id="lsPrincipal"></span></div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Total Paid</div>
                                        <div class="fw-bold text-success">UGX&nbsp;<span id="lsTotalPaid"></span></div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Outstanding</div>
                                        <div class="fw-bold text-danger">UGX&nbsp;<span id="lsOutstanding"></span></div>
                                    </div>
                                </div>
                                <div id="lsOverdueAlert" class="alert alert-danger py-1 px-2 small mt-2 mb-0" style="display:none; border-radius:8px">
                                    <i class="fas fa-exclamation-triangle me-1"></i>You have <strong id="lsOverdueCount"></strong> overdue installment(s). Please visit your branch to avoid penalties.
                                </div>
                                <div id="lsNextDue" class="alert alert-info py-1 px-2 small mt-2 mb-0" style="display:none; border-radius:8px">
                                    <i class="fas fa-calendar-alt me-1"></i>Next payment: <strong id="lsNextDueDate"></strong> — UGX <strong id="lsNextDueAmt"></strong>
                                </div>
                            </div>

                            {{-- Schedule table --}}
                            <div class="fw-bold small mb-2" style="color:#14532d"><i class="fas fa-table me-1"></i>Repayment Schedule</div>
                            <div style="overflow-x:auto; border-radius:10px; border:1px solid #d1fae5">
                                <table class="table table-sm mb-0" style="font-size:.8rem">
                                    <thead style="background:#d1fae5; color:#14532d">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Due Date</th>
                                            <th class="text-end">Installment</th>
                                            <th class="text-end">Paid</th>
                                            <th class="text-end">Balance</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lsScheduleBody"></tbody>
                                </table>
                            </div>
                            <div class="text-muted text-center small mt-2">
                                <i class="fas fa-shield-alt me-1"></i>Amounts in Uganda Shillings (UGX). For queries, visit your nearest branch.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted py-4" style="font-size:.78rem; border-top:1px solid #e0e0e0">
        &copy; {{ date('Y') }} Emuria Micro Finance Limited &mdash; All Rights Reserved
    </footer>
</div>

<!-- Resume toast (shown after restoring a draft) -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="resumeToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="resumeToastBody">Draft restored successfully. Please re-upload any required documents.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FORM WRAPPER (hidden until user clicks Start)
═══════════════════════════════════════════════════════════ -->
<div id="formWrapper" style="display:none">

<div class="container py-4" style="max-width:860px">

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('client.apply.store') }}" enctype="multipart/form-data" id="applyForm" novalidate>
        @csrf

        {{-- ═══════════════════════════════════════════════════════════
             STEP 1 — PERSONAL INFORMATION
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section active" id="section-0">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-user"></i></span>
                    Personal Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="required-star">*</span></label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name') }}" placeholder="As on your national ID" required>
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number <span class="required-star">*</span></label>
                        <input type="text" name="phone" id="phoneField" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" placeholder="256XXXXXXXXX" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <!-- Inline draft-restore panel (shown if saved draft found for this number) -->
                        <div id="inlineRestorePanel" class="alert alert-info py-2 px-3 mt-2 small d-flex align-items-center justify-content-between" style="display:none; border-radius:10px">
                            <span><i class="fas fa-history me-1"></i>A saved draft was found for <strong id="inlineRestorePhone"></strong>. Restore it?</span>
                            <span class="ms-2 text-nowrap">
                                <button type="button" id="inlineRestoreConfirmBtn" class="btn btn-sm btn-success me-1">Yes, restore</button>
                                <button type="button" id="inlineRestoreDismissBtn" class="btn btn-sm btn-outline-secondary">No</button>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">National ID (NIN)</label>
                        <input type="text" name="national_id" class="form-control" value="{{ old('national_id') }}" placeholder="CM…">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="Male"   {{ old('gender') == 'Male'   ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address (optional)</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="example@email.com">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 2 — LOAN DETAILS
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-1">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-hand-holding-usd"></i></span>
                    Loan Details
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Loan Product <span class="required-star">*</span></label>
                        <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">-- Select Product --</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}"
                                data-max="{{ $p->max_amt }}"
                                data-interest="{{ $p->interest }}"
                                data-period-type="{{ $p->period_type }}"
                                {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} — {{ number_format($p->interest, 1) }}% | Max: UGX {{ number_format($p->max_amt) }}
                            </option>
                            @endforeach
                        </select>
                        @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch <span class="required-star">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Requested Amount (UGX) <span class="required-star">*</span></label>
                        {{-- Text display for comma formatting --}}
                        <input type="text" id="requestedAmountDisplay"
                               class="form-control @error('requested_amount') is-invalid @enderror"
                               value="{{ old('requested_amount') ? number_format(old('requested_amount')) : '' }}"
                               placeholder="e.g. 1,000,000"
                               inputmode="numeric" autocomplete="off">
                        {{-- Hidden numeric value actually submitted --}}
                        <input type="hidden" name="requested_amount" id="requestedAmount"
                               value="{{ old('requested_amount') }}">
                        <div id="amountHint" class="form-text"></div>
                        <div id="amountError" class="text-danger small mt-1" style="display:none"></div>
                        @error('requested_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tenure (Periods) <span class="required-star">*</span></label>
                        <input type="number" name="tenure_periods" id="tenurePeriods"
                               class="form-control @error('tenure_periods') is-invalid @enderror"
                               value="{{ old('tenure_periods') }}" min="1" max="365" placeholder="e.g. 26" required>
                        <div id="tenureHint" class="form-text"></div>
                        @error('tenure_periods')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Repayment Frequency <span class="required-star">*</span></label>
                        <select name="repayment_frequency" id="repaymentFrequency"
                               class="form-select @error('repayment_frequency') is-invalid @enderror" required>
                            <option value="weekly"  {{ old('repayment_frequency','weekly') == 'weekly'  ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ old('repayment_frequency') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="daily"   {{ old('repayment_frequency') == 'daily'   ? 'selected' : '' }}>Daily</option>
                        </select>
                        <div id="freqHint" class="form-text"></div>
                        @error('repayment_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loan Purpose</label>
                        <input type="text" name="loan_purpose" class="form-control" value="{{ old('loan_purpose') }}" placeholder="e.g. Working capital">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preferred Disbursement Method</label>
                        <select name="preferred_disbursement_method" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="MTN" {{ old('preferred_disbursement_method') == 'MTN' ? 'selected' : '' }}>MTN Mobile Money</option>
                            <option value="Airtel" {{ old('preferred_disbursement_method') == 'Airtel' ? 'selected' : '' }}>Airtel Money</option>
                            <option value="Bank" {{ old('preferred_disbursement_method') == 'Bank' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="Cash" {{ old('preferred_disbursement_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 3 — RESIDENCE & REFERENCES
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-2">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-map-marker-alt"></i></span>
                    Residence, LC1 and Local Reference
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Residence Village</label>
                        <input type="text" name="residence_village" class="form-control" value="{{ old('residence_village') }}" placeholder="Village name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Residence Parish</label>
                        <input type="text" name="residence_parish" class="form-control" value="{{ old('residence_parish') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sub-county</label>
                        <input type="text" name="residence_subcounty" class="form-control" value="{{ old('residence_subcounty') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">District</label>
                        <input type="text" name="residence_district" class="form-control" value="{{ old('residence_district') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Landmark / Directions to Residence</label>
                        <input type="text" name="landmark_directions" class="form-control" value="{{ old('landmark_directions') }}"
                               placeholder="e.g. Near Trading Centre, opposite St. Peter chapel">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Years at Residence</label>
                        <input type="number" name="years_at_residence" class="form-control" value="{{ old('years_at_residence') }}" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Home Front Door Colour</label>
                        <input type="text" name="home_door_color" class="form-control" value="{{ old('home_door_color') }}" placeholder="e.g. Blue">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">House Structure Type</label>
                        <select name="home_type" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(['Permanent brick/cement','Mud and wattle','Iron sheet/mabati','Stone block','Semi-permanent','Other'] as $ht)
                            <option value="{{ $ht }}" {{ old('home_type') == $ht ? 'selected' : '' }}>{{ $ht }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Next of Kin Name</label>
                        <input type="text" name="next_of_kin_name" class="form-control" value="{{ old('next_of_kin_name') }}" placeholder="Full name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Next of Kin Phone</label>
                        <input type="text" name="next_of_kin_phone" class="form-control" value="{{ old('next_of_kin_phone') }}" placeholder="256XXXXXXXXX">
                    </div>

                    <div class="col-12"><hr class="my-1"><strong class="text-muted small">Chairman's Introduction Letter <span class="required-star">*</span></strong></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">LC1 Chairman's Letter <span class="required-star">*</span></label>
                        <div class="small text-muted mb-1">Upload the LC1 Chairman's letter introducing and vouching for the applicant. <strong>This is mandatory.</strong> Accepted: JPG, PNG, PDF — max 5 MB.</div>
                        <input type="file" name="chairman_letter" id="chairmanLetterInput"
                               class="form-control @error('chairman_letter') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.pdf" required>
                        @error('chairman_letter')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback" id="chairmanLetterErr">Please upload the LC1 Chairman's introduction letter — this is required.</div>@enderror
                    </div>

                    <div class="col-12"><hr class="my-1"><strong class="text-muted small">LC1 Information</strong></div>
                    <div class="col-md-6">
                        <label class="form-label">LC1 Name</label>
                        <input type="text" name="lc1_name" class="form-control" value="{{ old('lc1_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">LC1 Phone</label>
                        <input type="text" name="lc1_phone" class="form-control" value="{{ old('lc1_phone') }}" placeholder="256XXXXXXXXX">
                    </div>

                    <div class="col-12"><hr class="my-1"><strong class="text-muted small">Clan / Customary Authority</strong></div>
                    <div class="col-md-4">
                        <label class="form-label">Clan Chairperson Name</label>
                        <input type="text" name="clan_name" class="form-control" value="{{ old('clan_name') }}" placeholder="CCP name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Clan Chairperson Contact</label>
                        <input type="text" name="clan_contact" class="form-control" value="{{ old('clan_contact') }}" placeholder="256XXXXXXXXX">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Clan Letter Available?</label>
                        <select name="clan_letter_available" class="form-select">
                            <option value="0" {{ old('clan_letter_available') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('clan_letter_available') == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-1"><strong class="text-muted small">Community References</strong></div>
                    <div class="col-md-4">
                        <label class="form-label">Reference 1 Name</label>
                        <input type="text" name="reference_name" class="form-control" value="{{ old('reference_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reference 1 Contact</label>
                        <input type="text" name="reference_phone" class="form-control" value="{{ old('reference_phone') }}" placeholder="256XXXXXXXXX">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reference 1 Relationship</label>
                        <input type="text" name="reference_relationship" class="form-control" value="{{ old('reference_relationship') }}"
                               placeholder="e.g. Neighbor and produce supplier">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference 2 Name</label>
                        <input type="text" name="reference_2_name" class="form-control" value="{{ old('reference_2_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference 2 Contact</label>
                        <input type="text" name="reference_2_contact" class="form-control" value="{{ old('reference_2_contact') }}" placeholder="256XXXXXXXXX">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 4 — BUSINESS PROFILE
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-3">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-store"></i></span>
                    Business Profile and Evidence
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Business Name <span class="required-star">*</span></label>
                        <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror"
                               value="{{ old('business_name') }}" required>
                        @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Type</label>
                        <select name="business_type" id="businessTypeSelect" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(['Retail Shop','Wholesale','Restaurant/Food','Agriculture','Transport','Services','Manufacturing','Other'] as $bt)
                            <option value="{{ $bt }}" {{ old('business_type') == $bt || (old('business_type') && !in_array(old('business_type'),['Retail Shop','Wholesale','Restaurant/Food','Agriculture','Transport','Services','Manufacturing','Other']) && $bt==='Other') ? 'selected' : '' }}>{{ $bt }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="business_type_custom" id="businessTypeCustom"
                               class="form-control mt-2"
                               placeholder="Please specify your business type"
                               value="{{ (!in_array(old('business_type'),['','Retail Shop','Wholesale','Restaurant/Food','Agriculture','Transport','Services','Manufacturing','Other'])) ? old('business_type') : '' }}"
                               style="display:none">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Location</label>
                        <input type="text" name="business_location" class="form-control" value="{{ old('business_location') }}"
                               placeholder="e.g. Acowa Trading Centre">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Years in Operation</label>
                        <input type="number" name="business_years_operation" class="form-control" value="{{ old('business_years_operation') }}" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Avg. Daily Customers</label>
                        <input type="number" name="avg_daily_customers" class="form-control" value="{{ old('avg_daily_customers') }}" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Days Open Per Week</label>
                        <input type="number" name="business_days_open" class="form-control" value="{{ old('business_days_open') }}" min="1" max="7" placeholder="e.g. 6">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Peak Trading Hours</label>
                        <input type="text" name="peak_trading_hours" class="form-control" value="{{ old('peak_trading_hours') }}" placeholder="e.g. 5:30pm–8:30pm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Main Supplier Name</label>
                        <input type="text" name="top_supplier_name" class="form-control" value="{{ old('top_supplier_name') }}" placeholder="e.g. Kireka Wholesalers Ltd">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Describe Main Business Activity</label>
                        <textarea name="business_description" class="form-control" rows="2" placeholder="e.g. Retail shop selling produce, sugar, soap…">{{ old('business_description') }}</textarea>
                    </div>

                    <div class="col-12"><hr class="my-1"><strong class="text-muted small">Upload Business Evidence <small>(jpg/png/pdf, max 5MB each)</small></strong></div>
                    @foreach([
                        ['business_profile_photo',   'Business Profile Photo'],
                        ['business_activity_photos', 'Business Activity Photos'],
                        ['inventory_photos',          'Inventory Photos'],
                        ['sales_book_photo',          'Sales Book'],
                        ['purchases_book_photo',      'Purchases Book'],
                        ['expense_records_photo',     'Expense Records'],
                        ['mobile_money_statements',   'Mobile Money Statements'],
                    ] as [$field, $label])
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        <input type="file" name="{{ $field }}" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 5 — FINANCIAL CLAIMS
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-4">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-coins"></i></span>
                    Client Financial Claims
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-info py-2 px-3 small mb-0" style="border-radius:10px">
                            <i class="fas fa-info-circle me-1"></i>
                            All figures below are <strong>monthly</strong> (per calendar month). Enter what the business actually earns and spends each month.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monthly Sales (UGX) <span class="required-star">*</span></label>
                        <div class="form-text text-muted mb-1">DMS — Total gross monthly sales/turnover</div>
                        <input type="text" id="daily_sales_claimedDisp" class="form-control ugx-display @error('daily_sales_claimed') is-invalid @enderror"
                               placeholder="0" autocomplete="off"
                               value="{{ old('daily_sales_claimed') ? number_format((int)old('daily_sales_claimed')) : '0' }}">
                        <input type="hidden" name="daily_sales_claimed" id="daily_sales_claimedHid" value="{{ old('daily_sales_claimed', 0) }}">
                        <div class="invalid-feedback" id="daily_sales_claimedErr">@error('daily_sales_claimed'){{ $message }}@else Please enter the monthly sales amount.@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monthly Cost of Goods Sold (UGX) <span class="required-star">*</span></label>
                        <div class="form-text text-muted mb-1">DMCOGS — Purchase cost of goods sold monthly</div>
                        <input type="text" id="monthly_cogs_claimedDisp" class="form-control ugx-display @error('monthly_cogs_claimed') is-invalid @enderror"
                               placeholder="0" autocomplete="off"
                               value="{{ old('monthly_cogs_claimed') ? number_format((int)old('monthly_cogs_claimed')) : '0' }}">
                        <input type="hidden" name="monthly_cogs_claimed" id="monthly_cogs_claimedHid" value="{{ old('monthly_cogs_claimed', 0) }}">
                        <div class="invalid-feedback" id="monthly_cogs_claimedErr">@error('monthly_cogs_claimed'){{ $message }}@else Please enter the monthly cost of goods sold.@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monthly Operating Expenses (UGX) <span class="required-star">*</span></label>
                        <div class="form-text text-muted mb-1">DMOE — Rent, transport, utilities, wages etc.</div>
                        <input type="text" id="business_expenses_claimedDisp" class="form-control ugx-display @error('business_expenses_claimed') is-invalid @enderror"
                               placeholder="0" autocomplete="off"
                               value="{{ old('business_expenses_claimed') ? number_format((int)old('business_expenses_claimed')) : '0' }}">
                        <input type="hidden" name="business_expenses_claimed" id="business_expenses_claimedHid" value="{{ old('business_expenses_claimed', 0) }}">
                        <div class="invalid-feedback" id="business_expenses_claimedErr">@error('business_expenses_claimed'){{ $message }}@else Please enter the monthly operating expenses.@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monthly Household Expenses (UGX) <span class="required-star">*</span></label>
                        <div class="form-text text-muted mb-1">DMHE — Food, school fees, utilities for the household</div>
                        <input type="text" id="household_expenses_claimedDisp" class="form-control ugx-display @error('household_expenses_claimed') is-invalid @enderror"
                               placeholder="0" autocomplete="off"
                               value="{{ old('household_expenses_claimed') ? number_format((int)old('household_expenses_claimed')) : '0' }}">
                        <input type="hidden" name="household_expenses_claimed" id="household_expenses_claimedHid" value="{{ old('household_expenses_claimed', 0) }}">
                        <div class="invalid-feedback" id="household_expenses_claimedErr">@error('household_expenses_claimed'){{ $message }}@else Please enter the monthly household expenses.@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Other Monthly Income (UGX)</label>
                        <div class="form-text text-muted mb-1">DOMI — Salary, rental income, remittances etc.</div>
                        <input type="text" id="other_income_claimedDisp" class="form-control ugx-display"
                               placeholder="0" autocomplete="off"
                               value="{{ old('other_income_claimed') ? number_format((int)old('other_income_claimed')) : '0' }}">
                        <input type="hidden" name="other_income_claimed" id="other_income_claimedHid" value="{{ old('other_income_claimed', 0) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Seasonality / Trading Note</label>
                        <div class="form-text text-muted mb-1">SEASONALITY_NOTE — e.g. "peak during school term, low in Dec"</div>
                        <input type="text" name="seasonality_note" class="form-control"
                               value="{{ old('seasonality_note') }}"
                               placeholder="e.g. Stable trading cycle with moderate stock turnover">
                    </div>

                    <div class="col-12"><hr class="my-1"><strong class="text-muted small">External Debt</strong></div>
                    <div class="col-md-4">
                        <label class="form-label">Has Loans with Other Institutions?</label>
                        <select name="has_external_loans" class="form-select" id="hasExternalLoans">
                            <option value="0" {{ old('has_external_loans') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('has_external_loans') == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-4 external-fields">
                        <label class="form-label">Number of External Lenders</label>
                        <input type="number" name="external_lenders_count" class="form-control" value="{{ old('external_lenders_count', 0) }}" min="0">
                    </div>
                    <div class="col-md-4 external-fields">
                        <label class="form-label">External Outstanding (UGX)</label>
                        <input type="number" name="external_outstanding" class="form-control" value="{{ old('external_outstanding', 0) }}" min="0" step="1000">
                    </div>
                    <div class="col-md-6 external-fields">
                        <label class="form-label">External Installment per Period (UGX)</label>
                        <input type="number" name="external_installment_per_period" class="form-control" value="{{ old('external_installment_per_period', 0) }}" min="0" step="1000">
                    </div>
                    <div class="col-md-6 external-fields">
                        <label class="form-label">Max Days in External Arrears</label>
                        <input type="number" name="max_external_arrears_days" class="form-control" value="{{ old('max_external_arrears_days', 0) }}" min="0">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 6 — COLLATERAL
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-5">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-shield-alt"></i></span>
                    Collateral Security Pledge
                </div>

                {{-- ── Collateral 1 (Required) ── --}}
                <div class="d-flex align-items-center mb-3">
                    <strong class="text-muted small">Collateral 1 <span class="required-star">*</span></strong>
                    <span class="badge bg-danger ms-2" style="font-size:.65rem">Required</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type <span class="required-star">*</span></label>
                        <select name="collateral_1_type" class="form-select @error('collateral_1_type') is-invalid @enderror" required>
                            <option value="">-- Select --</option>
                            @foreach(['Land','Building','Motorcycle','Vehicle','Equipment','Machinery','Livestock','Electronics','Other'] as $ct)
                            <option value="{{ $ct }}" {{ old('collateral_1_type') == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                            @endforeach
                        </select>
                        @error('collateral_1_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Owner Name <span class="required-star">*</span></label>
                        <input type="text" name="collateral_1_owner_name" class="form-control @error('collateral_1_owner_name') is-invalid @enderror"
                               value="{{ old('collateral_1_owner_name') }}" required>
                        @error('collateral_1_owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ownership Status</label>
                        <select name="collateral_1_ownership_status" class="form-select">
                            <option value="Owned" {{ old('collateral_1_ownership_status') == 'Owned' ? 'selected' : '' }}>Owned</option>
                            <option value="Joint" {{ old('collateral_1_ownership_status') == 'Joint' ? 'selected' : '' }}>Joint Ownership</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description <span class="required-star">*</span></label>
                        <input type="text" name="collateral_1_description" class="form-control @error('collateral_1_description') is-invalid @enderror"
                               value="{{ old('collateral_1_description') }}" placeholder="Describe the collateral item" required>
                        @error('collateral_1_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Document Type</label>
                        <select name="collateral_1_doc_type" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(['Land Title','LC1 Letter + Witness','Logbook','Receipt','Agreement Letter','Other'] as $dt)
                            <option value="{{ $dt }}" {{ old('collateral_1_doc_type') == $dt ? 'selected' : '' }}>{{ $dt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Document Number</label>
                        <input type="text" name="collateral_1_doc_number" class="form-control @error('collateral_1_doc_number') is-invalid @enderror"
                               value="{{ old('collateral_1_doc_number') }}">
                        @error('collateral_1_doc_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Client Estimated Value (UGX) <span class="required-star">*</span></label>
                        <input type="text" id="collateral_1_client_valueDisp" class="form-control ugx-display @error('collateral_1_client_value') is-invalid @enderror"
                               placeholder="e.g. 5,000,000" autocomplete="off"
                               value="{{ old('collateral_1_client_value') ? number_format((int)old('collateral_1_client_value')) : '' }}">
                        <input type="hidden" name="collateral_1_client_value" id="collateral_1_client_valueHid" value="{{ old('collateral_1_client_value') }}">
                        <div class="invalid-feedback" id="collateral_1_client_valueErr">@error('collateral_1_client_value'){{ $message }}@else Please enter the estimated value of this collateral.@enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload Collateral Document Photo</label>
                        <input type="file" name="collateral_1_doc_photo" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Collateral Already Pledged?</label>
                        <select name="collateral_1_pledged" class="form-select">
                            <option value="0" {{ old('collateral_1_pledged') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('collateral_1_pledged') == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customary / Ancestral Land?</label>
                        <select name="collateral_1_customary" class="form-select">
                            <option value="0" {{ old('collateral_1_customary') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('collateral_1_customary') == '1' ? 'selected' : '' }}>Yes — CCP letter required</option>
                        </select>
                    </div>
                </div>{{-- /row collateral 1 --}}

                {{-- ── Add Collateral 2 button ── --}}
                <div class="mt-4" id="addCollateral2Wrap" style="{{ old('collateral_2_type') ? 'display:none' : '' }}">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showCollateral2()"
                            style="border-radius:50px">
                        <i class="fas fa-plus me-1"></i>Add Second Collateral <span class="text-muted">(Optional)</span>
                    </button>
                </div>

                {{-- ── Collateral 2 (Optional, hidden by default) ── --}}
                <div id="collateral2Section" style="{{ old('collateral_2_type') ? '' : 'display:none' }}">
                    <hr class="my-4">
                    <div class="d-flex align-items-center mb-3">
                        <strong class="text-muted small">Collateral 2</strong>
                        <span class="badge bg-secondary ms-2" style="font-size:.65rem">Optional</span>
                        <button type="button" class="btn btn-link btn-sm text-danger ms-auto p-0"
                                onclick="removeCollateral2()" title="Remove second collateral">
                            <i class="fas fa-times-circle me-1"></i>Remove
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="collateral_2_type" class="form-select" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                                <option value="">-- None --</option>
                                @foreach(['Land','Building','Motorcycle','Vehicle','Equipment','Machinery','Livestock','Electronics','Other'] as $ct)
                                <option value="{{ $ct }}" {{ old('collateral_2_type') == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Owner Name</label>
                            <input type="text" name="collateral_2_owner_name" class="form-control"
                                   value="{{ old('collateral_2_owner_name') }}" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client Estimated Value (UGX)</label>
                            <input type="text" id="collateral_2_client_valueDisp" class="form-control ugx-display"
                                   placeholder="0" autocomplete="off"
                                   value="{{ old('collateral_2_client_value') ? number_format((int)old('collateral_2_client_value')) : '' }}"
                                   {{ old('collateral_2_type') ? '' : 'disabled' }}>
                            <input type="hidden" name="collateral_2_client_value" id="collateral_2_client_valueHid" value="{{ old('collateral_2_client_value', 0) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="collateral_2_description" class="form-control"
                                   value="{{ old('collateral_2_description') }}" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Document Type</label>
                            <select name="collateral_2_doc_type" class="form-select" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                                <option value="">-- Select --</option>
                                @foreach(['Land Title','LC1 Letter + Witness','Logbook','Receipt','Agreement Letter','Other'] as $dt)
                                <option value="{{ $dt }}" {{ old('collateral_2_doc_type') == $dt ? 'selected' : '' }}>{{ $dt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Document Number</label>
                            <input type="text" name="collateral_2_doc_number" class="form-control"
                                   value="{{ old('collateral_2_doc_number') }}" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Upload Document Photo</label>
                            <input type="file" name="collateral_2_doc_photo" class="form-control"
                                   accept=".jpg,.jpeg,.png,.pdf" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Collateral Already Pledged?</label>
                            <select name="collateral_2_pledged" class="form-select" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                                <option value="0" {{ old('collateral_2_pledged', '0') == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('collateral_2_pledged') == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Customary / Ancestral Land?</label>
                            <select name="collateral_2_customary" class="form-select" {{ old('collateral_2_type') ? '' : 'disabled' }}>
                                <option value="0" {{ old('collateral_2_customary', '0') == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('collateral_2_customary') == '1' ? 'selected' : '' }}>Yes — CCP letter required</option>
                            </select>
                        </div>
                    </div>
                </div>{{-- /collateral2Section --}}

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 7 — GUARANTORS
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-6">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-users"></i></span>
                    Guarantor Support
                </div>

                {{-- ── Guarantor 1 (Mandatory) ── --}}
                <div class="d-flex align-items-center mb-3">
                    <strong class="text-muted small">Guarantor 1 <span class="required-star">*</span></strong>
                    <span class="badge bg-danger ms-2" style="font-size:.65rem">Required</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name <span class="required-star">*</span></label>
                        <input type="text" name="guarantor_1_name" class="form-control @error('guarantor_1_name') is-invalid @enderror"
                               value="{{ old('guarantor_1_name') }}" required>
                        @error('guarantor_1_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Relationship <span class="required-star">*</span></label>
                        <input type="text" name="guarantor_1_relationship" class="form-control @error('guarantor_1_relationship') is-invalid @enderror"
                               value="{{ old('guarantor_1_relationship') }}" placeholder="e.g. Brother-in-law" required>
                        @error('guarantor_1_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone <span class="required-star">*</span></label>
                        <input type="text" name="guarantor_1_phone" class="form-control @error('guarantor_1_phone') is-invalid @enderror"
                               value="{{ old('guarantor_1_phone') }}" required>
                        @error('guarantor_1_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Commitment Level <span class="required-star">*</span></label>
                        <select name="guarantor_1_commitment_level" class="form-select @error('guarantor_1_commitment_level') is-invalid @enderror" required>
                            <option value="High"     {{ old('guarantor_1_commitment_level') == 'High'     ? 'selected' : '' }}>High</option>
                            <option value="Moderate" {{ old('guarantor_1_commitment_level') == 'Moderate' ? 'selected' : '' }}>Moderate</option>
                            <option value="Low"      {{ old('guarantor_1_commitment_level') == 'Low'      ? 'selected' : '' }}>Low</option>
                        </select>
                        @error('guarantor_1_commitment_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pledged Asset Value (UGX)</label>
                        <input type="number" name="guarantor_1_pledged_asset_value" class="form-control"
                               value="{{ old('guarantor_1_pledged_asset_value', 0) }}" min="0" step="10000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Declared Monthly Income (UGX)</label>
                        <input type="number" name="guarantor_1_monthly_income" class="form-control"
                               value="{{ old('guarantor_1_monthly_income') }}" min="0" step="10000" placeholder="e.g. 1,000,000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Guarantor Signed Consent?</label>
                        <select name="guarantor_1_signed_consent" class="form-select">
                            <option value="0" {{ old('guarantor_1_signed_consent') == '0' ? 'selected' : '' }}>Not yet</option>
                            <option value="1" {{ old('guarantor_1_signed_consent') == '1' ? 'selected' : '' }}>Yes, signed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pledge Description</label>
                        <input type="text" name="guarantor_1_pledge_description" class="form-control"
                               value="{{ old('guarantor_1_pledge_description') }}" placeholder="e.g. Two dairy cows and salary support">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Type / Description</label>
                        <input type="text" name="guarantor_1_support_description" class="form-control"
                               value="{{ old('guarantor_1_support_description') }}" placeholder="e.g. Income + motorcycle">
                    </div>
                </div>

                {{-- ── Add Guarantor 2 button ── --}}
                <div class="mt-4" id="addGuarantor2Wrap" style="{{ old('guarantor_2_name') ? 'display:none' : '' }}">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showGuarantor2()"
                            style="border-radius:50px">
                        <i class="fas fa-plus me-1"></i>Add Second Guarantor <span class="text-muted">(Optional)</span>
                    </button>
                </div>

                {{-- ── Guarantor 2 (Optional, hidden by default) ── --}}
                <div id="guarantor2Section" style="{{ old('guarantor_2_name') ? '' : 'display:none' }}">
                    <hr class="my-4">
                    <div class="d-flex align-items-center mb-3">
                        <strong class="text-muted small">Guarantor 2</strong>
                        <span class="badge bg-secondary ms-2" style="font-size:.65rem">Optional</span>
                        <button type="button" class="btn btn-link btn-sm text-danger ms-auto p-0"
                                onclick="removeGuarantor2()" title="Remove second guarantor">
                            <i class="fas fa-times-circle me-1"></i>Remove
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="guarantor_2_name" class="form-control" value="{{ old('guarantor_2_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="guarantor_2_relationship" class="form-control" value="{{ old('guarantor_2_relationship') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="guarantor_2_phone" class="form-control" value="{{ old('guarantor_2_phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Commitment Level</label>
                            <select name="guarantor_2_commitment_level" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="High"     {{ old('guarantor_2_commitment_level') == 'High'     ? 'selected' : '' }}>High</option>
                                <option value="Moderate" {{ old('guarantor_2_commitment_level') == 'Moderate' ? 'selected' : '' }}>Moderate</option>
                                <option value="Low"      {{ old('guarantor_2_commitment_level') == 'Low'      ? 'selected' : '' }}>Low</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pledged Asset Value (UGX)</label>
                            <input type="number" name="guarantor_2_pledged_asset_value" class="form-control"
                                   value="{{ old('guarantor_2_pledged_asset_value', 0) }}" min="0" step="10000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Declared Monthly Income (UGX)</label>
                            <input type="number" name="guarantor_2_monthly_income" class="form-control"
                                   value="{{ old('guarantor_2_monthly_income') }}" min="0" step="10000" placeholder="e.g. 1,300,000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Signed Consent?</label>
                            <select name="guarantor_2_signed_consent" class="form-select">
                                <option value="0">Not yet</option>
                                <option value="1" {{ old('guarantor_2_signed_consent') == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pledge Description</label>
                            <input type="text" name="guarantor_2_pledge_description" class="form-control"
                                   value="{{ old('guarantor_2_pledge_description') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Support Type / Description</label>
                            <input type="text" name="guarantor_2_support_description" class="form-control"
                                   value="{{ old('guarantor_2_support_description') }}" placeholder="e.g. Salary + household assets">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             STEP 8 — DECLARATIONS & SUBMIT
        ═══════════════════════════════════════════════════════════ --}}
        <div class="step-section" id="section-7">
            <div class="form-card">
                <div class="section-title">
                    <span class="section-icon"><i class="fas fa-file-signature"></i></span>
                    Client Declarations
                </div>
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-1"></i>
                    By checking the boxes below, you confirm that the information provided is accurate.
                    Our field officer will contact you to verify your details.
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input @error('consent_verification') is-invalid @enderror"
                                   type="checkbox" name="consent_verification" id="consentVerification"
                                   {{ old('consent_verification') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="consentVerification">
                                <strong>Consent to Verification</strong> — I agree that Emuria Micro Finance may contact my references,
                                LC1, and guarantors to verify my application details.
                            </label>
                            @error('consent_verification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input @error('consent_crb') is-invalid @enderror"
                                   type="checkbox" name="consent_crb" id="consentCrb"
                                   {{ old('consent_crb') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="consentCrb">
                                <strong>Consent to CRB Check</strong> — I authorize Emuria Micro Finance to access my
                                Credit Reference Bureau (CRB) record.
                            </label>
                            @error('consent_crb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input @error('declaration_truth') is-invalid @enderror"
                                   type="checkbox" name="declaration_truth" id="declarationTruth"
                                   {{ old('declaration_truth') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="declarationTruth">
                                <strong>Declaration of Truth</strong> — I declare that all the information I have provided
                                is true, complete, and accurate to the best of my knowledge.
                            </label>
                            @error('declaration_truth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navigation Buttons --}}
        <div style="height:80px"></div>{{-- spacer so content isn't hidden behind sticky bar --}}
    </form>
</div>{{-- /container --}}
</div>{{-- /formWrapper --}}

{{-- Sticky navigation bar (always visible at bottom of viewport) --}}
<div id="stickyNav" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:100; background:#fff; border-top:1px solid #dee2e6; box-shadow:0 -2px 12px rgba(0,0,0,.08); padding:12px 20px;">
    <div style="max-width:860px; margin:0 auto; display:flex; justify-content:space-between; align-items:center;">
        <button type="button" class="btn btn-outline-secondary btn-back" id="btnBack" style="display:none; min-width:110px">
            <i class="fas fa-arrow-left me-1"></i> Back
        </button>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-primary btn-next" id="btnNext" style="min-width:110px">
                Next <i class="fas fa-arrow-right ms-1"></i>
            </button>
            <button type="submit" form="applyForm" class="btn btn-success" id="btnSubmit" style="display:none; min-width:140px">
                <i class="fas fa-paper-plane me-1"></i> Submit Application
            </button>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Landing ↔ Form toggle ──────────────────────────────────────────
function showForm() {
    document.getElementById('landingPage').style.display  = 'none';
    document.getElementById('formHeader').style.display   = '';
    document.getElementById('formWrapper').style.display  = '';
    document.getElementById('stickyNav').style.display    = '';
    showStep(currentStep);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function startApplication() {
    showForm();
}

// Click a product card → pre-select it in the dropdown then start
function selectProductAndStart(productId) {
    showForm();
    // Wait a tick for the form to be visible, then set the value and trigger change
    setTimeout(function() {
        const sel = document.querySelector('select[name="product_id"]');
        if (sel) {
            sel.value = productId;
            sel.dispatchEvent(new Event('change'));
        }
    }, 50);
}

// If there are validation errors, skip landing and show the form directly
@if ($errors->any())
    document.addEventListener('DOMContentLoaded', function() { showForm(); });
@endif

const TOTAL_STEPS = 8;
const STEP_LABELS = ['Personal Info','Loan Details','Residence','Business','Financials','Collateral','Guarantors','Declarations'];
let currentStep = 0;

function showStep(step) {
    document.querySelectorAll('.step-section').forEach((s, i) => {
        s.classList.toggle('active', i === step);
    });
    document.querySelectorAll('.step-item').forEach((item, i) => {
        item.classList.remove('active', 'done');
        if (i < step)  item.classList.add('done');
        if (i === step) item.classList.add('active');
    });
    document.getElementById('btnBack').style.display  = step === 0 ? 'none' : '';
    document.getElementById('btnNext').style.display  = step === TOTAL_STEPS - 1 ? 'none' : '';
    document.getElementById('btnSubmit').style.display = step === TOTAL_STEPS - 1 ? '' : 'none';
    // Update mobile step counter
    const counter = document.getElementById('stepCounter');
    if (counter) counter.textContent = 'Step ' + (step + 1) + ' of ' + TOTAL_STEPS + ' — ' + STEP_LABELS[step];
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Per-step client-side validation ─────────────────────────────────
function validateStep(stepIndex) {
    const section = document.getElementById('section-' + stepIndex);
    if (!section) return true;
    let valid = true;

    section.querySelectorAll('input, select, textarea').forEach(function(el) {
        // Skip hidden inputs, disabled, token
        if (el.type === 'hidden' || el.disabled || el.name === '_token') return;
        // Skip display-only UGX inputs (validated via their paired hidden field below)
        if (el.classList.contains('ugx-display')) return;
        // Skip the requested amount display input
        if (el.id === 'requestedAmountDisplay') return;
        el.classList.remove('is-invalid');
        if (!el.checkValidity()) {
            el.classList.add('is-invalid');
            // Show a human-readable message using validationMessage
            const errDiv = el.parentElement.querySelector('.invalid-feedback');
            if (errDiv && !errDiv.dataset.serverMsg) {
                errDiv.textContent = el.validationMessage || 'This field is required.';
            }
            valid = false;
        }
    });

    // Validate display/hidden UGX pairs in this step
    const UGX_PAIRS = [
        // [displayId, hiddenId, errorId, label, required, min]
        ['requestedAmountDisplay',          'requestedAmount',                  null,                              'Loan amount',             true,  1],
        ['daily_sales_claimedDisp',         'daily_sales_claimedHid',           'daily_sales_claimedErr',          'Monthly sales',           true,  0],
        ['monthly_cogs_claimedDisp',         'monthly_cogs_claimedHid',          'monthly_cogs_claimedErr',         'Cost of goods sold',      true,  0],
        ['business_expenses_claimedDisp',   'business_expenses_claimedHid',     'business_expenses_claimedErr',    'Operating expenses',      true,  0],
        ['household_expenses_claimedDisp',  'household_expenses_claimedHid',    'household_expenses_claimedErr',   'Household expenses',      true,  0],
        ['collateral_1_client_valueDisp',   'collateral_1_client_valueHid',     'collateral_1_client_valueErr',    'Collateral 1 value',      true,  1],
    ];
    UGX_PAIRS.forEach(function([dispId, hidId, errId, label, required, min]) {
        const disp = document.getElementById(dispId);
        const hid  = document.getElementById(hidId);
        if (!disp || !section.contains(disp)) return; // not in this step
        const val = hid ? parseInt(hid.value) : NaN;
        disp.classList.remove('is-invalid');
        const errDiv = errId ? document.getElementById(errId) : disp.parentElement.querySelector('.invalid-feedback');
        let msg = '';
        if (required && (!hid || !hid.value.trim() || isNaN(val))) {
            msg = label + ' is required — please enter a number.';
        } else if (required && min > 0 && val < min) {
            msg = label + ' must be greater than zero.';
        }
        if (msg) {
            disp.classList.add('is-invalid');
            if (errDiv) errDiv.textContent = msg;
            valid = false;
        }
    });

    return valid;
}

document.getElementById('btnNext').addEventListener('click', function() {
    if (!validateStep(currentStep)) {
        // Shake the active form card to signal errors
        const card = document.querySelector('#section-' + currentStep + ' .form-card');
        if (card) { card.style.animation = 'none'; card.offsetHeight; card.style.animation = ''; }
        return;
    }
    saveDraft();
    if (currentStep < TOTAL_STEPS - 1) {
        currentStep++;
        showStep(currentStep);
    }
});
document.getElementById('btnBack').addEventListener('click', function() {
    saveDraft();
    if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
    }
});

// If there were validation errors, jump to the first step with an error
@if ($errors->any())
    const errorFields = @json($errors->keys());
    const stepFieldMap = [
        ['full_name','phone','national_id','date_of_birth','gender'],
        ['product_id','branch_id','requested_amount','tenure_periods','repayment_frequency'],
        ['residence_village','lc1_name','reference_name','home_door_color','home_type','next_of_kin_name','next_of_kin_phone','reference_2_name','reference_2_contact','clan_name','clan_contact'],
        ['business_name','business_type','business_days_open','peak_trading_hours','top_supplier_name'],
        ['daily_sales_claimed','monthly_cogs_claimed','business_expenses_claimed','household_expenses_claimed','seasonality_note'],
        ['collateral_1_type','collateral_1_owner_name','collateral_1_description','collateral_1_client_value','collateral_1_pledged','collateral_1_customary'],
        ['guarantor_1_name','guarantor_1_relationship','guarantor_1_phone','guarantor_1_commitment_level','guarantor_1_monthly_income','guarantor_1_support_description'],
        ['consent_verification','consent_crb','declaration_truth'],
    ];
    let jumpStep = 0;
    for (let s = 0; s < stepFieldMap.length; s++) {
        if (stepFieldMap[s].some(f => errorFields.includes(f))) { jumpStep = s; break; }
    }
    currentStep = jumpStep;
    showStep(currentStep);
@endif

// ── Product → auto-set frequency, tenure hint, amount max ─────────────
const productSelect      = document.querySelector('select[name="product_id"]');
const amountDisplay      = document.getElementById('requestedAmountDisplay');
const amountHidden       = document.getElementById('requestedAmount');
const amountHint         = document.getElementById('amountHint');
const amountError        = document.getElementById('amountError');
const freqSelect         = document.getElementById('repaymentFrequency');
const freqHint           = document.getElementById('freqHint');
const tenureInput        = document.getElementById('tenurePeriods');
const tenureHint         = document.getElementById('tenureHint');

// period_type → {freq value, label, default tenure, tenure label}
const PERIOD_MAP = {
    '1': { freq: 'weekly',  label: 'Weekly',  tenure: 26,  tenureLabel: 'weeks (e.g. 26 weeks ≈ 6 months)' },
    '2': { freq: 'monthly', label: 'Monthly', tenure: 12,  tenureLabel: 'months (e.g. 12 months = 1 year)' },
    '3': { freq: 'daily',   label: 'Daily',   tenure: 30,  tenureLabel: 'days (e.g. 30 days = 1 month)' },
};

function updateAmountValidation() {
    const raw = parseInt(amountHidden.value) || 0;
    const opt = productSelect.options[productSelect.selectedIndex];
    const max = opt ? parseInt(opt.dataset.max || 0) : 0;
    if (max > 0 && raw > max) {
        amountError.textContent = '⚠️ Amount exceeds the maximum of UGX ' + max.toLocaleString() + ' for this product.';
        amountError.style.display = '';
        amountDisplay.classList.add('is-invalid');
    } else {
        amountError.style.display = 'none';
        amountDisplay.classList.remove('is-invalid');
    }
}

// Comma formatter for the requested amount display input
amountDisplay.addEventListener('input', function () {
    let raw = this.value.replace(/[^0-9]/g, '');
    amountHidden.value = raw;
    this.value = raw ? parseInt(raw).toLocaleString() : '';
    updateAmountValidation();
});

// ── Generic UGX display/hidden field wiring ────────────────────────────
// Each pair: [displayId, hiddenId]
const UGX_FIELD_PAIRS = [
    ['daily_sales_claimedDisp',        'daily_sales_claimedHid'],
    ['monthly_cogs_claimedDisp',       'monthly_cogs_claimedHid'],
    ['business_expenses_claimedDisp',  'business_expenses_claimedHid'],
    ['household_expenses_claimedDisp', 'household_expenses_claimedHid'],
    ['other_income_claimedDisp',       'other_income_claimedHid'],
    ['collateral_1_client_valueDisp',  'collateral_1_client_valueHid'],
    ['collateral_2_client_valueDisp',  'collateral_2_client_valueHid'],
];
UGX_FIELD_PAIRS.forEach(function([dispId, hidId]) {
    const disp = document.getElementById(dispId);
    const hid  = document.getElementById(hidId);
    if (!disp || !hid) return;
    disp.addEventListener('input', function() {
        let raw = this.value.replace(/[^0-9]/g, '');
        hid.value = raw || '0';
        this.value = raw ? parseInt(raw).toLocaleString() : '';
        // Clear error state on input
        this.classList.remove('is-invalid');
    });
    disp.addEventListener('blur', function() {
        // Re-format on blur (e.g. user pasted something)
        let raw = this.value.replace(/[^0-9]/g, '');
        hid.value = raw || '0';
        this.value = raw ? parseInt(raw).toLocaleString() : '0';
    });
});

function onProductChange() {
    const opt  = productSelect.options[productSelect.selectedIndex];
    if (!opt || !opt.value) return;

    const max        = parseInt(opt.dataset.max || 0);
    const rate       = parseFloat(opt.dataset.interest || 0);
    const periodType = opt.dataset.periodType || '1';
    const pm         = PERIOD_MAP[periodType] || PERIOD_MAP['1'];

    // ── Amount hint ──────────────────────────────────────────
    if (max > 0) {
        amountHint.textContent = 'Max: UGX ' + max.toLocaleString() + ' · Interest: ' + rate.toFixed(1) + '% per period';
        amountHint.className = 'form-text text-primary fw-semibold';
    }
    updateAmountValidation();

    // ── Auto-lock repayment frequency ────────────────────────
    for (let i = 0; i < freqSelect.options.length; i++) {
        if (freqSelect.options[i].value === pm.freq) {
            freqSelect.selectedIndex = i;
            break;
        }
    }
    freqSelect.disabled = true;
    freqHint.textContent = 'Auto-set by product (' + pm.label + ')';
    freqHint.className = 'form-text text-success fw-semibold';

    // ── Suggest default tenure ───────────────────────────────
    if (!tenureInput.value) {
        tenureInput.value = pm.tenure;
    }
    tenureHint.textContent = 'Enter number of ' + pm.tenureLabel;
    tenureHint.className = 'form-text text-muted';
}

if (productSelect) {
    productSelect.addEventListener('change', onProductChange);
    // Run on load for old() values
    if (productSelect.value) onProductChange();
    // Re-sync display input from hidden if old() present
    if (amountHidden.value) {
        amountDisplay.value = parseInt(amountHidden.value).toLocaleString();
        updateAmountValidation();
    }
}

// ── Single consolidated submit handler: validate all → freq backup → clear draft ──
document.getElementById('applyForm').addEventListener('submit', function(e) {
    // Validate every step in order; stop at first failure
    for (let s = 0; s < TOTAL_STEPS; s++) {
        if (!validateStep(s)) {
            e.preventDefault();
            currentStep = s;
            showStep(s);
            // Scroll to first invalid field in this step
            const firstInvalid = document.querySelector('#section-' + s + ' .is-invalid');
            if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
    }
    // All valid — inject freq hidden input if select is disabled
    if (freqSelect && freqSelect.disabled) {
        const hid = document.createElement('input');
        hid.type  = 'hidden';
        hid.name  = 'repayment_frequency';
        hid.value = freqSelect.value;
        this.appendChild(hid);
    }
    // Clear localStorage draft so the phone can start fresh next time
    const phoneField = document.getElementById('phoneField');
    if (phoneField && phoneField.value.trim()) {
        try { localStorage.removeItem(DRAFT_PREFIX + phoneField.value.trim()); } catch(ex) {}
    }
});

// Toggle reference fields
const hasRef = document.getElementById('hasRefSelect');
function toggleRef() {
    document.querySelectorAll('.ref-fields').forEach(el => {
        el.style.display = (hasRef && hasRef.value == '1') ? '' : 'none';
    });
}
if (hasRef) {
    hasRef.addEventListener('change', toggleRef);
    toggleRef();
}

// Toggle external loan fields
const hasExternal = document.getElementById('hasExternalLoans');
function toggleExternal() {
    document.querySelectorAll('.external-fields').forEach(el => {
        el.style.display = (hasExternal && hasExternal.value == '1') ? '' : 'none';
    });
}
if (hasExternal) {
    hasExternal.addEventListener('change', toggleExternal);
    toggleExternal();
}

// ── Business type "Other" → custom text input ─────────────────────────
const bizTypeSelect = document.getElementById('businessTypeSelect');
const bizTypeCustom = document.getElementById('businessTypeCustom');
function toggleBizCustom() {
    const isOther = bizTypeSelect && bizTypeSelect.value === 'Other';
    if (!bizTypeCustom) return;
    bizTypeCustom.style.display = isOther ? '' : 'none';
    bizTypeCustom.required = isOther;
}
if (bizTypeSelect) {
    bizTypeSelect.addEventListener('change', toggleBizCustom);
    toggleBizCustom(); // handle old() restoration on page reload with errors
}

// ── Save & Resume via localStorage + sessionStorage ──────────────────
// localStorage  = persists across browser sessions (keyed on phone number)
// sessionStorage = survives tab refresh but clears on browser close (temp key)
const DRAFT_PREFIX  = 'emuria_loan_draft_';
const SESSION_DRAFT = 'emuria_draft_session';
const SESSION_PHONE = 'emuria_draft_session_phone';

function showSaveIndicator() {
    const ind = document.getElementById('draftSaveIndicator');
    if (!ind) return;
    ind.style.opacity = '1';
    setTimeout(function() { ind.style.opacity = '0'; }, 2500);
}

function getFormSnapshot() {
    const form = document.getElementById('applyForm');
    const data = {};
    form.querySelectorAll('input:not([type=file]):not([type=submit]):not([type=button]), select, textarea').forEach(function(el) {
        if (!el.name || el.name === '_token') return;
        if (el.type === 'checkbox' || el.type === 'radio') {
            if (el.checked) data[el.name] = el.value;
        } else {
            data[el.name] = el.value;
        }
    });
    data._step    = currentStep;          // remember which step they were on
    data._savedAt = new Date().toISOString();
    return data;
}

function saveDraft() {
    const phoneField = document.getElementById('phoneField');
    const phone = phoneField ? phoneField.value.trim() : '';
    const snapshot = getFormSnapshot();
    // Always save to sessionStorage (survives page refresh in same tab)
    try {
        sessionStorage.setItem(SESSION_DRAFT, JSON.stringify(snapshot));
        if (phone) sessionStorage.setItem(SESSION_PHONE, phone);
    } catch(e) {}
    // Save to localStorage under phone key (persists after browser close)
    if (phone) {
        try {
            localStorage.setItem(DRAFT_PREFIX + phone, JSON.stringify(snapshot));
            showSaveIndicator();
        } catch(e) {}
    }
}

function restoreDraft(phone) {
    // Prefer localStorage (cross-session), fall back to sessionStorage
    let raw = phone ? localStorage.getItem(DRAFT_PREFIX + phone) : null;
    if (!raw) raw = sessionStorage.getItem(SESSION_DRAFT);
    if (!raw) return false;
    try {
        const data = JSON.parse(raw);
        const form = document.getElementById('applyForm');
        // Restore all named form fields
        Object.entries(data).forEach(function([name, value]) {
            if (name.startsWith('_')) return; // skip meta keys (_step, _savedAt)
            form.querySelectorAll('[name="' + name + '"]').forEach(function(el) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = (el.value === value);
                } else {
                    el.value = value;
                    el.dispatchEvent(new Event('change'));
                }
            });
        });
        // Refresh UGX display fields from their restored hidden counterparts
        UGX_FIELD_PAIRS.forEach(function([dispId, hidId]) {
            const disp = document.getElementById(dispId);
            const hid  = document.getElementById(hidId);
            if (disp && hid) {
                const n = parseInt(hid.value);
                disp.value = (!isNaN(n) && n > 0) ? n.toLocaleString() : (hid.value === '0' ? '0' : '');
            }
        });
        // Refresh requested amount display
        if (data.requested_amount) {
            const disp = document.getElementById('requestedAmountDisplay');
            if (disp) { disp.value = parseInt(data.requested_amount).toLocaleString(); updateAmountValidation(); }
        }
        // Jump back to the step they were on
        if (data._step !== undefined) {
            currentStep = parseInt(data._step) || 0;
            showStep(currentStep);
        }
        return true;
    } catch(e) { return false; }
}

function checkApplicationStatus(phone, callback) {
    fetch('/apply/check-status?phone=' + encodeURIComponent(phone))
        .then(function(r) { return r.json(); })
        .then(callback)
        .catch(function() { callback({ status: 'none' }); });
}

function resumeFromLanding() {
    const phoneInput = document.getElementById('resumePhoneInput');
    const phone = phoneInput ? phoneInput.value.trim() : '';
    if (!phone) {
        if (phoneInput) phoneInput.classList.add('is-invalid');
        return;
    }
    phoneInput.classList.remove('is-invalid');
    // Hide all result panels while checking
    ['resumeChecking','resumePending','resumeNotFound'].forEach(function(id) {
        document.getElementById(id).style.display = 'none';
    });
    document.getElementById('resumeChecking').style.display = '';
    document.getElementById('resumeBtn').disabled = true;

    checkApplicationStatus(phone, function(data) {
        document.getElementById('resumeChecking').style.display = 'none';
        document.getElementById('resumeBtn').disabled = false;

        if (data.status === 'pending') {
            // Application submitted but not yet approved/rejected — inform user
            document.getElementById('resumePendingCode').textContent   = data.application_code;
            document.getElementById('resumePendingDate').textContent   = data.submitted_at;
            document.getElementById('resumePendingStatus').textContent = data.status_label;
            document.getElementById('resumePending').style.display     = '';
            return;
        }
        // Not pending — check for a local draft
        const hasLocal   = !!localStorage.getItem(DRAFT_PREFIX + phone);
        const hasSession = !!sessionStorage.getItem(SESSION_DRAFT);
        if (!hasLocal && !hasSession) {
            document.getElementById('resumeNotFound').style.display = '';
            return;
        }
        showForm();
        setTimeout(function() {
            const phoneField = document.getElementById('phoneField');
            if (phoneField) phoneField.value = phone;
            restoreDraft(phone);
            showResumeToast(phone);
        }, 100);
    });
}

function showResumeToast(phone) {
    const toast = document.getElementById('resumeToast');
    if (toast && typeof bootstrap !== 'undefined') {
        document.getElementById('resumeToastBody').textContent =
            'Draft restored for ' + phone + '. Please re-upload any required documents.';
        new bootstrap.Toast(toast, { delay: 7000 }).show();
    }
}

// ── Save triggers ─────────────────────────────────────────────────────
// 1. Debounced auto-save 1.5s after any field changes
let _draftTimer = null;
document.getElementById('applyForm').addEventListener('input',  function() { clearTimeout(_draftTimer); _draftTimer = setTimeout(saveDraft, 1500); });
document.getElementById('applyForm').addEventListener('change', function() { clearTimeout(_draftTimer); _draftTimer = setTimeout(saveDraft, 1500); });

// 2. Immediate save when user closes/refreshes the browser tab
window.addEventListener('beforeunload', saveDraft);

// 3. On page load (no server errors): if a session draft phone exists, pre-fill
//    the resume input on the landing page so the user just clicks "Continue"
@if (!$errors->any())
document.addEventListener('DOMContentLoaded', function() {
    const sessionPhone = sessionStorage.getItem(SESSION_PHONE);
    if (sessionPhone) {
        const inp = document.getElementById('resumePhoneInput');
        if (inp) inp.value = sessionPhone;
    }
});
@endif

// When phone field loses focus inside the form, check for an existing draft OR pending app
const phoneFormField = document.getElementById('phoneField');
if (phoneFormField) {
    phoneFormField.addEventListener('blur', function() {
        const phone = this.value.trim();
        const panel = document.getElementById('inlineRestorePanel');
        if (!phone || !panel) return;
        panel.style.display = 'none';

        checkApplicationStatus(phone, function(data) {
            if (data.status === 'pending') {
                // Overwrite the restore panel to show pending warning instead
                document.getElementById('inlineRestorePhone').textContent = phone;
                panel.querySelector('span:first-child').innerHTML =
                    '<i class="fas fa-clock me-1"></i>Your application <strong>' + data.application_code + '</strong> is already submitted and <strong>' + data.status_label + '</strong>. Our team will contact you — please do not submit again.';
                panel.className = 'alert alert-warning py-2 px-3 mt-2 small';
                panel.style.display = '';
                document.getElementById('inlineRestoreConfirmBtn').style.display = 'none';
                document.getElementById('inlineRestoreDismissBtn').onclick = function() { panel.style.display = 'none'; };
                return;
            }
            // Reset panel style in case it was changed
            panel.className = 'alert alert-info py-2 px-3 mt-2 small d-flex align-items-center justify-content-between';
            document.getElementById('inlineRestoreConfirmBtn').style.display = '';

            const hasDraft = localStorage.getItem(DRAFT_PREFIX + phone) ||
                             (sessionStorage.getItem(SESSION_PHONE) === phone && sessionStorage.getItem(SESSION_DRAFT));
            if (hasDraft) {
                document.getElementById('inlineRestorePhone').textContent = phone;
                panel.querySelector('span:first-child').innerHTML =
                    '<i class="fas fa-history me-1"></i>A saved draft was found for <strong id="inlineRestorePhone">' + phone + '</strong>. Restore it?';
                panel.style.display = '';
                document.getElementById('inlineRestoreConfirmBtn').onclick = function() {
                    restoreDraft(phone);
                    panel.style.display = 'none';
                    showResumeToast(phone);
                };
                document.getElementById('inlineRestoreDismissBtn').onclick = function() {
                    panel.style.display = 'none';
                };
            }
        });
    });
}

// ── Collateral 2 toggle ───────────────────────────────────────────────
function showCollateral2() {
    document.getElementById('collateral2Section').style.display = '';
    document.getElementById('addCollateral2Wrap').style.display = 'none';
    // Enable all collateral 2 fields
    document.querySelectorAll('#collateral2Section input, #collateral2Section select').forEach(function(el) {
        el.disabled = false;
    });
}
function removeCollateral2() {
    document.getElementById('collateral2Section').style.display = 'none';
    document.getElementById('addCollateral2Wrap').style.display = '';
    // Clear and disable collateral 2 fields so they aren't submitted or validated
    document.querySelectorAll('#collateral2Section input[name^="collateral_2_"], #collateral2Section select[name^="collateral_2_"]').forEach(function(el) {
        el.value = el.tagName === 'SELECT' ? (el.querySelector('option') ? el.querySelector('option').value : '') : '';
        el.disabled = true;
    });
    var hid = document.getElementById('collateral_2_client_valueHid');
    if (hid) hid.value = '0';
    var disp = document.getElementById('collateral_2_client_valueDisp');
    if (disp) disp.value = '';
}

// ── Guarantor 2 toggle ───────────────────────────────────────────────
function showGuarantor2() {
    document.getElementById('guarantor2Section').style.display = '';
    document.getElementById('addGuarantor2Wrap').style.display = 'none';
}
function removeGuarantor2() {
    var sec = document.getElementById('guarantor2Section');
    sec.style.display = 'none';
    document.getElementById('addGuarantor2Wrap').style.display = '';
    // Clear all guarantor 2 fields so they aren't submitted
    sec.querySelectorAll('input[name^="guarantor_2_"], select[name^="guarantor_2_"]').forEach(function(el) {
        el.value = el.tagName === 'SELECT' ? (el.querySelector('option') ? el.querySelector('option').value : '') : '';
    });
}

function checkLoanStatus() {
    const phoneInput = document.getElementById('lsPhoneInput');
    const phone = phoneInput ? phoneInput.value.trim() : '';
    if (!phone) { phoneInput.classList.add('is-invalid'); return; }
    phoneInput.classList.remove('is-invalid');

    const btn = document.getElementById('lsBtn');
    btn.disabled = true;
    ['lsChecking','lsNotFound','lsResult'].forEach(function(id) {
        document.getElementById(id).style.display = 'none';
    });
    document.getElementById('lsChecking').style.display = '';

    fetch('{{ route("client.loan-status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '{{ csrf_token() }}'
        },
        body: JSON.stringify({ phone: phone })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('lsChecking').style.display = 'none';
        btn.disabled = false;

        if (!data.found) {
            document.getElementById('lsNotFoundMsg').textContent = data.message || 'No active loan found for that phone number.';
            document.getElementById('lsNotFound').style.display = '';
            return;
        }

        // Populate summary
        document.getElementById('lsMemberName').textContent   = data.member_name;
        document.getElementById('lsLoanCode').textContent     = data.loan.code;
        document.getElementById('lsProduct').textContent      = data.loan.product;
        document.getElementById('lsPrincipal').textContent    = data.loan.principal;
        document.getElementById('lsTotalPaid').textContent    = data.loan.total_paid;
        document.getElementById('lsOutstanding').textContent  = data.loan.outstanding;

        var badge = document.getElementById('lsStatusBadge');
        badge.textContent = data.loan.status;
        badge.className   = 'badge fs-6 px-3 py-2 bg-' + data.loan.status_color;

        var overdueEl = document.getElementById('lsOverdueAlert');
        if (data.loan.overdue > 0) {
            document.getElementById('lsOverdueCount').textContent = data.loan.overdue;
            overdueEl.style.display = '';
        } else {
            overdueEl.style.display = 'none';
        }

        var nextEl = document.getElementById('lsNextDue');
        if (data.loan.next_due_date) {
            document.getElementById('lsNextDueDate').textContent = data.loan.next_due_date;
            document.getElementById('lsNextDueAmt').textContent  = data.loan.next_due_amount;
            nextEl.style.display = '';
        } else {
            nextEl.style.display = 'none';
        }

        // Populate schedule table
        var tbody = document.getElementById('lsScheduleBody');
        tbody.innerHTML = '';
        (data.schedules || []).forEach(function(s) {
            var statusClass = { 'Paid': 'success', 'Overdue': 'danger', 'Pending': 'secondary' }[s.status] || 'secondary';
            tbody.innerHTML += '<tr>' +
                '<td class="ps-3 text-muted">' + s.installment + '</td>' +
                '<td>' + s.due_date + '</td>' +
                '<td class="text-end">' + s.amount + '</td>' +
                '<td class="text-end text-success">' + s.paid + '</td>' +
                '<td class="text-end text-danger">' + s.balance + '</td>' +
                '<td class="text-center"><span class="badge bg-' + statusClass + '">' + s.status + '</span></td>' +
                '</tr>';
        });
        if (!data.schedules || data.schedules.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No schedule yet — loan may not be disbursed.</td></tr>';
        }

        document.getElementById('lsResult').style.display = '';
        document.getElementById('lsResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(function() {
        document.getElementById('lsChecking').style.display = 'none';
        btn.disabled = false;
        document.getElementById('lsNotFoundMsg').textContent = 'Unable to connect. Please try again.';
        document.getElementById('lsNotFound').style.display = '';
    });
}
</script>
</body>
</html>
