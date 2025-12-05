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
</style>

<div class="main-panel">
  <div class="content-wrapper">
    
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
            <span></span>Welcome back, {{ Auth::user()->name }}! 👋
          </li>
        </ul>
      </nav>
    </div>

    <!-- Top Statistics Cards Row 1 - Main Metrics -->
    <div class="row">
      
      <!-- Total Members Card -->
      <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card audit-card h-100">
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
        <div class="card audit-card h-100">
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
        <div class="card audit-card h-100">
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
        <div class="card audit-card h-100">
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
        <div class="card card-bordered h-100">
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
        <div class="card card-bordered h-100">
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
        <div class="card card-bordered h-100">
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
        <div class="card card-bordered h-100">
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
        <div class="card card-bordered h-100">
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
        <div class="card card-bordered h-100">
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
                      @if(isset($activity->loan_id) && $activity->loan_id)
                        @php
                          // Check if loan has been disbursed by checking disbursements table
                          $loan = \App\Models\PersonalLoan::find($activity->loan_id);
                          if (!$loan) {
                            $loan = \App\Models\GroupLoan::find($activity->loan_id);
                          }
                          $hasDisbursement = false;
                          
                          if ($loan) {
                            $hasDisbursement = $loan->disbursements()->where('status', 2)->exists();
                          }
                          
                          // Route based on loan status and disbursement status
                          // 0=Pending Approval, 1=Approved (Pending Disbursement), 2=Disbursed (Active/Schedules), 3=Completed
                          if ($hasDisbursement || $activity->status == '2') {
                            // If disbursed or status is 2, show repayment schedules
                            $viewUrl = route('admin.loans.repayments.schedules', $activity->loan_id);
                          } else {
                            // Otherwise route based on status
                            $status = $activity->status ?? '0';
                            if ($status == '0') {
                              $viewUrl = route('admin.loans.show', $activity->loan_id); // Pending approval - loan details
                            } elseif ($status == '1') {
                              $viewUrl = route('admin.loans.disbursements.approve.show', $activity->loan_id); // Approved - disbursement page
                            } elseif ($status == '3') {
                              $viewUrl = route('admin.loans.repayments.schedules', $activity->loan_id); // Completed - repayment schedules
                            } else {
                              $viewUrl = route('admin.loans.show', $activity->loan_id);
                            }
                          }
                        @endphp
                        <a href="{{ $viewUrl }}" class="btn btn-sm btn-primary">
                          <i class="mdi mdi-eye"></i> View
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
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
  <!-- content-wrapper ends -->
  
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
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
              },
              {
                label: 'Cash Securities',
                data: {!! json_encode($chartData['savings'] ?? []) !!},
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
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
              },
              title: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) {
                    return 'UGX ' + value.toLocaleString();
                  }
                }
              }
            }
          }
        });
      }
    });
  </script>
  
  <!-- Footer -->
  @include('admin.footer')
  
</div>
<!-- main-panel ends -->