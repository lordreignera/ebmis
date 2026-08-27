@extends('layouts.admin')

@section('title', $pageTitle)

@push('styles')
<style>
    .dimension-card { border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 8px 22px rgba(15, 23, 42, .06); height: 100%; }
    .dimension-card__head { align-items: flex-start; display: flex; gap: 1rem; justify-content: space-between; }
    .dimension-card__icon { align-items: center; background: #eff6ff; border-radius: 12px; color: #2563eb; display: flex; flex: 0 0 44px; font-size: 1.4rem; height: 44px; justify-content: center; }
    .dimension-card__amount { color: #0f172a; font-size: 1.1rem; font-weight: 800; }
    .loan-state-strip { display: grid; gap: .45rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .loan-state-cell { background: #f8fafc; border-radius: 9px; padding: .55rem .35rem; text-align: center; }
    .loan-state-cell strong { color: #0f172a; display: block; font-size: .95rem; }
    .loan-state-cell span { color: #64748b; display: block; font-size: .68rem; font-weight: 700; line-height: 1.15; }
    .state-legend { display: flex; flex-wrap: wrap; gap: .45rem; }
    .state-legend .badge { font-size: .72rem; padding: .45rem .6rem; }
    @media (max-width: 575.98px) { .loan-state-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('admin.partials.page-header', [
        'title' => $pageTitle,
        'subtitle' => $pageSubtitle,
        'icon' => $dimensionLabel === 'Branch' ? 'mdi mdi-source-branch' : 'mdi mdi-package-variant',
        'backFallback' => route('admin.modules.loan-portfolio'),
        'backLabel' => 'Back to Loan Portfolio',
    ])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="fw-bold mb-2">Loan lifecycle used throughout this report</div>
            <div class="state-legend">
                <span class="badge bg-warning text-dark">0 Pending</span>
                <span class="badge bg-info text-dark">1 Approved</span>
                <span class="badge bg-success">2 Active / Disbursed</span>
                <span class="badge bg-secondary">3 Closed</span>
                <span class="badge bg-danger">4 Rejected</span>
                <span class="badge bg-primary">5 Restructured</span>
                <span class="badge bg-dark">6 Stopped</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($items as $item)
            <div class="col-xl-4 col-md-6">
                <div class="dimension-card card">
                    <div class="card-body">
                        <div class="dimension-card__head mb-3">
                            <div>
                                <div class="text-uppercase text-muted small fw-bold">{{ $item['type'] }}</div>
                                <h5 class="mb-1">{{ $item['name'] }}</h5>
                                <div class="dimension-card__amount">UGX {{ number_format($item['principal'], 0) }}</div>
                                <small class="text-muted">{{ number_format($item['total']) }} total loans</small>
                            </div>
                            <div class="dimension-card__icon"><i class="mdi {{ $dimensionLabel === 'Branch' ? 'mdi-office-building' : 'mdi-package-variant' }}"></i></div>
                        </div>

                        <div class="loan-state-strip mb-3">
                            @foreach([
                                0 => 'Pending', 1 => 'Approved', 2 => 'Active', 3 => 'Closed',
                                4 => 'Rejected', 5 => 'Restructured', 6 => 'Stopped'
                            ] as $status => $label)
                                <div class="loan-state-cell">
                                    <strong>{{ number_format($item['statuses'][$status] ?? 0) }}</strong>
                                    <span>{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        <a href="{{ route('admin.loans.index', $item['filters']) }}" class="btn btn-outline-primary w-100">
                            <i class="mdi mdi-format-list-bulleted me-1"></i> Open loan register
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">No {{ strtolower($dimensionLabel) }} portfolio data is available.</div></div>
            </div>
        @endforelse
    </div>
</div>
@endsection
