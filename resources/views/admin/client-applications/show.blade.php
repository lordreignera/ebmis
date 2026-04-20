@extends('layouts.admin')

@section('title', 'Application: ' . $app->application_code)

@section('content')
<div class="content-wrapper">
  <div class="page-header">
    <h3 class="page-title">
      <span class="page-title-icon bg-gradient-primary text-white me-2">
        <i class="mdi mdi-file-document-outline"></i>
      </span>
      Application: <code>{{ $app->application_code }}</code>
    </h3>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('admin/home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.client-applications.index') }}">Self-Applied</a></li>
        <li class="breadcrumb-item active">{{ $app->application_code }}</li>
      </ol>
    </nav>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  {{-- Top Status Bar --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="text-muted small">Status</div>
        <span class="badge bg-{{ $app->statusBadgeClass() }} fs-6 mt-1">{{ $app->statusLabel() }}</span>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="text-muted small">Traffic Light</div>
        <div class="mt-1 fw-bold text-{{ $app->trafficLightClass() }} fs-5">
          @if($app->traffic_light)
          <span style="display:inline-block;width:16px;height:16px;border-radius:50%;background:{{ $app->traffic_light === 'GREEN' ? '#198754' : ($app->traffic_light === 'YELLOW' ? '#ffc107' : '#dc3545') }};vertical-align:middle;margin-right:6px;"></span>
          {{ $app->traffic_light }}
          @else
          <span class="text-muted">Not Scored</span>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="text-muted small">SDL Score</div>
        @php $sdlHero = $app->sdl_score ?? $app->composite_score; @endphp
        <div class="fs-3 fw-bold mt-1 text-{{ $sdlHero >= 75 ? 'success' : ($sdlHero >= 60 ? 'warning' : 'danger') }}">
          {{ $sdlHero ?? '—' }}<span class="fs-6 text-muted">/100</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="text-muted small">Final Decision</div>
        @php
          $fdHero = $app->final_decision;
          $fdHeroColor = match($fdHero) { 'APP' => 'success', 'ARA' => 'warning', 'CON' => 'info', 'DECLINE' => 'danger', default => 'secondary' };
        @endphp
        <div class="fs-5 fw-bold mt-1 text-{{ $fdHeroColor }}">
          {{ $fdHero ?? ($app->traffic_light ? $app->risk_band.' Risk' : '—') }}
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">

    {{-- Left Column: Application Data --}}
    <div class="col-lg-7">

      {{-- Loan Details --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-primary text-white py-2">
          <i class="fas fa-hand-holding-usd me-2"></i>Loan Details
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><th width="45%">Application Code</th><td><code>{{ $app->application_code }}</code></td></tr>
            <tr><th>Product</th><td>{{ $app->product?->name ?? '—' }}</td></tr>
            <tr><th>Requested Amount</th><td><strong>UGX {{ number_format($app->requested_amount) }}</strong></td></tr>
            <tr><th>Tenure</th><td>{{ $app->tenure_periods }} periods ({{ ucfirst($app->repayment_frequency) }})</td></tr>
            <tr><th>Loan Purpose</th><td>{{ $app->loan_purpose ?? '—' }}</td></tr>
            <tr><th>Disbursement Method</th><td>{{ $app->preferred_disbursement_method ?? '—' }}</td></tr>
            <tr><th>Branch</th><td>{{ $app->branch?->name ?? '—' }}</td></tr>
            <tr><th>Submitted</th><td>{{ $app->created_at->format('d M Y, h:i A') }}</td></tr>
          </table>
        </div>
      </div>

      {{-- Applicant --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-info text-white py-2">
          <i class="fas fa-user me-2"></i>Applicant Information
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><th width="45%">Full Name</th><td>{{ $app->full_name }}</td></tr>
            <tr><th>Phone</th><td>{{ $app->phone }}</td></tr>
            <tr><th>National ID (NIN)</th><td>{{ $app->national_id ?? '—' }}</td></tr>
            <tr><th>Date of Birth</th><td>{{ $app->date_of_birth ? $app->date_of_birth->format('d M Y') : '—' }}</td></tr>
            <tr><th>Gender</th><td>{{ $app->gender ?? '—' }}</td></tr>
            <tr><th>Email</th><td>{{ $app->email ?? '—' }}</td></tr>
          </table>
        </div>
      </div>

      {{-- Residence --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-secondary text-white py-2">
          <i class="fas fa-map-marker-alt me-2"></i>Residence, LC1 & Reference
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><th width="45%">Village</th><td>{{ $app->residence_village }}</td></tr>
            <tr><th>Parish</th><td>{{ $app->residence_parish }}</td></tr>
            <tr><th>Sub-county</th><td>{{ $app->residence_subcounty }}</td></tr>
            <tr><th>District</th><td>{{ $app->residence_district }}</td></tr>
            <tr><th>Landmark</th><td>{{ $app->landmark_directions ?? '—' }}</td></tr>
            <tr><th>Years at Residence</th><td>{{ $app->years_at_residence ?? '—' }}</td></tr>
            <tr><th>LC1 Name</th><td>{{ $app->lc1_name ?? '—' }}</td></tr>
            <tr><th>LC1 Phone</th><td>{{ $app->lc1_phone ?? '—' }}</td></tr>
            <tr><th>Has Reference?</th><td>{{ $app->has_local_reference ? 'Yes' : 'No' }}</td></tr>
            @if($app->has_local_reference)
            <tr><th>Reference Name</th><td>{{ $app->reference_name }}</td></tr>
            <tr><th>Reference Phone</th><td>{{ $app->reference_phone }}</td></tr>
            <tr><th>Relationship</th><td>{{ $app->reference_relationship }}</td></tr>
            @endif
          </table>
        </div>
      </div>

      {{-- Business --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-warning text-dark py-2">
          <i class="fas fa-store me-2"></i>Business Profile
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><th width="45%">Business Name</th><td>{{ $app->business_name }}</td></tr>
            <tr><th>Business Type</th><td>{{ $app->business_type ?? '—' }}</td></tr>
            <tr><th>Location</th><td>{{ $app->business_location ?? '—' }}</td></tr>
            <tr><th>Years in Operation</th><td>{{ $app->business_years_operation ?? '—' }}</td></tr>
            <tr><th>Avg. Daily Customers</th><td>{{ $app->avg_daily_customers ?? '—' }}</td></tr>
            <tr><th>Description</th><td>{{ $app->business_description ?? '—' }}</td></tr>
          </table>

        </div>
      </div>

      {{-- ── Uploaded Documents Card ────────────────────────────────── --}}
      @php
        $allDocs = [
          'community' => [
            'label' => 'Community / Identity',
            'icon'  => 'fa-id-card',
            'docs'  => [
              ['chairman_letter', 'LC1 Chairman Letter', true],
            ],
          ],
          'business' => [
            'label' => 'Business Evidence',
            'icon'  => 'fa-store',
            'docs'  => [
              ['business_profile_photo',   'Business Profile Photo', false],
              ['business_activity_photos', 'Business Activity Photos', false],
              ['inventory_photos',          'Inventory Photos', false],
              ['sales_book_photo',          'Sales Book', false],
              ['purchases_book_photo',      'Purchases Book', false],
              ['expense_records_photo',     'Expense Records', false],
              ['mobile_money_statements',  'Mobile Money Statements', false],
            ],
          ],
          'collateral' => [
            'label' => 'Collateral Documents',
            'icon'  => 'fa-shield-alt',
            'docs'  => [
              ['collateral_1_doc_photo', 'Collateral 1 Document', false],
              ['collateral_2_doc_photo', 'Collateral 2 Document', false],
            ],
          ],
        ];
      @endphp
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2" style="background:#6f2da8; color:white">
          <i class="fas fa-folder-open me-2"></i>Uploaded Documents
        </div>
        <div class="card-body">
          @foreach($allDocs as $group)
          <div class="mb-3">
            <div class="d-flex align-items-center mb-2">
              <i class="fas {{ $group['icon'] }} text-muted me-2"></i>
              <strong class="small text-muted text-uppercase" style="letter-spacing:.05em">{{ $group['label'] }}</strong>
            </div>
            <div class="row g-2">
              @foreach($group['docs'] as [$field, $label, $required])
              @php $path = $app->$field; @endphp
              <div class="col-md-4 col-sm-6">
                @if($path)
                  @php
                    $url = \App\Services\FileStorageService::getFileUrl($path);
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                  @endphp
                  <a href="{{ $url }}" target="_blank"
                     class="d-flex align-items-center gap-2 p-2 rounded border border-success text-decoration-none text-success"
                     style="background:#f0fff4; font-size:.82rem; word-break:break-word">
                    @if($isImg)
                      <img src="{{ $url }}" alt="{{ $label }}"
                           style="width:44px; height:44px; object-fit:cover; border-radius:4px; flex-shrink:0"
                           onerror="this.style.display='none'">
                    @else
                      <span style="width:44px; height:44px; background:#d4edda; border-radius:4px; display:flex; align-items:center; justify-content:center; flex-shrink:0">
                        <i class="fas fa-file-pdf text-danger fs-5"></i>
                      </span>
                    @endif
                    <span>
                      <strong class="d-block">{{ $label }}</strong>
                      <span class="text-muted" style="font-size:.72rem">Click to view</span>
                    </span>
                    <i class="fas fa-external-link-alt ms-auto text-muted" style="font-size:.7rem"></i>
                  </a>
                @else
                  <div class="d-flex align-items-center gap-2 p-2 rounded border text-muted"
                       style="background:#f8f9fa; font-size:.82rem; border-color:#dee2e6 !important">
                    <span style="width:44px; height:44px; background:#e9ecef; border-radius:4px; display:flex; align-items:center; justify-content:center; flex-shrink:0">
                      <i class="fas fa-file-times" style="color:#adb5bd"></i>
                    </span>
                    <span>
                      <strong class="d-block">{{ $label }}</strong>
                      <span style="font-size:.72rem">{{ $required ? '⚠️ Required — not uploaded' : 'Not uploaded' }}</span>
                    </span>
                  </div>
                @endif
              </div>
              @endforeach
            </div>
          </div>
          @if(!$loop->last)<hr class="my-2">@endif
          @endforeach
        </div>
      </div>

      {{-- Financials --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-success text-white py-2">
          <i class="fas fa-coins me-2"></i>Client Financial Claims
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><th width="55%">Monthly Sales (DMS)</th><td>UGX {{ number_format($app->daily_sales_claimed) }}</td></tr>
            <tr><th>Monthly COGS (DMCOGS)</th><td>UGX {{ number_format($app->monthly_cogs_claimed ?? 0) }}</td></tr>
            <tr><th>Monthly Operating Expenses (DMOE)</th><td>UGX {{ number_format($app->business_expenses_claimed) }}</td></tr>
            <tr><th>Monthly Household Expenses (DMHE)</th><td>UGX {{ number_format($app->household_expenses_claimed) }}</td></tr>
            <tr><th>Other Monthly Income (DOMI)</th><td>UGX {{ number_format($app->other_income_claimed) }}</td></tr>
            <tr><th>Has External Loans?</th><td>{{ $app->has_external_loans ? 'Yes' : 'No' }}</td></tr>
            @if($app->has_external_loans)
            <tr><th>External Lenders</th><td>{{ $app->external_lenders_count }}</td></tr>
            <tr><th>External Outstanding</th><td>UGX {{ number_format($app->external_outstanding) }}</td></tr>
            <tr><th>External Install./Period</th><td>UGX {{ number_format($app->external_installment_per_period) }}</td></tr>
            <tr><th>Max Arrears Days</th><td>{{ $app->max_external_arrears_days }}</td></tr>
            @endif
          </table>
        </div>
      </div>

      {{-- Collateral --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-dark text-white py-2">
          <i class="fas fa-shield-alt me-2"></i>Collateral
        </div>
        <div class="card-body">
          <strong class="small text-muted">Collateral 1</strong>
          <table class="table table-sm table-borderless mb-3 mt-1">
            <tr><th width="45%">Type</th><td>{{ $app->collateral_1_type }}</td></tr>
            <tr><th>Description</th><td>{{ $app->collateral_1_description }}</td></tr>
            <tr><th>Owner</th><td>{{ $app->collateral_1_owner_name }}</td></tr>
            <tr><th>Ownership Status</th><td>{{ $app->collateral_1_ownership_status }}</td></tr>
            <tr><th>Document Type</th><td>{{ $app->collateral_1_doc_type }}</td></tr>
            <tr><th>Document Number</th><td>{{ $app->collateral_1_doc_number }}</td></tr>
            <tr><th>Client Estimated Value</th><td>UGX {{ number_format($app->collateral_1_client_value) }}</td></tr>
            <tr><th>FSV (System)</th><td><strong>UGX {{ number_format($app->fsv_collateral_1 ?? 0) }}</strong></td></tr>
          </table>

          @if($app->collateral_2_type)
          <hr>
          <strong class="small text-muted">Collateral 2</strong>
          <table class="table table-sm table-borderless mb-0 mt-1">
            <tr><th width="45%">Type</th><td>{{ $app->collateral_2_type }}</td></tr>
            <tr><th>Description</th><td>{{ $app->collateral_2_description }}</td></tr>
            <tr><th>Owner</th><td>{{ $app->collateral_2_owner_name }}</td></tr>
            <tr><th>Document Number</th><td>{{ $app->collateral_2_doc_number }}</td></tr>
            <tr><th>Client Estimated Value</th><td>UGX {{ number_format($app->collateral_2_client_value) }}</td></tr>
            <tr><th>FSV (System)</th><td><strong>UGX {{ number_format($app->fsv_collateral_2 ?? 0) }}</strong></td></tr>
          </table>
          @endif
        </div>
      </div>

      {{-- Guarantors --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-primary text-white py-2">
          <i class="fas fa-users me-2"></i>Guarantors
        </div>
        <div class="card-body">
          <strong class="small text-muted">Guarantor 1</strong>
          <table class="table table-sm table-borderless mb-3 mt-1">
            <tr><th width="45%">Name</th><td>{{ $app->guarantor_1_name }}</td></tr>
            <tr><th>Relationship</th><td>{{ $app->guarantor_1_relationship }}</td></tr>
            <tr><th>Phone</th><td>{{ $app->guarantor_1_phone }}</td></tr>
            <tr><th>Commitment Level</th><td><span class="badge bg-{{ $app->guarantor_1_commitment_level === 'High' ? 'success' : ($app->guarantor_1_commitment_level === 'Moderate' ? 'warning' : 'secondary') }}">{{ $app->guarantor_1_commitment_level }}</span></td></tr>
            <tr><th>Pledge Description</th><td>{{ $app->guarantor_1_pledge_description ?? '—' }}</td></tr>
            <tr><th>Pledged Asset Value</th><td>UGX {{ number_format($app->guarantor_1_pledged_asset_value) }}</td></tr>
            <tr><th>Signed Consent?</th><td>{{ $app->guarantor_1_signed_consent ? '✓ Yes' : '✗ No' }}</td></tr>
          </table>

          @if($app->guarantor_2_name)
          <hr>
          <strong class="small text-muted">Guarantor 2</strong>
          <table class="table table-sm table-borderless mb-0 mt-1">
            <tr><th width="45%">Name</th><td>{{ $app->guarantor_2_name }}</td></tr>
            <tr><th>Relationship</th><td>{{ $app->guarantor_2_relationship }}</td></tr>
            <tr><th>Phone</th><td>{{ $app->guarantor_2_phone }}</td></tr>
            <tr><th>Commitment Level</th><td><span class="badge bg-{{ $app->guarantor_2_commitment_level === 'High' ? 'success' : ($app->guarantor_2_commitment_level === 'Moderate' ? 'warning' : 'secondary') }}">{{ $app->guarantor_2_commitment_level }}</span></td></tr>
            <tr><th>Pledged Asset Value</th><td>UGX {{ number_format($app->guarantor_2_pledged_asset_value) }}</td></tr>
            <tr><th>Signed Consent?</th><td>{{ $app->guarantor_2_signed_consent ? '✓ Yes' : '✗ No' }}</td></tr>
          </table>
          @endif
        </div>
      </div>

    </div>{{-- /left column --}}

    {{-- Right Column: Scoring + Decision --}}
    <div class="col-lg-5">

      {{-- System Decision Layer --}}
      @php
        $sdl = $app->sdl_score ?? $app->composite_score ?? null;
        $fd  = $app->final_decision;
        $fdColor = match($fd) { 'APP' => 'success', 'ARA' => 'warning', 'CON' => 'info', 'DECLINE' => 'danger', default => 'secondary' };
        $fdLabel = match($fd) { 'APP' => 'APP – Full Approval', 'ARA' => 'ARA – Reduced Amount', 'CON' => 'CON – Conditional', 'DECLINE' => 'DECLINE', default => 'Pending' };
        $scoreColor = fn($v) => $v >= 80 ? 'success' : ($v >= 60 ? 'warning' : 'danger');
        $scoreLabel = fn($v) => $v >= 80 ? 'Pass' : ($v >= 60 ? 'Moderate' : 'Weak');
      @endphp
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background:#1a237e; color:white">
          <span><i class="fas fa-brain me-2"></i>System Decision Layer (SDL)</span>
          @if($sdl !== null)
          <span class="badge bg-{{ $sdl >= 75 ? 'success' : ($sdl >= 60 ? 'warning' : 'danger') }} fs-6">{{ $sdl }}/100</span>
          @endif
        </div>
        <div class="card-body p-0">

          {{-- Component Scorecard --}}
          <table class="table table-sm table-borderless mb-0">
            <thead class="table-light">
              <tr><th class="ps-3">Component</th><th class="text-end">Score</th><th class="text-end pe-2">Weight</th><th class="text-end pe-3">Weighted</th><th class="text-center">Status</th></tr>
            </thead>
            <tbody>
              @php
                $components = [
                  ['KYC', 'Know Your Customer',              $app->kyc_score,  '8.0%',  ($app->kyc_score  ?? 0) * 0.08],
                  ['CF',  'Cash Flow',                       $app->cf_score,   '18.0%', ($app->cf_score   ?? 0) * 0.18],
                  ['IVI', 'In-Person Verification Integrity',$app->ivi_score,  '10.0%', ($app->ivi_score  ?? 0) * 0.10],
                  ['VSS', 'Verification Strength Score',     $app->vss_score,  '8.0%',  ($app->vss_score  ?? 0) * 0.08],
                  ['CRB', 'Credit Reference Bureau',         $app->crb_score,  '10.0%', ($app->crb_score  ?? 0) * 0.10],
                  ['COL', 'Collateral Score',                $app->col_score,  '10.0%', ($app->col_score  ?? 0) * 0.10],
                  ['SS',  'Social Standing',                 $app->ss_score,   '8.0%',  ($app->ss_score   ?? 0) * 0.08],
                  ['GUA', 'Guarantors Score',                $app->guarantor_strength_score, '8.0%', ($app->guarantor_strength_score ?? 0) * 0.08],
                  ['EXP', 'Exposure Risk Score',             $app->exp_score,  '8.0%',  ($app->exp_score  ?? 0) * 0.08],
                  ['RPC', 'Regulatory & Policy Compliance',  $app->rpc_score,  '6.0%',  ($app->rpc_score  ?? 0) * 0.06],
                  ['CRD', 'Credibility Score',               $app->crd_score,  '6.0%',  ($app->crd_score  ?? 0) * 0.06],
                ];
              @endphp
              @foreach($components as [$code, $name, $val, $wt, $wtd])
              <tr>
                <td class="ps-3 small"><strong>({{ $code }})</strong> {{ $name }}</td>
                <td class="text-end small">{{ $val ?? '—' }}</td>
                <td class="text-end small pe-2">{{ $wt }}</td>
                <td class="text-end small pe-3">{{ $val !== null ? number_format($wtd, 1) : '—' }}</td>
                <td class="text-center">
                  @if($val !== null)
                  <span class="badge bg-{{ $val >= 80 ? 'success' : ($val >= 60 ? 'warning' : 'danger') }}">{{ $val >= 80 ? 'Pass' : ($val >= 60 ? 'Moderate' : 'Weak') }}</span>
                  @endif
                </td>
              </tr>
              @endforeach
              <tr class="table-dark">
                <td class="ps-3"><strong>(SDL) System Decision Layer Total</strong></td>
                <td class="text-end fw-bold fs-6" colspan="3">{{ $sdl ?? '—' }}</td>
                <td class="text-center">
                  @if($sdl !== null)
                  <span class="badge bg-{{ $sdl >= 75 ? 'success' : ($sdl >= 65 ? 'warning' : 'danger') }}">{{ $sdl >= 75 ? 'Strong' : ($sdl >= 65 ? 'Moderate' : 'Weak') }}</span>
                  @endif
                </td>
              </tr>
            </tbody>
          </table>

          {{-- Cash Flow & DSCR --}}
          <div class="border-top">
            <div class="px-3 py-2 small fw-semibold text-muted text-uppercase bg-light">Cash Flow, Capacity, and Amount Logic</div>
            <table class="table table-sm table-borderless mb-0 small">
              <tr><td class="ps-3">(VTI) Verified Total Income / Period</td><td class="text-end pe-3">{{ $app->daily_disposable_income ? 'UGX '.number_format($app->daily_disposable_income + ($app->total_debt_per_period ?? 0)) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">(VTE) Verified Total Expenses / Period</td><td class="text-end pe-3">{{ $app->total_debt_per_period ? 'UGX '.number_format($app->total_debt_per_period) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">(VNCF) Verified Net Cash Flow</td><td class="text-end pe-3 fw-semibold">{{ $app->daily_disposable_income ? 'UGX '.number_format($app->daily_disposable_income) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">(RLI) Required Loan Installment</td><td class="text-end pe-3">{{ $app->proposed_installment ? 'UGX '.number_format($app->proposed_installment) : '—' }}</td><td></td></tr>
              <tr>
                <td class="ps-3"><strong>(DSCR) Debt Service Coverage Ratio</strong></td>
                <td class="text-end pe-3 fw-bold">{{ $app->dscr ? $app->dscr.'x' : '—' }}</td>
                <td class="text-center pe-2"><span class="badge bg-{{ ($app->dscr ?? 0) >= 1.2 ? 'success' : (($app->dscr ?? 0) >= 0.8 ? 'warning' : 'danger') }}">{{ ($app->dscr ?? 0) >= 1.2 ? 'Pass' : (($app->dscr ?? 0) >= 0.8 ? 'Below Min' : 'Fail') }}</span></td>
              </tr>
              <tr>
                <td class="ps-3">Stressed DSCR (–20% income)</td>
                <td class="text-end pe-3">{{ $app->stressed_dscr ? $app->stressed_dscr.'x' : '—' }}</td>
                <td class="text-center pe-2"><span class="badge bg-{{ ($app->stressed_dscr ?? 0) >= 0.8 ? 'success' : 'danger' }}">{{ ($app->stressed_dscr ?? 0) >= 0.8 ? 'Pass' : 'ABS Fail' }}</span></td>
              </tr>
              <tr><td class="ps-3">(MAI) Maximum Affordable Installment</td><td class="text-end pe-3">{{ $app->mai ? 'UGX '.number_format($app->mai) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">(MAA) Maximum Affordable Amount</td><td class="text-end pe-3">{{ $app->maa ? 'UGX '.number_format($app->maa) : '—' }}</td><td></td></tr>
            </table>
          </div>

          {{-- Collateral & Coverage --}}
          <div class="border-top">
            <div class="px-3 py-2 small fw-semibold text-muted text-uppercase bg-light">Credit History, Exposure, and Collateral</div>
            <table class="table table-sm table-borderless mb-0 small">
              <tr><td class="ps-3">FSV Collateral 1</td><td class="text-end pe-3">{{ $app->fsv_collateral_1 ? 'UGX '.number_format($app->fsv_collateral_1) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">FSV Collateral 2</td><td class="text-end pe-3">{{ $app->fsv_collateral_2 ? 'UGX '.number_format($app->fsv_collateral_2) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">(TAPFSV) Total Available Pledged FSV</td><td class="text-end pe-3 fw-semibold">{{ $app->fsv_total ? 'UGX '.number_format($app->fsv_total) : '—' }}</td><td></td></tr>
              <tr><td class="ps-3">(MCL) Maximum Collateral Limit</td><td class="text-end pe-3">{{ $app->mcl ? 'UGX '.number_format($app->mcl) : '—' }}</td><td></td></tr>
              <tr>
                <td class="ps-3"><strong>(CCR) Collateral Coverage Ratio</strong></td>
                <td class="text-end pe-3 fw-bold">{{ $app->collateral_coverage ? $app->collateral_coverage.'x' : '—' }}</td>
                <td class="text-center pe-2"><span class="badge bg-{{ ($app->collateral_coverage ?? 0) >= 2 ? 'success' : 'danger' }}">{{ ($app->collateral_coverage ?? 0) >= 2 ? 'Meets Min (200%)' : 'Below 200%' }}</span></td>
              </tr>
              <tr><td class="ps-3">Collateral Saleability Score</td><td class="text-end pe-3">{{ $app->collateral_saleability_score ?? '—' }}/100</td><td></td></tr>
              <tr><td class="ps-3">Guarantor Security Total</td><td class="text-end pe-3">{{ $app->guarantor_security_total ? 'UGX '.number_format($app->guarantor_security_total) : '—' }}</td><td></td></tr>
              <tr>
                <td class="ps-3">Fraud Flag Count</td>
                <td class="text-end pe-3">{{ $app->fraud_flag_count ?? 0 }}</td>
                <td class="text-center pe-2"><span class="badge bg-{{ ($app->fraud_flag_count ?? 0) < 3 ? 'success' : 'danger' }}">{{ ($app->fraud_flag_count ?? 0) < 3 ? 'Below Threshold' : 'EXCEEDED' }}</span></td>
              </tr>
            </table>
          </div>

          {{-- Final Decision --}}
          <div class="border-top">
            <div class="px-3 py-2 small fw-semibold text-muted text-uppercase bg-light">Final Decision and Reasons</div>
            <table class="table table-sm table-borderless mb-0 small">
              <tr>
                <td class="ps-3"><strong>Final Decision</strong></td>
                <td class="text-end pe-3" colspan="2">
                  @if($fd)
                  <span class="badge bg-{{ $fdColor }} fs-6 px-3">{{ $fdLabel }}</span>
                  @else
                  <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="ps-3"><strong>(FAA) Final Allowable Amount</strong></td>
                <td class="text-end pe-3 fw-bold text-success" colspan="2">{{ $app->faa ? 'UGX '.number_format($app->faa) : '—' }}</td>
              </tr>
              <tr>
                <td class="ps-3"><strong>Approved Amount</strong></td>
                <td class="text-end pe-3 fw-bold" colspan="2">{{ $app->approved_amount ? 'UGX '.number_format($app->approved_amount) : '—' }}</td>
              </tr>
              <tr>
                <td class="ps-3">Traffic Light</td>
                <td class="text-end pe-3" colspan="2">
                  <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $app->traffic_light === 'GREEN' ? '#198754' : ($app->traffic_light === 'YELLOW' ? '#ffc107' : '#dc3545') }};vertical-align:middle;margin-right:4px;"></span>
                  <strong class="text-{{ $app->trafficLightClass() }}">{{ $app->traffic_light ?? 'N/A' }}</strong>
                </td>
              </tr>
            </table>
          </div>

        </div>
      </div>

      {{-- Hard Policy Gates --}}
      @if($app->gate_status)
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2 bg-dark text-white">
          <i class="fas fa-traffic-light me-2"></i>Hard Policy Gates
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-borderless mb-0">
            @foreach($app->gate_status as $key => $gate)
            <tr>
              <td class="ps-3">{{ $gate['label'] }}</td>
              <td class="text-end pe-3">
                <span class="badge bg-{{ $gate['status'] === 'PASS' ? 'success' : ($gate['status'] === 'BLOCK' ? 'danger' : 'warning') }}">
                  {{ $gate['status'] }}
                </span>
              </td>
            </tr>
            @endforeach
          </table>
        </div>
      </div>
      @endif

      {{-- System Notes --}}
      @if($app->system_notes)
      <div class="alert alert-light border small mb-3">
        <i class="fas fa-info-circle me-1 text-primary"></i>
        <strong>System Notes:</strong> {{ $app->system_notes }}
      </div>
      @endif

      {{-- FVL Summary (if field verification has been submitted) --}}
      @if($app->fieldVerification)
      @php $fvl = $app->fieldVerification; @endphp
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2 text-white" style="background:#1b5e20">
          <i class="fas fa-clipboard-check me-2"></i>Field Verification Summary
          <span class="badge bg-white text-dark ms-2 small">{{ $fvl->verifier?->name ?? 'FO' }} &mdash; {{ $fvl->visit_start ? \Carbon\Carbon::parse($fvl->visit_start)->format('d M Y, H:i') : '—' }}</span>
        </div>
        <div class="card-body p-0">
          <div class="row g-0">
            {{-- KYC column --}}
            <div class="col-md-4 border-end p-3">
              <div class="small fw-semibold text-muted mb-2 text-uppercase">KYC</div>
              <div class="d-flex flex-column gap-1 small">
                <span class="{{ $fvl->idv ? 'text-success' : 'text-danger' }}"><i class="fas fa-{{ $fvl->idv ? 'check' : 'times' }} me-1"></i>Identity Doc Verified</span>
                <span class="{{ $fvl->pvs ? 'text-success' : 'text-danger' }}"><i class="fas fa-{{ $fvl->pvs ? 'check' : 'times' }} me-1"></i>Physical Visit</span>
                <span class="{{ $fvl->avs ? 'text-success' : 'text-danger' }}"><i class="fas fa-{{ $fvl->avs ? 'check' : 'times' }} me-1"></i>Applicant at Site</span>
                <span class="{{ $fvl->next_of_kin_v ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->next_of_kin_v ? 'check' : 'minus' }} me-1"></i>Next-of-Kin Reachable</span>
                @if($fvl->gps_capture)
                <span class="text-success"><i class="fas fa-map-marker-alt me-1"></i>GPS: {{ $fvl->gps_capture }}</span>
                @endif
              </div>
            </div>
            {{-- Verified Financials column --}}
            <div class="col-md-4 border-end p-3">
              <div class="small fw-semibold text-muted mb-2 text-uppercase">Verified Financials</div>
              <table class="table table-sm table-borderless mb-0 small">
                <tr><td>V. Monthly Sales</td><td class="text-end fw-semibold">UGX {{ number_format($fvl->v_monthly_sales) }}</td></tr>
                <tr><td>V. COGS</td><td class="text-end">UGX {{ number_format($fvl->v_cogs) }}</td></tr>
                <tr><td>V. Op. Expenses</td><td class="text-end">UGX {{ number_format($fvl->v_opex) }}</td></tr>
                <tr><td>V. Household</td><td class="text-end">UGX {{ number_format($fvl->v_household_expenses) }}</td></tr>
                @if($fvl->v_other_income > 0)
                <tr><td>V. Other Income</td><td class="text-end">UGX {{ number_format($fvl->v_other_income) }}</td></tr>
                @endif
                @php
                  $vDisp = ($fvl->v_monthly_sales - $fvl->v_cogs - $fvl->v_opex - $fvl->v_household_expenses + $fvl->v_other_income);
                  $cDisp = $app->daily_sales_claimed - ($app->monthly_cogs_claimed ?? 0) - $app->business_expenses_claimed - $app->household_expenses_claimed + $app->other_income_claimed;
                  $diff  = abs($vDisp - $cDisp);
                  $diffPct = $cDisp > 0 ? round($diff / $cDisp * 100) : 0;
                @endphp
                <tr class="{{ $diffPct > 30 ? 'table-danger' : 'table-light' }}">
                  <td><strong>V. Disposable</strong></td>
                  <td class="text-end fw-bold">UGX {{ number_format($vDisp) }}</td>
                </tr>
                @if($diffPct > 15)
                <tr><td colspan="2" class="text-{{ $diffPct > 30 ? 'danger' : 'warning' }} small">
                  <i class="fas fa-exclamation-triangle me-1"></i>{{ $diffPct }}% variance from declared
                </td></tr>
                @endif
              </table>
            </div>
            {{-- Community & Policy column --}}
            <div class="col-md-4 p-3">
              <div class="small fw-semibold text-muted mb-2 text-uppercase">Community &amp; Policy</div>
              <div class="d-flex flex-column gap-1 small">
                <span class="{{ $fvl->lc1_name_confirmed ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->lc1_name_confirmed ? 'check' : 'minus' }} me-1"></i>LC1 Confirmed</span>
                <span class="{{ $fvl->clan_name_confirmed ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->clan_name_confirmed ? 'check' : 'minus' }} me-1"></i>Clan Confirmed</span>
                <span class="{{ $fvl->ref1_contacted ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->ref1_contacted ? 'check' : 'minus' }} me-1"></i>Ref 1 Contacted</span>
                <span class="{{ $fvl->ref2_contacted ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->ref2_contacted ? 'check' : 'minus' }} me-1"></i>Ref 2 Contacted</span>
                <span class="{{ $fvl->g1_signed ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->g1_signed ? 'check' : 'minus' }} me-1"></i>G1 Consent Signed</span>
                <span class="{{ $fvl->g2_signed ? 'text-success' : 'text-muted' }}"><i class="fas fa-{{ $fvl->g2_signed ? 'check' : 'minus' }} me-1"></i>G2 Consent Signed</span>
              </div>
              @if($fvl->contradiction_count > 0)
              <div class="alert alert-warning mt-2 py-1 small mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>{{ $fvl->contradiction_count }} contradiction(s) noted
              </div>
              @endif
              <div class="mt-2">
                <span class="badge bg-{{ $fvl->field_recommendation === 'proceed' ? 'success' : ($fvl->field_recommendation === 'flag' ? 'warning' : 'danger') }}">
                  FO: {{ strtoupper($fvl->field_recommendation) }}
                </span>
                @if($fvl->physical_visit_confirmed)
                <span class="badge bg-success ms-1">Physical Visit ✓</span>
                @else
                <span class="badge bg-secondary ms-1">Remote Only</span>
                @endif
              </div>
              @if($fvl->officer_notes)
              <div class="text-muted small mt-2 fst-italic">"{{ Str::limit($fvl->officer_notes, 80) }}"</div>
              @endif
            </div>
          </div>
          {{-- FVL Photos --}}
          @php
            $fvlPhotos = [
              'client_home_photo'        => 'Home',
              'client_business_photo'    => 'Business',
              'customer_unposed_photo'   => 'Unposed',
              'officer_selfie_client'    => 'Selfie',
              'live_business_stock_photo'=> 'Stock',
              'coll_1_photo'             => 'Coll. 1',
              'coll_2_photo'             => 'Coll. 2',
            ];
            $hasAnyFvlPhoto = collect($fvlPhotos)->keys()->contains(fn($k) => !empty($fvl->$k));
          @endphp
          @if($hasAnyFvlPhoto)
          <div class="border-top px-3 py-2">
            <div class="small fw-semibold text-muted text-uppercase mb-2">Field Visit Photos</div>
            <div class="d-flex flex-wrap gap-2">
              @foreach($fvlPhotos as $field => $label)
                @if(!empty($fvl->$field))
                  @php $fvlUrl = \App\Services\FileStorageService::getFileUrl($fvl->$field); @endphp
                  <a href="{{ $fvlUrl }}" target="_blank" class="text-decoration-none text-center" style="font-size:.72rem">
                    <img src="{{ $fvlUrl }}" alt="{{ $label }}"
                         style="width:56px; height:56px; object-fit:cover; border-radius:6px; border:1px solid #dee2e6; display:block"
                         onerror="this.outerHTML='<span style=\'width:56px;height:56px;background:#e9ecef;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.2rem\'>📷</span>'">
                    <span class="text-muted d-block mt-1">{{ $label }}</span>
                  </a>
                @endif
              @endforeach
            </div>
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- If already converted --}}
      @if($app->status === 'converted' && $app->loan)
      <div class="alert alert-success mb-3">
        <i class="fas fa-check-circle me-1"></i>
        Converted to loan <code>{{ $app->loan->code }}</code>.
        <a href="{{ route('admin.loans.show', $app->loan_id) }}" class="btn btn-sm btn-success ms-2">View Loan</a>
      </div>
      @endif

      {{-- FO Decision Panel --}}
      @if($app->status === 'pending_fo_verification')
      <div class="card shadow-sm border-0 border-top border-3 border-warning mb-3">
        <div class="card-header bg-white py-2">
          <i class="fas fa-walking me-2 text-warning"></i><strong>Awaiting Field Visit</strong>
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3">
            This application has been submitted by the client and is waiting for a field officer physical visit.
            Complete the <strong>Field Verification Layer (FVL)</strong> form after your site visit to trigger scoring.
          </p>
          <a href="{{ route('admin.client-applications.verify', $app->id) }}" class="btn btn-warning w-100 fw-semibold">
            <i class="fas fa-clipboard-check me-2"></i>Start Field Verification
          </a>
          <hr class="my-2">
          {{-- Allow direct reject without visit if clearly fraudulent --}}
          <form method="POST" action="{{ route('admin.client-applications.reject', $app->id) }}"
                onsubmit="return confirm('Reject without field visit? This cannot be undone.')">
            @csrf
            <label class="form-label small text-muted">Reject without visit (fraud / clear duplicate)</label>
            <textarea name="rejection_reason" class="form-control form-control-sm mb-2" rows="2" required
                      placeholder="e.g. Duplicate application. Client not contactable."></textarea>
            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
              <i class="fas fa-times-circle me-1"></i>Reject Pre-Visit
            </button>
          </form>
        </div>
      </div>
      @endif

      @if($app->status === 'pending_fo_review')
      <div class="card shadow-sm border-0 border-top border-3 border-primary mb-3">
        <div class="card-header bg-white py-2">
          <i class="fas fa-user-check me-2 text-primary"></i><strong>Field Officer Decision</strong>
        </div>
        <div class="card-body">

          {{-- Approve --}}
          <form method="POST" action="{{ route('admin.client-applications.approve', $app->id) }}" class="mb-3"
                onsubmit="return confirm('Approve this application? A Member and Loan record will be created.')">
            @csrf
            @method('POST')
            <label class="form-label small fw-semibold">Approval Notes (optional)</label>
            <textarea name="approval_notes" class="form-control form-control-sm mb-2" rows="2"
                      placeholder="e.g. Business and residence verified; collateral confirmed."></textarea>
            <button type="submit" class="btn btn-success w-100">
              <i class="fas fa-check-circle me-1"></i>
              Approve — Create Member &amp; Loan
            </button>
          </form>

          <hr class="my-2">

          {{-- Reject --}}
          <form method="POST" action="{{ route('admin.client-applications.reject', $app->id) }}"
                onsubmit="return confirm('Reject this application? This cannot be undone.')">
            @csrf
            @method('POST')
            <label class="form-label small fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="rejection_reason" class="form-control form-control-sm mb-2" rows="2" required
                      placeholder="e.g. Business not found at stated location. Collateral value insufficient."></textarea>
            <button type="submit" class="btn btn-outline-danger w-100">
              <i class="fas fa-times-circle me-1"></i>Reject Application
            </button>
          </form>
        </div>
      </div>
      @endif

      {{-- If Rejected --}}
      @if($app->status === 'rejected')
      <div class="card border-danger shadow-sm mb-3">
        <div class="card-header bg-danger text-white py-2">
          <i class="fas fa-ban me-2"></i>Application Rejected
        </div>
        <div class="card-body small">
          <strong>Reason:</strong> {{ $app->rejection_reason }}<br>
          <span class="text-muted">Rejected by: {{ $app->reviewer?->name ?? 'System' }} on {{ $app->reviewed_at?->format('d M Y, h:i A') }}</span>
        </div>
      </div>
      @endif

    </div>{{-- /right column --}}
  </div>{{-- /row --}}
</div>
@endsection
