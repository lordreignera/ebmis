<style>
  /* Dashboard Cards Alignment and Styling */
  .card-bordered {
    border: 1px solid #e5e9f2 !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    transition: all 0.3s ease !important;
  }
  
  .card-bordered:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    transform: translateY(-2px);
  }
  
  .card-inner {
    padding: 1.5rem !important;
  }
  
  /* Horizontal dividers */
  .card-inner hr,
  hr {
    border: none;
    border-top: 1px solid #e5e9f2;
    margin: 0.5rem 0 1rem 0;
  }
  
  /* Ensure all cards in a row have equal height */
  .stretch-card {
    display: flex;
    flex-direction: column;
  }
  
  .stretch-card > .card {
    flex: 1;
  }
  
  /* Enhanced KPI Card Styling */
  .audit-card {
    border: 1px solid #e5e9f2 !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    transition: all 0.3s ease !important;
    min-height: 140px !important;
  }
  
  .audit-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
    transform: translateY(-3px) !important;
  }
  
  .audit-card .card-body {
    padding: 1.5rem !important;
    height: 100% !important;
  }
  
  .audit-card h3 {
    font-size: 2.2rem !important;
    font-weight: 700 !important;
    line-height: 1.1 !important;
  }
  
  .audit-card h6 {
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
  }
  
  .audit-card .icon {
    width: 50px !important;
    height: 50px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
  }
  
  .audit-card .icon-item {
    font-size: 24px !important;
  }
  
  /* Card titles */
  .card-title-sm h6.title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #364a63;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .card-title-sm p {
    font-size: 0.85rem;
    color: #8094ae;
    margin-bottom: 0.5rem;
  }
  
  /* Title group spacing */
  .card-title-group {
    margin-bottom: 1rem;
  }
  
  .pb-3 {
    padding-bottom: 1rem !important;
  }
  
  /* Analytics overview styling */
  .analytic-ov-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .analytic-au-data {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f5f6fa;
  }
  
  .analytic-au-data:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
  
  .analytic-au-data .title {
    font-size: 0.875rem;
    color: #8094ae;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }
  
  .analytic-au-data .amount {
    font-size: 1.25rem !important;
    font-weight: 600;
    color: #364a63;
  }
  
  /* Clickable pending actions hover effect */
  a .analytic-au-data:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding-left: 0.5rem;
    margin-left: -0.5rem;
    margin-right: -0.5rem;
    padding-right: 0.5rem;
  }
  
  /* Table styling for Cash Securities and Loans cards */
  .nk-tb-list {
    width: 100%;
  }
  
  .nk-tb-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1.5rem;
    border-bottom: 1px solid #f5f6fa;
  }
  
  .nk-tb-item:last-child {
    border-bottom: none;
  }
  
  .nk-tb-head {
    background-color: #f5f6fa;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #526484;
    padding: 0.75rem 1.5rem;
  }
  
  .nk-tb-col {
    flex: 1;
  }
  
  .nk-tb-channel {
    flex: 1.5;
  }
  
  .nk-tb-prev-sessions {
    flex: 1;
    text-align: right;
  }
  
  .tb-lead {
    font-size: 0.875rem;
    color: #364a63;
    font-weight: 500;
  }
  
  .tb-amount {
    font-size: 0.95rem !important;
    font-weight: 600;
    color: #364a63;
  }
  
  /* Grid margins */
  .grid-margin {
    margin-bottom: 1.5rem;
  }
  
  /* Responsive adjustments */
  @media (max-width: 1199px) {
    .col-xxl-3 {
      margin-bottom: 1.5rem;
    }
  }
  
  @media (max-width: 991px) {
    .col-lg-6 {
      margin-bottom: 1.5rem;
    }
  }
  
  @media (max-width: 767px) {
    .col-md-6 {
      margin-bottom: 1.5rem;
    }
  }
  
  /* Ensure consistent card heights */
  .h-100 {
    height: 100% !important;
  }
  
  /* Force equal height for all KPI cards */
  .row .stretch-card {
    display: flex !important;
  }
  
  .row .stretch-card .card {
    width: 100% !important;
    min-height: 160px !important;
  }
  
  .d-flex.flex-column {
    height: 100% !important;
  }
  
  .flex-grow-1 {
    flex-grow: 1 !important;
  }
  
  /* Remove bottom margin from last row */
  .row:last-of-type .grid-margin:last-child {
    margin-bottom: 0 !important;
  }

  /* Dashboard operational color palette */
  .dashboard-page {
    --ebims-ink: #172033;
    --ebims-muted: #64748b;
    --ebims-border: #dfe7ef;
    --ebims-panel: #ffffff;
    --ebims-green: #16804f;
    --ebims-green-soft: #e8f7ef;
    --ebims-blue: #2563eb;
    --ebims-blue-soft: #eaf1ff;
    --ebims-red: #dc2626;
    --ebims-red-soft: #fff1f2;
    --ebims-amber: #b7791f;
    --ebims-amber-soft: #fff7e6;
    --ebims-teal: #0f766e;
    --ebims-teal-soft: #e6fffb;
    --ebims-violet: #7c3aed;
    --ebims-violet-soft: #f3edff;
  }

  .dashboard-page .page-header {
    background: #ffffff;
    border: 1px solid var(--ebims-border);
    border-left: 5px solid var(--ebims-blue);
    border-radius: 8px;
    padding: 1rem 1.25rem;
    box-shadow: 0 8px 22px rgba(23, 32, 51, 0.05);
  }

  .dashboard-page .page-title {
    color: var(--ebims-ink);
    margin-bottom: 0.35rem;
  }

  .dashboard-page .page-title-icon {
    background: var(--ebims-blue) !important;
    border-radius: 8px !important;
  }

  .dashboard-page .breadcrumb {
    margin-bottom: 0;
  }

  .dashboard-page .breadcrumb-item {
    color: var(--ebims-muted);
  }

  .dashboard-page .card-bordered,
  .dashboard-page .audit-card {
    background: var(--ebims-panel) !important;
    border: 1px solid var(--ebims-border) !important;
    border-radius: 8px !important;
    box-shadow: 0 8px 24px rgba(23, 32, 51, 0.06) !important;
  }

  .dashboard-page .card-bordered:hover,
  .dashboard-page .audit-card:hover {
    box-shadow: 0 12px 30px rgba(23, 32, 51, 0.09) !important;
    transform: translateY(-1px) !important;
  }

  .dashboard-page .audit-card {
    border-top-width: 4px !important;
    overflow: hidden;
  }

  .dashboard-page .audit-card h3 {
    color: var(--card-accent, var(--ebims-blue)) !important;
  }

  .dashboard-page .audit-card h6,
  .dashboard-page .card-title-sm h6.title,
  .dashboard-page .card-title h6.title {
    color: var(--ebims-ink) !important;
  }

  .dashboard-page .audit-card p,
  .dashboard-page .analytic-au-data .title,
  .dashboard-page .card-title-sm p {
    color: var(--ebims-muted) !important;
  }

  .dashboard-page .audit-card .icon {
    background: var(--card-soft, var(--ebims-blue-soft)) !important;
    border: 1px solid rgba(23, 32, 51, 0.06) !important;
    color: var(--card-accent, var(--ebims-blue)) !important;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.8) !important;
  }

  .dashboard-page .audit-card .icon-item,
  .dashboard-page .analytic-au-data i {
    color: var(--card-accent, var(--panel-accent, var(--ebims-blue))) !important;
  }

  .dashboard-page .kpi-members {
    --card-accent: var(--ebims-green);
    --card-soft: var(--ebims-green-soft);
    border-top-color: var(--ebims-green) !important;
  }

  .dashboard-page .kpi-active-loans {
    --card-accent: var(--ebims-blue);
    --card-soft: var(--ebims-blue-soft);
    border-top-color: var(--ebims-blue) !important;
  }

  .dashboard-page .kpi-overdue {
    --card-accent: var(--ebims-red);
    --card-soft: var(--ebims-red-soft);
    border-top-color: var(--ebims-red) !important;
  }

  .dashboard-page .kpi-due-today {
    --card-accent: var(--ebims-amber);
    --card-soft: var(--ebims-amber-soft);
    border-top-color: var(--ebims-amber) !important;
  }

  .dashboard-page .panel-members {
    --panel-accent: var(--ebims-green);
    --panel-soft: var(--ebims-green-soft);
  }

  .dashboard-page .panel-investments {
    --panel-accent: var(--ebims-violet);
    --panel-soft: var(--ebims-violet-soft);
  }

  .dashboard-page .panel-securities {
    --panel-accent: var(--ebims-teal);
    --panel-soft: var(--ebims-teal-soft);
  }

  .dashboard-page .panel-loans {
    --panel-accent: var(--ebims-blue);
    --panel-soft: var(--ebims-blue-soft);
  }

  .dashboard-page .panel-actions {
    --panel-accent: var(--ebims-amber);
    --panel-soft: var(--ebims-amber-soft);
  }

  .dashboard-page .panel-chart {
    --panel-accent: var(--ebims-blue);
    --panel-soft: var(--ebims-blue-soft);
  }

  .dashboard-page .panel-calendar {
    --panel-accent: var(--ebims-teal);
    --panel-soft: var(--ebims-teal-soft);
  }

  .dashboard-page .panel-events {
    --panel-accent: var(--ebims-violet);
    --panel-soft: var(--ebims-violet-soft);
  }

  .dashboard-page .dashboard-help-card {
    --panel-accent: var(--ebims-blue);
    --panel-soft: var(--ebims-blue-soft);
  }

  .dashboard-page .dashboard-help-card .card-inner {
    padding: 1.2rem 1.4rem !important;
  }

  .dashboard-page .dashboard-help-icon {
    align-items: center;
    background: var(--ebims-blue-soft);
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    color: var(--ebims-blue);
    display: inline-flex;
    flex: 0 0 46px;
    font-size: 26px;
    height: 46px;
    justify-content: center;
    width: 46px;
  }

  .dashboard-page .card-bordered {
    border-left: 4px solid var(--panel-accent, var(--ebims-blue)) !important;
  }

  .dashboard-page .card-bordered .card-title-group {
    background: var(--panel-soft, var(--ebims-blue-soft));
    margin: -1.5rem -1.5rem 1rem;
    padding: 1rem 1.5rem !important;
    border-bottom: 1px solid var(--ebims-border);
  }

  .dashboard-page .analytic-au-data {
    border-bottom-color: #edf2f7;
  }

  .dashboard-page .analytic-au-data .amount,
  .dashboard-page .tb-amount {
    color: var(--ebims-ink) !important;
  }

  .dashboard-page .analytic-au-data .amount.text-primary,
  .dashboard-page .analytic-au-data .amount.text-success,
  .dashboard-page .analytic-au-data .amount.text-info,
  .dashboard-page .analytic-au-data .amount.text-warning,
  .dashboard-page .analytic-au-data .amount.text-danger {
    color: var(--panel-accent, var(--ebims-blue)) !important;
  }

  .dashboard-page a .analytic-au-data:hover {
    background-color: var(--panel-soft, #f8fafc) !important;
  }

  .dashboard-page .nk-ck {
    min-height: 320px;
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.88), #ffffff);
    border-top: 1px solid var(--ebims-border);
  }

  .dashboard-page #loansVsSavingsChart {
    max-height: 320px;
  }

  .dashboard-page .nk-tb-head {
    background: #eef4f8 !important;
    color: var(--ebims-ink) !important;
  }

  .dashboard-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.5rem;
  }

  .dashboard-calendar-weekday {
    color: var(--ebims-muted);
    font-size: 0.76rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
  }

  .dashboard-calendar-day {
    align-items: flex-start;
    aspect-ratio: 1 / 0.74;
    background: #f8fafc;
    border: 1px solid #e8eef5;
    border-radius: 8px;
    color: var(--ebims-ink);
    display: flex;
    flex-direction: column;
    font-weight: 700;
    justify-content: space-between;
    min-height: 58px;
    padding: 0.55rem;
  }

  .dashboard-calendar-day.is-muted {
    background: #ffffff;
    color: #b3bfcc;
  }

  .dashboard-calendar-day.is-today {
    border-color: var(--ebims-blue);
    box-shadow: inset 0 0 0 1px var(--ebims-blue);
  }

  .dashboard-calendar-day.has-events {
    background: #ffffff;
  }

  .dashboard-event-dots {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.2rem;
    min-height: 0.65rem;
  }

  .dashboard-event-dot {
    border-radius: 999px;
    display: inline-block;
    height: 0.48rem;
    width: 0.48rem;
  }

  .dashboard-event-dot.collection {
    background: var(--ebims-teal);
  }

  .dashboard-event-dot.manual {
    background: var(--ebims-violet);
  }

  .dashboard-event-count {
    color: var(--ebims-muted);
    font-size: 0.68rem;
    font-weight: 700;
  }

  .dashboard-event-list {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
  }

  .dashboard-event-item {
    align-items: flex-start;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    gap: 0.75rem;
    padding-bottom: 0.8rem;
  }

  .dashboard-event-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  .dashboard-event-date {
    align-items: center;
    background: var(--panel-soft, #f8fafc);
    border: 1px solid var(--ebims-border);
    border-radius: 8px;
    color: var(--panel-accent, var(--ebims-blue));
    display: flex;
    flex: 0 0 48px;
    flex-direction: column;
    font-weight: 800;
    line-height: 1;
    padding: 0.45rem 0.25rem;
    text-align: center;
  }

  .dashboard-event-date .month {
    font-size: 0.68rem;
    margin-top: 0.2rem;
    text-transform: uppercase;
  }

  .dashboard-event-title {
    color: var(--ebims-ink);
    font-weight: 700;
    line-height: 1.25;
    text-decoration: none;
  }

  .dashboard-event-title:hover {
    color: var(--panel-accent, var(--ebims-blue));
    text-decoration: none;
  }

  .dashboard-event-meta {
    color: var(--ebims-muted);
    font-size: 0.78rem;
    margin-top: 0.2rem;
  }

  .dashboard-event-badge {
    background: var(--panel-soft, #f8fafc);
    border-radius: 999px;
    color: var(--panel-accent, var(--ebims-blue));
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 700;
    margin-top: 0.35rem;
    padding: 0.18rem 0.55rem;
    text-transform: uppercase;
  }

  .dashboard-add-event-btn {
    align-items: center;
    display: inline-flex;
    gap: 0.35rem;
    white-space: nowrap;
  }

  @media (max-width: 575px) {
    .dashboard-calendar-grid {
      gap: 0.3rem;
    }

    .dashboard-calendar-day {
      aspect-ratio: 1 / 0.9;
      font-size: 0.78rem;
      min-height: 44px;
      padding: 0.35rem;
    }

    .dashboard-calendar-weekday {
      font-size: 0.68rem;
    }
  }
</style>

<div class="dashboard-page">
    @php
      $canManageDashboardEvents = auth()->user()?->isSuperAdmin()
        || auth()->user()?->isAdministrator()
        || auth()->user()?->can('manage-dashboard-events');
    @endphp

    <!-- Welcome Message -->
    <div class="page-header mb-4">
      <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
          <i class="mdi mdi-home"></i>
        </span> Dashboard - Emuria Micro Finance
      </h3>
      <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
          <li class="breadcrumb-item active" aria-current="page">
            <span></span>Welcome back, {{ Auth::user()->name }}.
          </li>
        </ul>
      </nav>
    </div>

    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card card-bordered dashboard-help-card">
          <div class="card-inner d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="dashboard-help-icon">
                <i class="mdi mdi-help-circle-outline"></i>
              </div>
              <div>
                <h5 class="mb-1">Need help using EBIMS?</h5>
                <p class="mb-0 text-muted">Open the user guide for menu navigation, daily workflows, and screenshot-based steps.</p>
              </div>
            </div>
            <a href="{{ route('admin.help.guide') }}" class="btn btn-primary">
              <i class="mdi mdi-book-open-page-variant"></i> Open User Guide
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Statistics Cards Row 1 - Main Metrics -->
    <div class="row">
      
      <!-- Total Members Card -->
      <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card audit-card kpi-members h-100">
          <div class="card-body d-flex flex-column">
            <div class="row flex-grow-1">
              <div class="col-9">
                <div class="d-flex align-items-center align-self-start mb-2">
                  <h3 class="mb-0 text-primary fw-bold">{{ number_format($stats['total_members'] ?? 0) }}</h3>
                </div>
                <h6 class="text-dark font-weight-bold mb-2">Total Registered Members</h6>
                <p class="text-success mb-0">
                  <i class="mdi mdi-check-circle"></i> {{ number_format($stats['activated_members'] ?? 0) }} Activated
                  @if($stats['pending_members'] > 0)
                    <br><span class="text-warning"><i class="mdi mdi-clock"></i> {{ $stats['pending_members'] }} Pending</span>
                  @endif
                </p>
              </div>
              <div class="col-3 d-flex align-items-center justify-content-center">
                <div class="icon icon-box-success">
                  <span class="mdi mdi-account-multiple icon-item"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Loans Card -->
      <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card audit-card kpi-active-loans h-100">
          <div class="card-body d-flex flex-column">
            <div class="row flex-grow-1">
              <div class="col-9">
                <div class="d-flex align-items-center align-self-start mb-2">
                  <h3 class="mb-0 text-primary fw-bold">{{ number_format($stats['total_active_loans'] ?? 0) }}</h3>
                </div>
                <h6 class="text-dark font-weight-bold mb-2">Total Active Loans</h6>
                <p class="text-primary mb-0">
                  <i class="mdi mdi-currency-usd"></i> <strong>{{ number_format($stats['total_active_loans_value'] ?? 0) }} UGX</strong>
                  <br><small class="text-muted">Portfolio Value</small>
                </p>
              </div>
              <div class="col-3 d-flex align-items-center justify-content-center">
                <div class="icon icon-box-primary">
                  <span class="mdi mdi-cash-multiple icon-item"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Repayments Due (Overdue) Card -->
      <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card audit-card kpi-overdue h-100">
          <div class="card-body d-flex flex-column">
            <div class="row flex-grow-1">
              <div class="col-9">
                <div class="d-flex align-items-center align-self-start mb-2">
                  <h3 class="mb-0 text-danger fw-bold">{{ number_format($stats['repayments_due_count'] ?? 0) }}</h3>
                </div>
                <h6 class="text-dark font-weight-bold mb-2">Total Overdue Loans</h6>
                <p class="text-danger mb-0">
                  <i class="mdi mdi-alert-circle"></i> <strong>{{ number_format($stats['repayments_due'] ?? 0) }} UGX</strong>
                  <br><small class="text-muted">Amount Overdue</small>
                </p>
              </div>
              <div class="col-3 d-flex align-items-center justify-content-center">
                <div class="icon icon-box-danger">
                  <span class="mdi mdi-alert icon-item"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Repayments Due Today Card -->
      <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card audit-card kpi-due-today h-100">
          <div class="card-body d-flex flex-column">
            <div class="row flex-grow-1">
              <div class="col-9">
                <div class="d-flex align-items-center align-self-start mb-2">
                  <h3 class="mb-0 text-warning fw-bold">{{ number_format($stats['repayments_due_today_count'] ?? 0) }}</h3>
                </div>
                <h6 class="text-dark font-weight-bold mb-2">Payments Due Today</h6>
                <p class="text-warning mb-0">
                  <i class="mdi mdi-calendar-check"></i> <strong>{{ number_format($stats['repayments_due_today'] ?? 0) }} UGX</strong>
                  <br><small class="text-muted">Expected Today</small>
                </p>
              </div>
              <div class="col-3 d-flex align-items-center justify-content-center">
                <div class="icon icon-box-warning">
                  <span class="mdi mdi-calendar-today icon-item"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards Row 2 - Detailed Metrics -->
    <div class="row">
      
      <!-- Members Overview Card -->
      <div class="col-lg-6 col-xxl-3 col-md-6 grid-margin stretch-card">
        <div class="card card-bordered panel-members h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3 g-2">
              <div class="card-title card-title-sm">
                <h6 class="title">Members Overview</h6>
              </div>
            </div>
            <hr>
            <div class="analytic-ov">
              <div class="analytic-data-group analytic-ov-group g-3">
                <div class="analytic-data analytic-au-data">
                  <div class="title">Individual Clients</div>
                  <div class="amount" style="font-size: 1.1rem">{{ number_format($stats['total_members'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">Groups</div>
                  <div class="amount" style="font-size: 1.1rem">{{ number_format($stats['total_groups'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">Activated</div>
                  <div class="amount text-success" style="font-size: 1.1rem">{{ number_format($stats['activated_members'] ?? 0) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Investments Overview Card -->
      <div class="col-lg-6 col-xxl-3 col-md-6 grid-margin stretch-card">
        <div class="card card-bordered panel-investments h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3 g-2">
              <div class="card-title card-title-sm">
                <h6 class="title">Investments Overview</h6>
              </div>
            </div>
            <hr>
            <div class="analytic-ov">
              <div class="analytic-data-group analytic-ov-group g-3">
                <div class="analytic-data analytic-au-data">
                  <div class="title">Number of Investors</div>
                  <div class="amount" style="font-size: 1.1rem">{{ number_format($stats['investors_count'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">Total Investment</div>
                  <div class="amount" style="font-size: 1.1rem">UGX {{ number_format($stats['investment_value'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">Investment this Month</div>
                  <div class="amount" style="font-size: 1.1rem">UGX {{ number_format($stats['investment_month'] ?? 0) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cash Securities Card -->
      <div class="col-lg-6 col-xxl-3 col-md-6 grid-margin stretch-card">
        <div class="card card-bordered panel-securities h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3 g-2">
              <div class="card-title card-title-sm">
                <h6 class="title">Cash Securities</h6>
              </div>
            </div>
            <hr>
            <div class="analytic-ov">
              <div class="analytic-data-group analytic-ov-group g-3">
                <div class="analytic-data analytic-au-data">
                  <div class="title">Total Received</div>
                  <div class="amount text-success" style="font-size: 1.1rem">UGX {{ number_format($stats['savings_value'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">This Month</div>
                  <div class="amount" style="font-size: 1.1rem">UGX {{ number_format($stats['savings_month'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">Number of Securities</div>
                  <div class="amount" style="font-size: 1.1rem">{{ number_format($stats['savings_count'] ?? 0) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loans Card -->
      <div class="col-lg-6 col-xxl-3 col-md-6 grid-margin stretch-card">
        <div class="card card-bordered panel-loans h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3 g-2">
              <div class="card-title card-title-sm">
                <h6 class="title">Loans Overview</h6>
              </div>
            </div>
            <hr>
            <div class="analytic-ov">
              <div class="analytic-data-group analytic-ov-group g-3">
                <div class="analytic-data analytic-au-data">
                  <div class="title">All Loans Disbursed</div>
                  <div class="amount text-primary" style="font-size: 1.1rem">UGX {{ number_format($stats['total_loans_value'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">This Month</div>
                  <div class="amount" style="font-size: 1.1rem">UGX {{ number_format($stats['total_loans_month'] ?? 0) }}</div>
                </div>
                <div class="analytic-data analytic-au-data">
                  <div class="title">Total Number of Loans</div>
                  <div class="amount" style="font-size: 1.1rem">{{ number_format($stats['total_loans_count'] ?? 0) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Charts and Activity Row -->
    <div class="row">
      
      <!-- Loans vs Savings Chart -->
      <div class="col-lg-8 col-xxl-8">
        <div class="card card-bordered panel-chart h-100">
          <div class="card-inner mb-n2">
            <div class="card-title-group">
              <div class="card-title card-title-sm">
                <h6 class="title">Loans vs Cash Securities (Last 6 Months)</h6>
              </div>
            </div>
          </div>
          <div class="nk-ck p-4">
            <canvas id="loansVsSavingsChart" height="100"></canvas>
          </div>
        </div>
      </div>

      <!-- Pending Actions Card -->
      <div class="col-lg-4 col-xxl-4">
        <div class="card card-bordered panel-actions h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3">
              <div class="card-title">
                <h6 class="title">Pending Actions</h6>
              </div>
            </div>
            <hr>
            <div class="analytic-ov">
              <div class="analytic-data-group analytic-ov-group g-3">
                <div class="analytic-data analytic-au-data" style="opacity: 0.5;">
                  <div class="title">
                    <i class="mdi mdi-pen text-muted"></i> Pending Signature
                  </div>
                  <div class="amount text-muted" style="font-size: 1.2rem">{{ number_format($stats['pending_signature'] ?? 0) }}</div>
                  <small class="text-muted d-block" style="font-size: 0.75rem;">Not tracked</small>
                </div>
                <a href="{{ route('admin.loans.index', ['status' => '0', 'verified' => '0']) }}" class="text-decoration-none">
                  <div class="analytic-data analytic-au-data" style="cursor: pointer; transition: background 0.2s;">
                    <div class="title">
                      <i class="mdi mdi-check-circle text-info"></i> Pending Approval
                    </div>
                    <div class="amount text-info" style="font-size: 1.2rem">{{ number_format($stats['pending_approval'] ?? 0) }}</div>
                  </div>
                </a>
                <a href="{{ route('admin.loans.disbursements.pending') }}" class="text-decoration-none">
                  <div class="analytic-data analytic-au-data" style="cursor: pointer; transition: background 0.2s;">
                    <div class="title">
                      <i class="mdi mdi-bank-transfer text-primary"></i> Pending Disbursement
                    </div>
                    <div class="amount text-primary" style="font-size: 1.2rem">{{ number_format($stats['pending_disbursement'] ?? 0) }}</div>
                  </div>
                </a>
                <a href="{{ route('admin.members.pending') }}" class="text-decoration-none">
                  <div class="analytic-data analytic-au-data" style="cursor: pointer; transition: background 0.2s;">
                    <div class="title">
                      <i class="mdi mdi-account-clock text-secondary"></i> Pending Members
                    </div>
                    <div class="amount text-secondary" style="font-size: 1.2rem">{{ number_format($stats['pending_members'] ?? 0) }}</div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Calendar Preview Row -->
    <div class="row mt-4">
      <div class="col-lg-8 grid-margin stretch-card">
        <div class="card card-bordered panel-calendar h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3 g-2 align-items-center">
              <div class="card-title card-title-sm">
                <h6 class="title">Calendar Preview</h6>
                <p>{{ $calendarPreview['month_label'] ?? now()->format('F Y') }}</p>
              </div>
              @if($canManageDashboardEvents && ($calendarPreview['events_enabled'] ?? false))
                <button type="button" class="btn btn-sm btn-primary dashboard-add-event-btn" data-bs-toggle="modal" data-bs-target="#addDashboardEventModal">
                  <i class="mdi mdi-plus"></i> Add Event
                </button>
              @endif
            </div>
            <div class="dashboard-calendar-grid">
              @foreach(($calendarPreview['weekdays'] ?? []) as $weekday)
                <div class="dashboard-calendar-weekday">{{ $weekday }}</div>
              @endforeach
              @foreach(($calendarPreview['days'] ?? []) as $day)
                <div class="dashboard-calendar-day {{ empty($day['in_month']) ? 'is-muted' : '' }} {{ !empty($day['is_today']) ? 'is-today' : '' }} {{ ($day['events_count'] ?? 0) > 0 ? 'has-events' : '' }}">
                  <span>{{ $day['day'] }}</span>
                  @if(($day['events_count'] ?? 0) > 0)
                    <div class="dashboard-event-dots">
                      @if(!empty($day['has_collection']))
                        <span class="dashboard-event-dot collection"></span>
                      @endif
                      @if(!empty($day['has_manual']))
                        <span class="dashboard-event-dot manual"></span>
                      @endif
                      @if(($day['events_count'] ?? 0) > 1)
                        <span class="dashboard-event-count">{{ $day['events_count'] }}</span>
                      @endif
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 grid-margin stretch-card">
        <div class="card card-bordered panel-events h-100">
          <div class="card-inner">
            <div class="card-title-group pb-3">
              <div class="card-title card-title-sm">
                <h6 class="title">Upcoming Events</h6>
              </div>
            </div>
            <hr>
            <div class="dashboard-event-list">
              @forelse(($calendarPreview['upcoming'] ?? []) as $event)
                @php($eventDate = \Carbon\Carbon::parse($event['date']))
                <div class="dashboard-event-item">
                  <div class="dashboard-event-date">
                    <span>{{ $eventDate->format('d') }}</span>
                    <span class="month">{{ $eventDate->format('M') }}</span>
                  </div>
                  <div class="flex-grow-1">
                    @if(!empty($event['url']))
                      <a href="{{ $event['url'] }}" class="dashboard-event-title">{{ $event['title'] }}</a>
                    @else
                      <div class="dashboard-event-title">{{ $event['title'] }}</div>
                    @endif
                    <div class="dashboard-event-meta">{{ $event['time_label'] }} @if(!empty($event['subtitle'])) - {{ $event['subtitle'] }} @endif</div>
                    <span class="dashboard-event-badge">{{ $event['badge'] }}</span>
                  </div>
                </div>
              @empty
                <div class="text-muted py-4 text-center">No upcoming events</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="row mt-4">
      <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title">
              <i class="mdi mdi-history"></i> Recent Activity
            </h4>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th style="width: 15%;">Time</th>
                    <th style="width: 70%;">Activity</th>
                    <th style="width: 15%;" class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($recentActivity ?? [] as $activity)
                  <tr>
                    <td>{{ $activity->created_at->diffForHumans() }}</td>
                    <td>{{ $activity->description }}</td>
                    <td class="text-center">
                      <?php
                        $viewUrl = null;

                        if (isset($activity->loan_id) && $activity->loan_id) {
                          $loan = \App\Models\PersonalLoan::find($activity->loan_id) ?: \App\Models\GroupLoan::find($activity->loan_id);
                          $hasDisbursement = $loan ? $loan->disbursements()->where('status', 2)->exists() : false;
                          $status = $activity->status ?? '0';

                          if ($hasDisbursement || $status == '2' || $status == '3') {
                            $viewUrl = route('admin.loans.repayments.schedules', $activity->loan_id);
                          } elseif ($status == '1') {
                            $viewUrl = route('admin.loans.disbursements.approve.show', $activity->loan_id);
                          } else {
                            $viewUrl = route('admin.loans.show', $activity->loan_id);
                          }
                        }
                      ?>

                      <?php if ($viewUrl): ?>
                        <a href="<?php echo e($viewUrl); ?>" class="btn btn-sm btn-primary">
                          <i class="mdi mdi-eye"></i> View
                        </a>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No recent activity</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  @if($canManageDashboardEvents && ($calendarPreview['events_enabled'] ?? false))
    <div class="modal fade" id="addDashboardEventModal" tabindex="-1" aria-labelledby="addDashboardEventModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.dashboard-events.store') }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addDashboardEventModalLabel">
              <i class="mdi mdi-calendar-plus me-2"></i>Add Event
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            @if($errors->dashboardEvent->any())
              <div class="alert alert-danger">
                @foreach($errors->dashboardEvent->all() as $error)
                  <div>{{ $error }}</div>
                @endforeach
              </div>
            @endif
            <div class="mb-3">
              <label for="dashboardEventTitle" class="form-label">Title</label>
              <input type="text" id="dashboardEventTitle" name="title" class="form-control" value="{{ old('title') }}" maxlength="120" required>
            </div>
            <div class="row">
              <div class="col-md-7 mb-3">
                <label for="dashboardEventDate" class="form-label">Date</label>
                <input type="date" id="dashboardEventDate" name="event_date" class="form-control" value="{{ old('event_date', now()->toDateString()) }}" required>
              </div>
              <div class="col-md-5 mb-3">
                <label for="dashboardEventTime" class="form-label">Time</label>
                <input type="time" id="dashboardEventTime" name="event_time" class="form-control" value="{{ old('event_time') }}">
              </div>
            </div>
            <div class="mb-3">
              <label for="dashboardEventCategory" class="form-label">Category</label>
              <select id="dashboardEventCategory" name="category" class="form-control" required>
                <option value="general" @selected(old('category', 'general') === 'general')>General</option>
                <option value="collection" @selected(old('category') === 'collection')>Collection</option>
                <option value="field_visit" @selected(old('category') === 'field_visit')>Field Visit</option>
                <option value="meeting" @selected(old('category') === 'meeting')>Meeting</option>
                <option value="approval" @selected(old('category') === 'approval')>Approval</option>
              </select>
            </div>
            <div class="mb-0">
              <label for="dashboardEventNotes" class="form-label">Notes</label>
              <textarea id="dashboardEventNotes" name="notes" class="form-control" rows="3" maxlength="500">{{ old('notes') }}</textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="mdi mdi-content-save me-1"></i>Save Event
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <!-- Chart.js Script -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Loans vs Savings Chart
      const ctx = document.getElementById('loansVsSavingsChart');
      if (ctx) {
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: {!! json_encode($chartData['months'] ?? []) !!},
            datasets: [
              {
                label: 'Loans Disbursed',
                data: {!! json_encode($chartData['loans'] ?? []) !!},
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointHoverBackgroundColor: '#1d4ed8',
                pointHoverBorderColor: '#ffffff',
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 3,
                tension: 0.4,
                fill: true
              },
              {
                label: 'Cash Securities',
                data: {!! json_encode($chartData['savings'] ?? []) !!},
                borderColor: '#0f766e',
                backgroundColor: 'rgba(15, 118, 110, 0.13)',
                pointBackgroundColor: '#0f766e',
                pointBorderColor: '#ffffff',
                pointHoverBackgroundColor: '#115e59',
                pointHoverBorderColor: '#ffffff',
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 3,
                tension: 0.4,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              legend: {
                position: 'top',
                align: 'end',
                labels: {
                  color: '#172033',
                  usePointStyle: true,
                  pointStyle: 'circle',
                  padding: 20,
                  boxWidth: 8,
                  font: {
                    size: 12,
                    weight: '600'
                  }
                }
              },
              title: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(100, 116, 139, 0.16)',
                  drawBorder: false
                },
                ticks: {
                  color: '#64748b',
                  callback: function(value) {
                    return 'UGX ' + value.toLocaleString();
                  }
                }
              },
              x: {
                grid: {
                  display: false,
                  drawBorder: false
                },
                ticks: {
                  color: '#64748b'
                }
              }
            }
          }
        });
      }

      @if($errors->dashboardEvent->any())
        const addEventModal = document.getElementById('addDashboardEventModal');
        if (addEventModal && window.bootstrap && bootstrap.Modal) {
          bootstrap.Modal.getOrCreateInstance(addEventModal).show();
        }
      @endif
    });
  </script>
