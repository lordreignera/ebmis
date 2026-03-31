@extends('layouts.admin')

@section('title', 'Self-Applied Loan Applications')

@section('content')
<div class="content-wrapper">
  <div class="page-header">
    <h3 class="page-title">
      <span class="page-title-icon bg-gradient-primary text-white me-2">
        <i class="mdi mdi-account-arrow-right"></i>
      </span>
      Self-Applied Loan Applications
    </h3>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('admin/home') }}">Home</a></li>
        <li class="breadcrumb-item active">Self-Applied Applications</li>
      </ol>
    </nav>
  </div>

  <div class="row">
    <div class="col-12">

      {{-- Stat Cards --}}
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center p-3">
              <div class="text-muted small mb-1">Pending Scoring</div>
              <div class="fs-3 fw-bold text-secondary">{{ $counts['scoring'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body text-center p-3">
              <div class="text-muted small mb-1">Pending FO Review</div>
              <div class="fs-3 fw-bold text-warning">{{ $counts['fo_review'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body text-center p-3">
              <div class="text-muted small mb-1">Converted to Loan</div>
              <div class="fs-3 fw-bold text-success">{{ $counts['converted'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-body text-center p-3">
              <div class="text-muted small mb-1">Rejected</div>
              <div class="fs-3 fw-bold text-danger">{{ $counts['rejected'] }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Main Card --}}
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">

          {{-- Tabs --}}
          <ul class="nav nav-pills mb-0">
            @foreach([
              ['fo_review', 'Pending FO Review', 'warning'],
              ['scoring',   'Pending Scoring',   'secondary'],
              ['converted', 'Converted',         'success'],
              ['rejected',  'Rejected',          'danger'],
              ['all',       'All',               'primary'],
            ] as [$key, $label, $color])
            <li class="nav-item">
              <a class="nav-link py-1 px-3 {{ $tab === $key ? 'active bg-'.$color : '' }}"
                 href="{{ route('admin.client-applications.index', array_merge(request()->except(['tab','page']), ['tab' => $key])) }}">
                {{ $label }}
                @if(isset($counts[$key]) && $counts[$key] > 0)
                <span class="badge bg-{{ $tab === $key ? 'white text-'.$color : $color }} ms-1">{{ $counts[$key] }}</span>
                @endif
              </a>
            </li>
            @endforeach
          </ul>

          {{-- Search + Filter --}}
          <form method="GET" class="d-flex gap-2 align-items-center" style="min-width:0">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Name / Code / Phone"
                   value="{{ $search }}" style="width:160px">
            <select name="branch_id" class="form-select form-select-sm" style="width:130px">
              <option value="">All Branches</option>
              @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
              @endforeach
            </select>
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            <a href="{{ route('admin.client-applications.index', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
          </form>
        </div>

        <div class="card-body p-0">
          @if($applications->isEmpty())
          <div class="text-center py-5 text-muted">
            <i class="mdi mdi-inbox-full fs-1 d-block mb-2"></i>
            No applications found.
          </div>
          @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Application Code</th>
                  <th>Applicant</th>
                  <th>Branch</th>
                  <th>Product</th>
                  <th class="text-end">Amount (UGX)</th>
                  <th class="text-center">Score</th>
                  <th class="text-center">Light</th>
                  <th class="text-center">Status</th>
                  <th>Submitted</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($applications as $app)
                <tr>
                  <td><code class="small">{{ $app->application_code }}</code></td>
                  <td>
                    <div class="fw-semibold">{{ $app->full_name }}</div>
                    <div class="text-muted small">{{ $app->phone }}</div>
                  </td>
                  <td>{{ $app->branch?->name ?? '—' }}</td>
                  <td>{{ $app->product?->name ?? '—' }}</td>
                  <td class="text-end fw-semibold">{{ number_format($app->requested_amount) }}</td>
                  <td class="text-center">
                    @if($app->composite_score !== null)
                    <span class="badge bg-{{ $app->composite_score >= 85 ? 'success' : ($app->composite_score >= 65 ? 'warning' : 'danger') }}">
                      {{ $app->composite_score }}/100
                    </span>
                    @else
                    <span class="text-muted small">—</span>
                    @endif
                  </td>
                  <td class="text-center">
                    @if($app->traffic_light)
                    <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $app->traffic_light === 'GREEN' ? '#198754' : ($app->traffic_light === 'YELLOW' ? '#ffc107' : '#dc3545') }};"></span>
                    <span class="small ms-1 text-{{ $app->trafficLightClass() }}">{{ $app->traffic_light }}</span>
                    @else
                    <span class="text-muted small">Pending</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <span class="badge bg-{{ $app->statusBadgeClass() }}">{{ $app->statusLabel() }}</span>
                  </td>
                  <td class="small text-muted">{{ $app->created_at->format('d M Y') }}</td>
                  <td>
                    <a href="{{ route('admin.client-applications.show', $app->id) }}"
                       class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye me-1"></i>View
                    </a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="px-3 py-2">
            {{ $applications->links() }}
          </div>
          @endif
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
