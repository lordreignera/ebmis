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
        <div class="text-muted small">System Traffic Light</div>
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
        <div class="text-muted small">Composite Score</div>
        <div class="fs-3 fw-bold mt-1 text-{{ $app->composite_score >= 85 ? 'success' : ($app->composite_score >= 65 ? 'warning' : 'danger') }}">
          {{ $app->composite_score ?? '—' }}<span class="fs-6 text-muted">/100</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="text-muted small">Risk Band</div>
        <div class="fs-5 fw-bold mt-1 text-{{ $app->risk_band === 'Low' ? 'success' : ($app->risk_band === 'Medium' ? 'warning' : 'danger') }}">
          {{ $app->risk_band ?? '—' }}
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

          {{-- Document upload thumbnails --}}
          <div class="mt-3">
            <strong class="small text-muted">Uploaded Documents:</strong>
            <div class="row g-2 mt-1">
              @foreach([
                ['business_profile_photo',   'Profile Photo'],
                ['business_activity_photos', 'Activity Photos'],
                ['inventory_photos',          'Inventory'],
                ['sales_book_photo',          'Sales Book'],
                ['purchases_book_photo',      'Purchases Book'],
                ['expense_records_photo',     'Expense Records'],
                ['mobile_money_statements',   'MoMo Statements'],
              ] as [$field, $label])
              <div class="col-auto">
                @if($app->$field)
                <a href="{{ \App\Services\FileStorageService::getFileUrl($app->$field) }}" target="_blank"
                   class="badge bg-success text-decoration-none">
                  <i class="fas fa-paperclip me-1"></i>{{ $label }}
                </a>
                @else
                <span class="badge bg-light text-muted">{{ $label }}: None</span>
                @endif
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      {{-- Financials --}}
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-success text-white py-2">
          <i class="fas fa-coins me-2"></i>Client Financial Claims
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><th width="55%">Daily Sales Claimed</th><td>UGX {{ number_format($app->daily_sales_claimed) }}</td></tr>
            <tr><th>Business Expenses/Day</th><td>UGX {{ number_format($app->business_expenses_claimed) }}</td></tr>
            <tr><th>Household Expenses/Day</th><td>UGX {{ number_format($app->household_expenses_claimed) }}</td></tr>
            <tr><th>Other Daily Income</th><td>UGX {{ number_format($app->other_income_claimed) }}</td></tr>
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
            <tr><th>Document</th>
              <td>
                @if($app->collateral_1_doc_photo)
                <a href="{{ \App\Services\FileStorageService::getFileUrl($app->collateral_1_doc_photo) }}" target="_blank" class="btn btn-xs btn-outline-success btn-sm py-0">View Doc</a>
                @else No document @endif
              </td>
            </tr>
          </table>

          @if($app->collateral_2_type)
          <hr>
          <strong class="small text-muted">Collateral 2</strong>
          <table class="table table-sm table-borderless mb-0 mt-1">
            <tr><th width="45%">Type</th><td>{{ $app->collateral_2_type }}</td></tr>
            <tr><th>Description</th><td>{{ $app->collateral_2_description }}</td></tr>
            <tr><th>Owner</th><td>{{ $app->collateral_2_owner_name }}</td></tr>
            <tr><th>Document Number</th><td>{{ $app->collateral_2_doc_number }}</td></tr>
            <tr><th>Client Value</th><td>UGX {{ number_format($app->collateral_2_client_value) }}</td></tr>
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
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2" style="background:#1a237e; color:white">
          <i class="fas fa-brain me-2"></i>System Decision-Making Layer
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-borderless mb-0">
            <thead class="table-light"><tr><th>Metric</th><th class="text-end">Value</th><th class="text-center">Rating</th></tr></thead>
            <tbody>
              <tr>
                <td>Evidence Score (ES)</td>
                <td class="text-end">{{ $app->es_score ?? '—' }}/100</td>
                <td class="text-center"><span class="badge bg-{{ ($app->es_score ?? 0) >= 80 ? 'success' : (($app->es_score ?? 0) >= 50 ? 'warning' : 'danger') }}">{{ ($app->es_score ?? 0) >= 80 ? 'Strong' : (($app->es_score ?? 0) >= 50 ? 'Moderate' : 'Weak') }}</span></td>
              </tr>
              <tr>
                <td>Verification Score (VSS)</td>
                <td class="text-end">{{ $app->vss_score ?? '—' }}/100</td>
                <td class="text-center"><span class="badge bg-{{ ($app->vss_score ?? 0) >= 80 ? 'success' : (($app->vss_score ?? 0) >= 50 ? 'warning' : 'danger') }}">{{ ($app->vss_score ?? 0) >= 80 ? 'Strong' : (($app->vss_score ?? 0) >= 50 ? 'Moderate' : 'Weak') }}</span></td>
              </tr>
              <tr class="table-light"><td colspan="3" class="fw-semibold small py-1">Income & Repayment</td></tr>
              <tr>
                <td>Daily Disposable Income</td>
                <td class="text-end">{{ $app->daily_disposable_income ? 'UGX '.number_format($app->daily_disposable_income) : '—' }}</td>
                <td></td>
              </tr>
              <tr>
                <td>Proposed Installment</td>
                <td class="text-end">{{ $app->proposed_installment ? 'UGX '.number_format($app->proposed_installment) : '—' }}</td>
                <td></td>
              </tr>
              <tr>
                <td>Total Debt Service/Period</td>
                <td class="text-end">{{ $app->total_debt_per_period ? 'UGX '.number_format($app->total_debt_per_period) : '—' }}</td>
                <td></td>
              </tr>
              <tr>
                <td><strong>DSCR</strong></td>
                <td class="text-end fw-bold">{{ $app->dscr ?? '—' }}</td>
                <td class="text-center"><span class="badge bg-{{ ($app->dscr ?? 0) >= 2 ? 'success' : (($app->dscr ?? 0) >= 1 ? 'warning' : 'danger') }}">{{ ($app->dscr ?? 0) >= 2 ? 'Strong' : (($app->dscr ?? 0) >= 1 ? 'Pass' : 'Fail') }}</span></td>
              </tr>
              <tr class="table-light"><td colspan="3" class="fw-semibold small py-1">Collateral</td></tr>
              <tr>
                <td>FSV Total</td>
                <td class="text-end">{{ $app->fsv_total ? 'UGX '.number_format($app->fsv_total) : '—' }}</td>
                <td></td>
              </tr>
              <tr>
                <td><strong>Collateral Coverage</strong></td>
                <td class="text-end fw-bold">{{ $app->collateral_coverage ? $app->collateral_coverage.'×' : '—' }}</td>
                <td class="text-center"><span class="badge bg-{{ ($app->collateral_coverage ?? 0) >= 3 ? 'success' : (($app->collateral_coverage ?? 0) >= 2 ? 'warning' : 'danger') }}">{{ ($app->collateral_coverage ?? 0) >= 3 ? 'Meets Min.' : 'Below Min.' }}</span></td>
              </tr>
              <tr>
                <td>Max Approvable Amount</td>
                <td class="text-end">{{ $app->max_approvable_amount ? 'UGX '.number_format($app->max_approvable_amount) : '—' }}</td>
                <td></td>
              </tr>
              <tr>
                <td>Coll. Saleability Score</td>
                <td class="text-end">{{ $app->collateral_saleability_score ?? '—' }}/100</td>
                <td class="text-center"><span class="badge bg-{{ ($app->collateral_saleability_score ?? 0) >= 65 ? 'success' : 'warning' }}">{{ ($app->collateral_saleability_score ?? 0) >= 65 ? 'Strong' : 'Moderate' }}</span></td>
              </tr>
              <tr class="table-light"><td colspan="3" class="fw-semibold small py-1">Guarantors</td></tr>
              <tr>
                <td>Guarantor Security Total</td>
                <td class="text-end">{{ $app->guarantor_security_total ? 'UGX '.number_format($app->guarantor_security_total) : '—' }}</td>
                <td></td>
              </tr>
              <tr>
                <td><strong>Guarantor Strength</strong></td>
                <td class="text-end fw-bold">{{ $app->guarantor_strength_score ?? '—' }}/100</td>
                <td class="text-center"><span class="badge bg-{{ ($app->guarantor_strength_score ?? 0) >= 80 ? 'success' : (($app->guarantor_strength_score ?? 0) >= 50 ? 'warning' : 'secondary') }}">{{ ($app->guarantor_strength_score ?? 0) >= 80 ? 'Strong' : (($app->guarantor_strength_score ?? 0) >= 50 ? 'Moderate' : 'Weak') }}</span></td>
              </tr>
              <tr class="table-light"><td colspan="3" class="fw-semibold small py-1">Final Decision</td></tr>
              <tr>
                <td><strong>Composite Score</strong></td>
                <td class="text-end fw-bold fs-5">{{ $app->composite_score ?? '—' }}/100</td>
                <td class="text-center"><span class="badge bg-{{ $app->composite_score >= 85 ? 'success' : ($app->composite_score >= 65 ? 'warning' : 'danger') }}">{{ $app->risk_band ?? '—' }} Risk</span></td>
              </tr>
              <tr>
                <td><strong>System Recommendation</strong></td>
                <td class="text-end" colspan="2">
                  <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $app->traffic_light === 'GREEN' ? '#198754' : ($app->traffic_light === 'YELLOW' ? '#ffc107' : '#dc3545') }};vertical-align:middle;margin-right:4px;"></span>
                  <strong class="text-{{ $app->trafficLightClass() }}">{{ $app->traffic_light ?? 'N/A' }}</strong>
                </td>
              </tr>
            </tbody>
          </table>
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

      {{-- If already converted --}}
      @if($app->status === 'converted' && $app->loan)
      <div class="alert alert-success mb-3">
        <i class="fas fa-check-circle me-1"></i>
        Converted to loan <code>{{ $app->loan->code }}</code>.
        <a href="{{ route('admin.loans.show', $app->loan_id) }}" class="btn btn-sm btn-success ms-2">View Loan</a>
      </div>
      @endif

      {{-- FO Decision Panel --}}
      @if(in_array($app->status, ['pending_fo_review', 'pending_fo_verification']))
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
