@php
    $stats = $officerStats ?? [];
@endphp

<style>
  .officer-dashboard {
    min-height: 100%;
  }

  .officer-dashboard .metric-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    height: 100%;
  }

  .officer-dashboard .metric-card .card-body {
    min-height: 132px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: .75rem;
  }

  .officer-dashboard .metric-label {
    color: #64748b;
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
  }

  .officer-dashboard .metric-value {
    color: #0f172a;
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.1;
    overflow-wrap: anywhere;
  }

  .officer-dashboard .metric-subtext {
    color: #64748b;
    font-size: .82rem;
  }

  .officer-dashboard .metric-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    font-size: 1.15rem;
    color: #0f5fb8;
    background: #e0f2fe;
  }

  .officer-dashboard .metric-icon.success {
    color: #15803d;
    background: #dcfce7;
  }

  .officer-dashboard .metric-icon.warning {
    color: #a16207;
    background: #fef3c7;
  }

  .officer-dashboard .metric-icon.danger {
    color: #b91c1c;
    background: #fee2e2;
  }

  .officer-dashboard .work-panel {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
  }

  .officer-dashboard .collection-row {
    display: grid;
    grid-template-columns: 1.2fr .9fr .8fr .9fr auto;
    gap: .75rem;
    align-items: center;
    padding: .8rem 0;
    border-bottom: 1px solid #eef2f7;
  }

  .officer-dashboard .collection-row:last-child {
    border-bottom: 0;
  }

  @media (max-width: 991.98px) {
    .officer-dashboard .collection-row {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="officer-dashboard container-fluid">
  <div class="page-header mb-4">
    <h3 class="page-title">
      <span class="page-title-icon bg-gradient-primary text-white me-2">
        <i class="mdi mdi-account-cash"></i>
      </span>
      My Collections Dashboard
    </h3>
    <nav aria-label="breadcrumb">
      <ul class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">
          Welcome back, {{ Auth::user()->name }}.
        </li>
      </ul>
    </nav>
  </div>

  <div class="row">
    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
      <div class="w-100">
        <div class="metric-card">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-3">
              <div class="metric-label">Assigned Active Loans</div>
              <span class="metric-icon"><i class="mdi mdi-clipboard-account-outline"></i></span>
            </div>
            <div>
              <div class="metric-value">{{ number_format($stats['assigned_active_loans'] ?? 0) }}</div>
              <div class="metric-subtext">
                <a href="{{ route('admin.loans.active', ['type' => 'personal', 'per_page' => 20]) }}">
                  {{ number_format($stats['assigned_personal_loans'] ?? 0) }} personal
                </a>
                /
                <a href="{{ route('admin.loans.active', ['type' => 'group', 'per_page' => 20]) }}">
                  {{ number_format($stats['assigned_group_loans'] ?? 0) }} group
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
      <div class="w-100">
        <div class="metric-card">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-3">
              <div class="metric-label">Due Today</div>
              <span class="metric-icon warning"><i class="mdi mdi-calendar-today"></i></span>
            </div>
            <div>
              <div class="metric-value">UGX {{ number_format($stats['due_today_amount'] ?? 0) }}</div>
              <div class="metric-subtext">
                {{ number_format($stats['due_today_count'] ?? 0) }} loan(s)
                <br>
                <a href="{{ route('admin.loans.active.collections', ['per_page' => 20]) }}">Personal queue</a>
                /
                <a href="{{ route('admin.loans.active', ['type' => 'group', 'status' => 'due_today', 'per_page' => 20]) }}">Group loans</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
      <div class="w-100">
        <div class="metric-card">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-3">
              <div class="metric-label">Overdue Assigned Loans</div>
              <span class="metric-icon danger"><i class="mdi mdi-alert-circle-outline"></i></span>
            </div>
            <div>
              <div class="metric-value">UGX {{ number_format($stats['overdue_amount'] ?? 0) }}</div>
              <div class="metric-subtext">
                {{ number_format($stats['overdue_count'] ?? 0) }} loan(s)
                <br>
                <a href="{{ route('admin.loans.active.risk-follow-up', ['per_page' => 20]) }}">Personal queue</a>
                /
                <a href="{{ route('admin.loans.active', ['type' => 'group', 'status' => 'overdue', 'per_page' => 20]) }}">Group loans</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
      <a href="{{ $performanceLinks['month'] ?? route('admin.repayments.history') }}" class="text-decoration-none w-100">
        <div class="metric-card">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-3">
              <div class="metric-label">Collected This Month</div>
              <span class="metric-icon success"><i class="mdi mdi-cash-check"></i></span>
            </div>
            <div>
              <div class="metric-value">UGX {{ number_format($stats['collections_month'] ?? 0) }}</div>
              <div class="metric-subtext">{{ number_format($stats['collections_month_count'] ?? 0) }} payment(s)</div>
            </div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-4 grid-margin stretch-card">
      <div class="work-panel w-100">
        <div class="card-body">
          <h5 class="mb-3">My Performance</h5>
          <div class="d-grid gap-2">
            <a href="{{ $performanceLinks['today'] ?? route('admin.repayments.history') }}" class="btn btn-outline-success text-start">
              <i class="mdi mdi-calendar-today me-1"></i>
              Today: UGX {{ number_format($stats['collections_today'] ?? 0) }}
            </a>
            <a href="{{ $performanceLinks['week'] ?? route('admin.repayments.history') }}" class="btn btn-outline-primary text-start">
              <i class="mdi mdi-calendar-week me-1"></i>
              This Week: UGX {{ number_format($stats['collections_week'] ?? 0) }}
            </a>
            <a href="{{ $performanceLinks['month'] ?? route('admin.repayments.history') }}" class="btn btn-outline-info text-start">
              <i class="mdi mdi-calendar-month me-1"></i>
              This Month: UGX {{ number_format($stats['collections_month'] ?? 0) }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8 grid-margin stretch-card">
      <div class="work-panel w-100">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Recent Collections</h5>
            <a href="{{ route('admin.repayments.history') }}" class="btn btn-sm btn-outline-primary">
              <i class="mdi mdi-filter-outline"></i> Filter Collections
            </a>
          </div>

          @forelse($recentCollections ?? [] as $collection)
            <div class="collection-row">
              <div>
                <div class="fw-semibold">{{ $collection->borrower_name ?: 'Unknown borrower' }}</div>
                <div class="text-muted small">{{ $collection->loan_code }} - {{ $collection->branch_name ?? 'No branch' }}</div>
              </div>
              <div>
                <div class="text-muted small">Collected</div>
                <div class="fw-semibold">UGX {{ number_format($collection->amount ?? 0) }}</div>
              </div>
              <div>
                <div class="text-muted small">Date</div>
                <div>{{ $collection->date_created ? \Carbon\Carbon::parse($collection->date_created)->format('Y-m-d') : 'N/A' }}</div>
              </div>
              <div>
                <div class="text-muted small">Reference</div>
                <div>{{ $collection->transaction_reference ?: 'N/A' }}</div>
              </div>
              <div>
                @if(($collection->loan_type ?? 'personal') === 'personal')
                  <a href="{{ route('admin.repayments.receipt', $collection->id) }}" class="btn btn-sm btn-outline-success">
                    <i class="mdi mdi-receipt"></i> Receipt
                  </a>
                @else
                  <a href="{{ route('admin.loans.repayments.schedules', ['id' => $collection->loan_id, 'type' => 'group']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="mdi mdi-calendar-clock"></i> Schedules
                  </a>
                @endif
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">No collections recorded for your assigned loans yet.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
