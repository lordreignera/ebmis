@extends('layouts.admin')

@section('title', 'Field Verification — ' . $app->application_code)

@section('content')
<div class="content-wrapper">
  <div class="page-header">
    <h3 class="page-title">
      <span class="page-title-icon bg-gradient-warning text-white me-2">
        <i class="mdi mdi-clipboard-check-outline"></i>
      </span>
      Field Verification Layer (FVL)
    </h3>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('admin/home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.client-applications.index') }}">Self-Applied</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.client-applications.show', $app->id) }}">{{ $app->application_code }}</a></li>
        <li class="breadcrumb-item active">Field Verification</li>
      </ol>
    </nav>
  </div>

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  {{-- Application Summary Banner --}}
  <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="fas fa-user-circle fa-2x me-3 text-warning"></i>
    <div>
      <strong>{{ $app->full_name }}</strong> &mdash; <code>{{ $app->application_code }}</code><br>
      <span class="small">Loan: UGX {{ number_format($app->requested_amount) }} &bull; {{ $app->tenure_periods }} {{ ucfirst($app->repayment_frequency) }}(s) &bull; Branch: {{ $app->branch?->name ?? '—' }}</span>
    </div>
    <div class="ms-auto text-end small">
      <div class="text-muted">Declared Monthly Sales (DMS)</div>
      <strong class="fs-6">UGX {{ number_format($app->daily_sales_claimed) }}</strong>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.client-applications.verify.submit', $app->id) }}" enctype="multipart/form-data" id="fvlForm">
    @csrf

    <div class="row g-3">
      <div class="col-lg-8">

        {{-- ══ 1. VISIT DETAILS ══════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-primary text-white py-2">
            <i class="fas fa-calendar-check me-2"></i>1. Visit Details
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Visit Start <span class="text-danger">*</span></label>
                <input type="datetime-local" name="visit_start" class="form-control form-control-sm @error('visit_start') is-invalid @enderror"
                       value="{{ old('visit_start') }}" required>
                @error('visit_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Visit End <span class="text-danger">*</span></label>
                <input type="datetime-local" name="visit_end" class="form-control form-control-sm @error('visit_end') is-invalid @enderror"
                       value="{{ old('visit_end') }}" required>
                @error('visit_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">GPS Capture (lat,lng)</label>
                <input type="text" name="gps_capture" class="form-control form-control-sm"
                       value="{{ old('gps_capture') }}" placeholder="0.3476,32.5825" id="gpsField">
                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm mt-1" onclick="captureGPS()">
                  <i class="fas fa-map-marker-alt me-1"></i>Capture GPS
                </button>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Device / IMEI</label>
                <input type="text" name="device_id" class="form-control form-control-sm"
                       value="{{ old('device_id') }}" placeholder="Device identifier">
              </div>
            </div>
          </div>
        </div>

        {{-- ══ 2. KYC VERIFICATION ════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-info text-white py-2">
            <i class="fas fa-id-card me-2"></i>2. KYC Verification
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-3">
                <thead class="table-light">
                  <tr>
                    <th>Check</th>
                    <th>Declared</th>
                    <th class="text-center" style="width:120px">Verified? <span class="text-danger">*</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Identity Document Verified (IDV)</td>
                    <td>NIN: {{ $app->national_id ?? '—' }}</td>
                    <td class="text-center">
                      <select name="idv_status" class="form-select form-select-sm" required>
                        <option value="">— select —</option>
                        <option value="Verified"          {{ old('idv_status') === 'Verified'           ? 'selected' : '' }}>Verified</option>
                        <option value="Partially Verified" {{ old('idv_status') === 'Partially Verified' ? 'selected' : '' }}>Partially Verified</option>
                        <option value="N/A"               {{ old('idv_status') === 'N/A'               ? 'selected' : '' }}>N/A</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td>Physical Visit to Stated Residence (PVS)</td>
                    <td>{{ $app->residence_village }}, {{ $app->residence_district }}</td>
                    <td class="text-center">
                      <select name="pvs_status" class="form-select form-select-sm" required>
                        <option value="">— select —</option>
                        <option value="Verified"          {{ old('pvs_status') === 'Verified'           ? 'selected' : '' }}>Verified</option>
                        <option value="Partially Verified" {{ old('pvs_status') === 'Partially Verified' ? 'selected' : '' }}>Partially Verified</option>
                        <option value="N/A"               {{ old('pvs_status') === 'N/A'               ? 'selected' : '' }}>N/A</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td>Applicant Verified at Site (AVS)</td>
                    <td>{{ $app->full_name }}</td>
                    <td class="text-center">
                      <select name="avs_status" class="form-select form-select-sm" required>
                        <option value="">— select —</option>
                        <option value="Verified"          {{ old('avs_status') === 'Verified'           ? 'selected' : '' }}>Verified</option>
                        <option value="Partially Verified" {{ old('avs_status') === 'Partially Verified' ? 'selected' : '' }}>Partially Verified</option>
                        <option value="N/A"               {{ old('avs_status') === 'N/A'               ? 'selected' : '' }}>N/A</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td>Residence Landmark<br><small class="text-muted">Client declared: {{ $app->landmark_directions ?? '—' }}</small></td>
                    <td></td>
                    <td>
                      <input type="text" name="res_landmark_seen" class="form-control form-control-sm"
                             placeholder="What FO observed at the location"
                             value="{{ old('res_landmark_seen') }}">
                    </td>
                  </tr>
                  <tr>
                    <td>Home Door Colour<br><small class="text-muted">Client declared: {{ $app->home_door_color ?? '—' }}</small></td>
                    <td></td>
                    <td>
                      <input type="text" name="home_door_color_seen" class="form-control form-control-sm"
                             placeholder="Colour FO observed"
                             value="{{ old('home_door_color_seen') }}">
                    </td>
                  </tr>
                  <tr>
                    <td>Next-of-Kin Reachable</td>
                    <td>{{ $app->next_of_kin_name ?? '—' }} / {{ $app->next_of_kin_phone ?? '—' }}</td>
                    <td class="text-center">
                      <select name="next_of_kin_status" class="form-select form-select-sm" required>
                        <option value="">— select —</option>
                        <option value="Verified"          {{ old('next_of_kin_status') === 'Verified'           ? 'selected' : '' }}>Verified</option>
                        <option value="Partially Verified" {{ old('next_of_kin_status') === 'Partially Verified' ? 'selected' : '' }}>Partially Verified</option>
                        <option value="N/A"               {{ old('next_of_kin_status') === 'N/A'               ? 'selected' : '' }}>N/A</option>
                      </select>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Years at Residence (verified)</label>
                <input type="number" name="years_residence_v" class="form-control form-control-sm"
                       value="{{ old('years_residence_v', $app->years_at_residence) }}" min="0" max="99">
              </div>
            </div>
            {{-- Photos --}}
            <div class="row g-3 mt-2">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Client Home Photo</label>
                <input type="file" name="client_home_photo" class="form-control form-control-sm" accept="image/*">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Client Business Photo</label>
                <input type="file" name="client_business_photo" class="form-control form-control-sm" accept="image/*">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Customer Unposed Photo</label>
                <input type="file" name="customer_unposed_photo" class="form-control form-control-sm" accept="image/*">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Officer Selfie with Client</label>
                <input type="file" name="officer_selfie_client" class="form-control form-control-sm" accept="image/*">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Live Business Stock Photo</label>
                <input type="file" name="live_business_stock_photo" class="form-control form-control-sm" accept="image/*">
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">On-Site Security Question</label>
                <input type="text" name="on_site_question" class="form-control form-control-sm"
                       value="{{ old('on_site_question') }}" placeholder="e.g. What colour is your front gate?">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Client's Answer</label>
                <input type="text" name="on_site_answer" class="form-control form-control-sm"
                       value="{{ old('on_site_answer') }}" placeholder="Client's answer">
              </div>
            </div>
          </div>
        </div>

        {{-- ══ 3. BUSINESS & CASH FLOW VERIFICATION ══════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-warning text-dark py-2">
            <i class="fas fa-store me-2"></i>3. Business &amp; Cash Flow Verification
          </div>
          <div class="card-body">
            <div class="alert alert-light border small mb-3">
              All figures below are <strong>monthly</strong>. Compare with client declarations on the right.
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-3">
                <thead class="table-light">
                  <tr>
                    <th>Field (CDL Code)</th>
                    <th class="text-end">Declared</th>
                    <th>FO Verified Figure <span class="text-danger">*</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Monthly Sales <span class="badge bg-secondary small">DMS</span></td>
                    <td class="text-end">UGX {{ number_format($app->daily_sales_claimed) }}</td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">UGX</span>
                        <input type="number" name="v_monthly_sales" class="form-control @error('v_monthly_sales') is-invalid @enderror"
                               value="{{ old('v_monthly_sales') }}" min="0" required placeholder="0">
                        @error('v_monthly_sales')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>Cost of Goods Sold <span class="badge bg-secondary small">DMCOGS</span></td>
                    <td class="text-end">UGX {{ number_format($app->monthly_cogs_claimed ?? 0) }}</td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">UGX</span>
                        <input type="number" name="v_cogs" class="form-control @error('v_cogs') is-invalid @enderror"
                               value="{{ old('v_cogs') }}" min="0" required placeholder="0">
                        @error('v_cogs')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>Operating Expenses <span class="badge bg-secondary small">DMOE</span></td>
                    <td class="text-end">UGX {{ number_format($app->business_expenses_claimed) }}</td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">UGX</span>
                        <input type="number" name="v_opex" class="form-control @error('v_opex') is-invalid @enderror"
                               value="{{ old('v_opex') }}" min="0" required placeholder="0">
                        @error('v_opex')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>Household Expenses <span class="badge bg-secondary small">DMHE</span></td>
                    <td class="text-end">UGX {{ number_format($app->household_expenses_claimed) }}</td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">UGX</span>
                        <input type="number" name="v_household_expenses" class="form-control @error('v_household_expenses') is-invalid @enderror"
                               value="{{ old('v_household_expenses') }}" min="0" required placeholder="0">
                        @error('v_household_expenses')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>Other Income <span class="badge bg-secondary small">DOMI</span></td>
                    <td class="text-end">UGX {{ number_format($app->other_income_claimed) }}</td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">UGX</span>
                        <input type="number" name="v_other_income" class="form-control"
                               value="{{ old('v_other_income', 0) }}" min="0" placeholder="0">
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>Existing Loan Installment <span class="badge bg-secondary small">DMELI</span></td>
                    <td class="text-end">UGX {{ number_format($app->external_installment_per_period ?? 0) }}</td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">UGX</span>
                        <input type="number" name="v_loan_installment" class="form-control"
                               value="{{ old('v_loan_installment', 0) }}" min="0" placeholder="0">
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="row g-3">
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Sales Record Seen?</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="sales_record_seen" value="1" id="srs" {{ old('sales_record_seen') ? 'checked' : '' }}>
                  <label class="form-check-label small" for="srs">Sales book / records sighted</label>
                </div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">MoMo Statement Seen?</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="mobile_money_seen" value="1" id="mms" {{ old('mobile_money_seen') ? 'checked' : '' }}>
                  <label class="form-check-label small" for="mms">Mobile money statements sighted</label>
                </div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Supplier Confirmed?</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="supplier_confirmed" value="1" id="sc" {{ old('supplier_confirmed') ? 'checked' : '' }}>
                  <label class="form-check-label small" for="sc">Declared: {{ $app->top_supplier_name ?? '—' }}</label>
                </div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Confirmed Supplier Name</label>
                <input type="text" name="supplier_confirmed_name" class="form-control form-control-sm"
                       value="{{ old('supplier_confirmed_name', $app->top_supplier_name) }}">
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Business Open During Visit?</label>
                <div class="form-check mt-1">
                  <input class="form-check-input" type="checkbox" name="business_open_v" value="1" id="businessOpenV" {{ old('business_open_v') ? 'checked' : '' }}>
                  <label class="form-check-label small" for="businessOpenV">Business was open &amp; operating</label>
                </div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Business Days Open (verified)</label>
                <input type="number" name="business_open_days_v" class="form-control form-control-sm"
                       value="{{ old('business_open_days_v', $app->business_days_open) }}" min="1" max="7">
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Peak Hours (verified)</label>
                <input type="text" name="peak_hours_v" class="form-control form-control-sm"
                       value="{{ old('peak_hours_v', $app->peak_trading_hours) }}" placeholder="e.g. 5:30pm–8:30pm">
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Avg. Daily Customers (verified)</label>
                <input type="number" name="avg_customers_v" class="form-control form-control-sm"
                       value="{{ old('avg_customers_v', $app->avg_daily_customers) }}" min="0">
              </div>
            </div>
          </div>
        </div>

        {{-- ══ 4. CRB CHECK ═══════════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-dark text-white py-2">
            <i class="fas fa-university me-2"></i>4. CRB / External Debt Check
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-sm-3">
                <label class="form-label small fw-semibold">CRB Defaults</label>
                <input type="number" name="crb_defaults" class="form-control form-control-sm"
                       value="{{ old('crb_defaults', 0) }}" min="0">
              </div>
              <div class="col-sm-3">
                <label class="form-label small fw-semibold">CRB Arrears (UGX)</label>
                <input type="number" name="crb_arrears" class="form-control form-control-sm"
                       value="{{ old('crb_arrears', 0) }}" min="0">
              </div>
              <div class="col-sm-3">
                <label class="form-label small fw-semibold">Active NXT Loans</label>
                <input type="number" name="crb_nxt_count" class="form-control form-control-sm"
                       value="{{ old('crb_nxt_count', $app->external_lenders_count ?? 0) }}" min="0">
              </div>
              <div class="col-sm-3">
                <label class="form-label small fw-semibold">External Installment/Mo</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="crb_ext_inst" class="form-control"
                         value="{{ old('crb_ext_inst', $app->external_installment_per_period ?? 0) }}" min="0">
                </div>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">CRB Skip Flag</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="crb_skip_flag" value="1" id="crbSkip" {{ old('crb_skip_flag') ? 'checked' : '' }}>
                  <label class="form-check-label small" for="crbSkip">Skip CRB (rural — no access)</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- ══ 5. COLLATERAL VERIFICATION ═════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-secondary text-white py-2">
            <i class="fas fa-shield-alt me-2"></i>5. Collateral Verification
          </div>
          <div class="card-body">
            <h6 class="fw-semibold small text-muted mb-2">Collateral 1 — {{ $app->collateral_1_type }} / {{ $app->collateral_1_description }}</h6>
            <div class="row g-3 mb-4">
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Verified Market Value (VMV)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="coll_1_vmv" class="form-control"
                         value="{{ old('coll_1_vmv', $app->collateral_1_client_value) }}" min="0">
                </div>
                <div class="small text-muted mt-1">Client claimed: UGX {{ number_format($app->collateral_1_client_value ?? 0) }}</div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Encumbrances / Liens (UGX)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="coll_1_enc" class="form-control"
                         value="{{ old('coll_1_enc', 0) }}" min="0">
                </div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Collateral 1 Photo</label>
                <input type="file" name="coll_1_photo" class="form-control form-control-sm" accept="image/*">
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  @foreach([
                    ['coll_1_physically_inspected', 'Physically Inspected'],
                    ['coll_1_ownership_accepted',   'Ownership Accepted'],
                    ['coll_1_pledge_signed',        'Pledge Form Signed'],
                    ['coll_1_customary_verified',   'Customary Pledged (CCP Sighted)'],
                  ] as [$field, $label])
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1"
                           id="{{ $field }}" {{ old($field) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="{{ $field }}">{{ $label }}</label>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>

            @if($app->collateral_2_type)
            <hr>
            <h6 class="fw-semibold small text-muted mb-2">Collateral 2 — {{ $app->collateral_2_type }} / {{ $app->collateral_2_description }}</h6>
            <div class="row g-3">
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">VMV</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="coll_2_vmv" class="form-control"
                         value="{{ old('coll_2_vmv', $app->collateral_2_client_value) }}" min="0">
                </div>
                <div class="small text-muted mt-1">Client claimed: UGX {{ number_format($app->collateral_2_client_value ?? 0) }}</div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Encumbrances (UGX)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="coll_2_enc" class="form-control"
                         value="{{ old('coll_2_enc', 0) }}" min="0">
                </div>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Collateral 2 Photo</label>
                <input type="file" name="coll_2_photo" class="form-control form-control-sm" accept="image/*">
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  @foreach([
                    ['coll_2_physically_inspected', 'Physically Inspected'],
                    ['coll_2_ownership_accepted',   'Ownership Accepted'],
                    ['coll_2_pledge_signed',        'Pledge Form Signed'],
                    ['coll_2_customary_verified',   'Customary Pledged (CCP Sighted)'],
                  ] as [$field, $label])
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1"
                           id="{{ $field }}" {{ old($field) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="{{ $field }}">{{ $label }}</label>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>
            @else
            <div class="alert alert-light small border mb-0">No second collateral declared.</div>
            @endif
          </div>
        </div>

        {{-- ══ 6. SOCIAL / COMMUNITY VERIFICATION ════════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header text-white py-2" style="background:#3949ab">
            <i class="fas fa-users me-2"></i>6. Social &amp; Community Verification
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-3">
                <thead class="table-light">
                  <tr><th>Check</th><th>Declared</th><th class="text-center">Confirmed?</th></tr>
                </thead>
                <tbody>
                  <tr>
                    <td>LC1 Name</td>
                    <td>{{ $app->lc1_name ?? '—' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="lc1_name_confirmed" value="1" {{ old('lc1_name_confirmed') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>LC1 Contact</td>
                    <td>{{ $app->lc1_phone ?? '—' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="lc1_contact_confirmed" value="1" {{ old('lc1_contact_confirmed') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>LC1 Letter Sighted</td>
                    <td>—</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="lc1_letter_sighted" value="1" {{ old('lc1_letter_sighted') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>Clan Name Confirmed</td>
                    <td>{{ $app->clan_name ?? '—' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="clan_name_confirmed" value="1" {{ old('clan_name_confirmed') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>Clan Contact Confirmed</td>
                    <td>{{ $app->clan_contact ?? '—' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="clan_contact_confirmed" value="1" {{ old('clan_contact_confirmed') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>Clan Chairperson Letter Sighted</td>
                    <td>{{ $app->clan_letter_available ? 'Client says Yes' : 'Client says No' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="clan_letter_sighted" value="1" {{ old('clan_letter_sighted') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>Reference 1 Contacted</td>
                    <td>{{ $app->reference_name ?? '—' }} / {{ $app->reference_phone ?? '—' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="ref1_contacted" value="1" {{ old('ref1_contacted') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>Reference 2 Contacted</td>
                    <td>{{ $app->reference_2_name ?? '—' }} / {{ $app->reference_2_contact ?? '—' }}</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="ref2_contacted" value="1" {{ old('ref2_contacted') ? 'checked' : '' }}></td>
                  </tr>
                  <tr>
                    <td>Disputes Reported?</td>
                    <td>—</td>
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="disputes_reported" value="1" {{ old('disputes_reported') ? 'checked' : '' }}></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="row g-3">
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Consistent References Count</label>
                <input type="number" name="ref_consistent_count" class="form-control form-control-sm"
                       value="{{ old('ref_consistent_count', 0) }}" min="0" max="5">
              </div>
              <div class="col-sm-8">
                <label class="form-label small fw-semibold">Residence Stability Evidence</label>
                <input type="text" name="residence_stability_evi" class="form-control form-control-sm"
                       value="{{ old('residence_stability_evi') }}" placeholder="e.g. Rent receipts, utility bill, neighbours confirmed">
              </div>
            </div>
          </div>
        </div>

        {{-- ══ 7. GUARANTOR VERIFICATION ══════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-success text-white py-2">
            <i class="fas fa-user-shield me-2"></i>7. Guarantor Verification
          </div>
          <div class="card-body">
            <h6 class="fw-semibold small text-muted mb-2">Guarantor 1 — {{ $app->guarantor_1_name }} / {{ $app->guarantor_1_phone }}</h6>
            <div class="row g-3 mb-4">
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Income Verified (UGX/mo)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="g1_income_verified" class="form-control"
                         value="{{ old('g1_income_verified', $app->guarantor_1_monthly_income) }}" min="0">
                </div>
                <small class="text-muted">Declared: UGX {{ number_format($app->guarantor_1_monthly_income ?? 0) }}</small>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Asset Value Verified (UGX)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="g1_asset_verified" class="form-control"
                         value="{{ old('g1_asset_verified', $app->guarantor_1_pledged_asset_value) }}" min="0">
                </div>
                <small class="text-muted">Declared: UGX {{ number_format($app->guarantor_1_pledged_asset_value ?? 0) }}</small>
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  @foreach([
                    ['g1_contact_verified',       'Contact Verified'],
                    ['g1_relationship_confirmed',  'Relationship Confirmed'],
                    ['g1_willing',                 'Willing to Guarantee'],
                    ['g1_signed',                  'Consent Form Signed'],
                  ] as [$field, $label])
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1"
                           id="{{ $field }}" {{ old($field) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="{{ $field }}">{{ $label }}</label>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>

            @if($app->guarantor_2_name)
            <hr>
            <h6 class="fw-semibold small text-muted mb-2">Guarantor 2 — {{ $app->guarantor_2_name }} / {{ $app->guarantor_2_phone }}</h6>
            <div class="row g-3">
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Income Verified (UGX/mo)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="g2_income_verified" class="form-control"
                         value="{{ old('g2_income_verified', $app->guarantor_2_monthly_income) }}" min="0">
                </div>
                <small class="text-muted">Declared: UGX {{ number_format($app->guarantor_2_monthly_income ?? 0) }}</small>
              </div>
              <div class="col-sm-4">
                <label class="form-label small fw-semibold">Asset Value Verified (UGX)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">UGX</span>
                  <input type="number" name="g2_asset_verified" class="form-control"
                         value="{{ old('g2_asset_verified', $app->guarantor_2_pledged_asset_value) }}" min="0">
                </div>
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  @foreach([
                    ['g2_contact_verified',       'Contact Verified'],
                    ['g2_relationship_confirmed',  'Relationship Confirmed'],
                    ['g2_willing',                 'Willing to Guarantee'],
                    ['g2_signed',                  'Consent Form Signed'],
                  ] as [$field, $label])
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1"
                           id="{{ $field }}" {{ old($field) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="{{ $field }}">{{ $label }}</label>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>
            @else
            <div class="alert alert-light small border mb-0">No second guarantor declared.</div>
            @endif
          </div>
        </div>

        {{-- ══ UPLOADED DOCUMENTS ════════════════════════════════ --}}
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

      </div>{{-- /col-lg-8 --}}

      {{-- ══ RIGHT PANEL: Policy Controls + Submission ══════════ --}}
      <div class="col-lg-4">

        <div class="card shadow-sm border-0 mb-3 sticky-top" style="top:70px">
          <div class="card-header bg-danger text-white py-2">
            <i class="fas fa-traffic-light me-2"></i>8. Policy Controls &amp; Submission
          </div>
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label small fw-semibold">Physical Visit Confirmed? <span class="text-danger">*</span></label>
              <input type="hidden" name="physical_visit_confirmed" value="0">
              <div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="physical_visit_confirmed" value="1" id="pvc1" {{ old('physical_visit_confirmed') == '1' ? 'checked' : '' }} required>
                  <label class="form-check-label small" for="pvc1">Yes — I physically visited</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="physical_visit_confirmed" value="0" id="pvc0" {{ old('physical_visit_confirmed') === '0' ? 'checked' : '' }}>
                  <label class="form-check-label small" for="pvc0">No — Remote only</label>
                </div>
              </div>
              @error('physical_visit_confirmed')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Contradictions Found</label>
              <input type="number" name="contradiction_count" class="form-control form-control-sm"
                     value="{{ old('contradiction_count', 0) }}" min="0" max="20">
              <div class="form-text">Count of declared vs observed contradictions.</div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Time Constraint?</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="time_constraint" value="1" id="tc" {{ old('time_constraint') ? 'checked' : '' }}>
                <label class="form-check-label small" for="tc">Visit was time-constrained</label>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Temporary Trigger?</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="temp_trigger" value="1" id="tt" {{ old('temp_trigger') ? 'checked' : '' }}>
                <label class="form-check-label small" for="tt">Exceptional circumstance flag</label>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Remote Risk Note</label>
              <textarea name="remote_risk_note" class="form-control form-control-sm" rows="2"
                        placeholder="Any remote / unverified risks...">{{ old('remote_risk_note') }}</textarea>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Officer Notes</label>
              <textarea name="officer_notes" class="form-control form-control-sm" rows="3"
                        placeholder="General field observations...">{{ old('officer_notes') }}</textarea>
            </div>

            <hr>

            <div class="mb-4">
              <label class="form-label small fw-semibold">Field Recommendation <span class="text-danger">*</span></label>
              <select name="field_recommendation" class="form-select form-select-sm @error('field_recommendation') is-invalid @enderror" required id="recSelect">
                <option value="">— Choose —</option>
                <option value="proceed" {{ old('field_recommendation') === 'proceed' ? 'selected' : '' }}>✅ Proceed to Scoring</option>
                <option value="flag"    {{ old('field_recommendation') === 'flag'    ? 'selected' : '' }}>⚠️  Flag for Review</option>
                <option value="reject"  {{ old('field_recommendation') === 'reject'  ? 'selected' : '' }}>❌ Reject</option>
              </select>
              @error('field_recommendation')<div class="invalid-feedback">{{ $message }}</div>@enderror

              <div id="rejectNote" class="alert alert-danger mt-2 small py-2 d-none">
                Selecting <strong>Reject</strong> will reject the application immediately without scoring.
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold" id="submitBtn">
              <i class="fas fa-paper-plane me-2"></i>Submit Field Verification
            </button>
            <a href="{{ route('admin.client-applications.show', $app->id) }}" class="btn btn-outline-secondary w-100 mt-2">
              Cancel — Back to Application
            </a>
          </div>
        </div>

      </div>

    </div>{{-- /row --}}
  </form>
</div>

@push('scripts')
<script>
function captureGPS() {
    if (!navigator.geolocation) {
        alert('Geolocation not supported by this browser.');
        return;
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById('gpsField').value =
            pos.coords.latitude.toFixed(6) + ',' + pos.coords.longitude.toFixed(6);
    }, function() {
        alert('Unable to retrieve location. Please enter manually.');
    });
}

// Show reject warning
document.getElementById('recSelect').addEventListener('change', function() {
    const note = document.getElementById('rejectNote');
    const btn  = document.getElementById('submitBtn');
    if (this.value === 'reject') {
        note.classList.remove('d-none');
        btn.classList.replace('btn-primary', 'btn-danger');
        btn.innerHTML = '<i class="fas fa-times-circle me-2"></i>Submit & Reject Application';
    } else {
        note.classList.add('d-none');
        btn.classList.replace('btn-danger', 'btn-primary');
        btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Field Verification';
    }
});

// Fix checkbox boolean: last hidden input wins unless checkbox is checked
// We handle this server-side through explicit (bool) casting

// ── UGX thousands separator for all amount fields ──────────────
(function () {
    var amountFields = [
        'v_monthly_sales','v_cogs','v_opex','v_household_expenses',
        'v_other_income','v_loan_installment',
        'crb_arrears','crb_ext_inst',
        'coll_1_vmv','coll_1_enc','coll_2_vmv','coll_2_enc',
        'g1_income_verified','g1_asset_verified',
        'g2_income_verified','g2_asset_verified'
    ];

    function fmt(val) {
        var n = parseInt(String(val).replace(/[^0-9]/g, ''), 10);
        return isNaN(n) ? '' : n.toLocaleString('en-UG');
    }
    function strip(val) {
        return String(val).replace(/[^0-9]/g, '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        amountFields.forEach(function (name) {
            var orig = document.querySelector('[name="' + name + '"]');
            if (!orig) return;

            // Create a hidden input that carries the raw number on submit
            var hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = name;
            hidden.value = strip(orig.value) || '0';
            orig.parentNode.insertBefore(hidden, orig.nextSibling);

            // Convert original to a text display field
            orig.type = 'text';
            orig.removeAttribute('name');
            orig.removeAttribute('min');
            orig.setAttribute('inputmode', 'numeric');
            orig.value = orig.value !== '' ? fmt(orig.value) : '';

            orig.addEventListener('input', function () {
                var digits = strip(this.value);
                hidden.value = digits || '0';
                var cursor = this.selectionStart;
                var before = this.value.length;
                this.value  = digits ? parseInt(digits, 10).toLocaleString('en-UG') : '';
                var diff    = this.value.length - before;
                try { this.setSelectionRange(cursor + diff, cursor + diff); } catch (e) {}
            });

            orig.addEventListener('focus', function () {
                if (this.value === '0') this.value = '';
            });
            orig.addEventListener('blur', function () {
                if (this.value === '') hidden.value = '0';
            });
        });
    });
}());
</script>
@endpush
@endsection
