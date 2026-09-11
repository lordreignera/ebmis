@extends('layouts.admin')

@section('title', 'Self-Applied Loan Applications')

@section('content')
@php
    $statusTabs = [
        'fo_verification' => ['label' => 'FO Visit', 'long' => 'Pending FO Visit', 'icon' => 'mdi-map-marker-check', 'tone' => 'warning'],
        'scoring' => ['label' => 'Scoring', 'long' => 'Pending Scoring', 'icon' => 'mdi-calculator-variant', 'tone' => 'secondary'],
        'fo_review' => ['label' => 'FO Review', 'long' => 'Pending FO Review', 'icon' => 'mdi-account-search', 'tone' => 'info'],
        'converted' => ['label' => 'Converted', 'long' => 'Converted to Loan', 'icon' => 'mdi-check-circle-outline', 'tone' => 'success'],
        'rejected' => ['label' => 'Rejected', 'long' => 'Rejected', 'icon' => 'mdi-close-circle-outline', 'tone' => 'danger'],
        'all' => ['label' => 'All', 'long' => 'All Applications', 'icon' => 'mdi-format-list-bulleted', 'tone' => 'primary'],
    ];

    $selectedStatus = $statusTabs[$tab] ?? $statusTabs['fo_verification'];
    $money = fn ($value) => 'UGX ' . number_format((float) $value, 0);
@endphp

<div class="content-wrapper client-applications-index-page">
    <div class="page-header client-applications-titlebar">
        <div>
            <h3 class="page-title mb-1">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-file-account"></i>
                </span>
                Self-Applied Loan Applications
            </h3>
            <div class="text-muted small">Review public applications, assign field verification, and convert approved clients.</div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('admin/home') }}">Home</a></li>
                <li class="breadcrumb-item active">Self-Applied Applications</li>
            </ol>
        </nav>
    </div>

    <div class="application-summary-grid">
        @foreach($statusTabs as $key => $item)
            @php
                $count = (int) ($counts[$key] ?? 0);
                $isActive = $tab === $key;
            @endphp
            <a class="application-summary-tile tile-{{ $item['tone'] }} {{ $isActive ? 'is-active' : '' }}"
               href="{{ route('admin.client-applications.index', array_merge(request()->except(['tab', 'page']), ['tab' => $key])) }}">
                <span class="summary-icon"><i class="mdi {{ $item['icon'] }}"></i></span>
                <span class="summary-copy">
                    <span class="summary-label">{{ $item['long'] }}</span>
                    <span class="summary-count">{{ number_format($count) }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="applications-workspace">
        <div class="applications-toolbar">
            <div>
                <div class="eyebrow">Current View</div>
                <h4>{{ $selectedStatus['long'] }}</h4>
                <div class="text-muted small">{{ number_format($applications->total()) }} matching application{{ $applications->total() === 1 ? '' : 's' }}</div>
            </div>

            <form method="GET" class="application-filter-form">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="filter-field filter-search">
                    <label>Search</label>
                    <div class="input-with-icon">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control" placeholder="Code, name, phone, NIN"
                               value="{{ $search }}">
                    </div>
                </div>
                <div class="filter-field">
                    <label>Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) $branch === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-filter me-1"></i>Apply
                </button>
                <a href="{{ route('admin.client-applications.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Clear</a>
            </form>
        </div>

        @if($applications->isEmpty())
            <div class="empty-state">
                <i class="mdi mdi-inbox-full"></i>
                <strong>No applications found</strong>
                <span>Try a different status, branch, or search term.</span>
            </div>
        @else
            <div class="application-list" role="list">
                @foreach($applications as $app)
                    @php
                        $score = $app->composite_score;
                        $scoreClass = $score === null ? 'muted' : ($score >= 85 ? 'success' : ($score >= 65 ? 'warning' : 'danger'));
                        $lightColor = match ($app->traffic_light) {
                            'GREEN' => '#198754',
                            'YELLOW' => '#ffc107',
                            'RED' => '#dc3545',
                            default => '#94a3b8',
                        };
                    @endphp
                    <article class="application-row" role="listitem">
                        <div class="application-main">
                            <div class="application-code-line">
                                <code>{{ $app->application_code }}</code>
                                <span class="status-pill bg-{{ $app->statusBadgeClass() }}">{{ $app->statusLabel() }}</span>
                            </div>
                            <div class="applicant-name">{{ $app->full_name }}</div>
                            <div class="applicant-meta">
                                <span><i class="fas fa-phone-alt"></i>{{ $app->phone }}</span>
                                @if($app->national_id)
                                    <span><i class="fas fa-id-card"></i>{{ $app->national_id }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="application-detail">
                            <span class="detail-label">Branch</span>
                            <span class="detail-value">{{ $app->branch?->name ?? 'Not set' }}</span>
                        </div>

                        <div class="application-detail">
                            <span class="detail-label">Product</span>
                            <span class="detail-value">{{ $app->product?->name ?? 'Not set' }}</span>
                        </div>

                        <div class="application-detail amount-detail">
                            <span class="detail-label">Requested</span>
                            <span class="detail-value">{{ $money($app->requested_amount) }}</span>
                        </div>

                        <div class="application-score">
                            <div class="score-chip score-{{ $scoreClass }}">
                                <span class="score-label">Score</span>
                                <strong>{{ $score !== null ? $score . '/100' : 'Pending' }}</strong>
                            </div>
                            <div class="traffic-chip">
                                <span class="traffic-dot" style="background: {{ $lightColor }}"></span>
                                <span>{{ $app->traffic_light ?: 'Pending' }}</span>
                            </div>
                        </div>

                        <div class="application-date">
                            <span class="detail-label">Submitted</span>
                            <span class="detail-value">{{ $app->created_at->format('d M Y') }}</span>
                        </div>

                        <div class="application-actions">
                            @if($app->status === 'pending_fo_verification')
                                <a href="{{ route('admin.client-applications.verify', $app->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-clipboard-check me-1"></i>Verify
                                </a>
                            @endif
                            <a href="{{ route('admin.client-applications.show', $app->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-row">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.client-applications-index-page {
    color: #111827;
}

.client-applications-titlebar {
    align-items: flex-start;
    gap: 16px;
}

.application-summary-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.application-summary-tile {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 82px;
    padding: 12px;
    border: 1px solid #dbe5ec;
    border-radius: 8px;
    background: #ffffff;
    color: #111827;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}

.application-summary-tile:hover {
    color: #111827;
    border-color: #b9c8d6;
    box-shadow: 0 5px 14px rgba(15, 23, 42, .08);
}

.application-summary-tile.is-active {
    border-color: #0d6efd;
    box-shadow: inset 0 0 0 1px #0d6efd, 0 5px 14px rgba(13, 110, 253, .10);
}

.summary-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    background: #eef2f7;
    color: #334155;
    flex: 0 0 auto;
}

.tile-warning .summary-icon { background: #fff8e1; color: #946200; }
.tile-secondary .summary-icon { background: #f1f5f9; color: #475569; }
.tile-info .summary-icon { background: #e0f2fe; color: #0369a1; }
.tile-success .summary-icon { background: #dcfce7; color: #166534; }
.tile-danger .summary-icon { background: #fee2e2; color: #991b1b; }
.tile-primary .summary-icon { background: #dbeafe; color: #1d4ed8; }

.summary-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.summary-label {
    color: #64748b;
    font-size: .72rem;
    line-height: 1.2;
    white-space: normal;
}

.summary-count {
    color: #0f172a;
    font-size: 1.35rem;
    line-height: 1.15;
    font-weight: 800;
}

.applications-workspace {
    background: #ffffff;
    border: 1px solid #dbe5ec;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
}

.applications-toolbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    padding: 16px;
    border-bottom: 1px solid #e5edf3;
}

.applications-toolbar h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #111827;
}

.eyebrow {
    color: #64748b;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.application-filter-form {
    display: grid;
    grid-template-columns: minmax(220px, 300px) minmax(160px, 220px) auto auto;
    align-items: end;
    gap: 10px;
    min-width: 0;
}

.filter-field label {
    display: block;
    margin-bottom: 4px;
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    top: 50%;
    left: 10px;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: .85rem;
}

.input-with-icon .form-control {
    padding-left: 32px;
}

.application-list {
    display: flex;
    flex-direction: column;
}

.application-row {
    display: grid;
    grid-template-columns: minmax(210px, 1.55fr) minmax(120px, .85fr) minmax(145px, 1fr) minmax(120px, .85fr) minmax(112px, .75fr) minmax(92px, .65fr) auto;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid #e5edf3;
}

.application-row:hover {
    background: #f8fafc;
}

.application-row:last-child {
    border-bottom: 0;
}

.application-main,
.application-detail,
.application-score,
.application-date {
    min-width: 0;
}

.application-code-line {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}

.application-code-line code {
    color: #1f2937;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 3px 6px;
    font-size: .75rem;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 3px 8px;
    color: #ffffff;
    font-size: .68rem;
    font-weight: 800;
    white-space: nowrap;
}

.status-pill.bg-warning,
.status-pill.bg-light {
    color: #111827;
}

.applicant-name {
    color: #0f172a;
    font-weight: 800;
    line-height: 1.25;
    word-break: break-word;
}

.applicant-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
    color: #64748b;
    font-size: .78rem;
}

.applicant-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.detail-label,
.score-label {
    display: block;
    color: #64748b;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .03em;
    text-transform: uppercase;
}

.detail-value {
    display: block;
    color: #111827;
    font-size: .86rem;
    font-weight: 700;
    line-height: 1.25;
    word-break: break-word;
}

.amount-detail .detail-value {
    white-space: nowrap;
}

.application-score {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.score-chip {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    width: fit-content;
    min-width: 86px;
    padding: 6px 8px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}

.score-chip strong {
    color: #111827;
    font-size: .86rem;
    line-height: 1.15;
}

.score-success { background: #ecfdf3; border-color: #bbf7d0; }
.score-warning { background: #fff8e1; border-color: #fde68a; }
.score-danger { background: #fef2f2; border-color: #fecaca; }
.score-muted { background: #f8fafc; border-color: #e2e8f0; }

.traffic-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #475569;
    font-size: .74rem;
    font-weight: 800;
}

.traffic-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, .06);
}

.application-actions {
    display: flex;
    justify-content: flex-end;
    gap: 6px;
    white-space: nowrap;
}

.application-actions .btn {
    border-radius: 6px;
    font-weight: 700;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 260px;
    padding: 32px;
    color: #64748b;
    text-align: center;
}

.empty-state i {
    color: #94a3b8;
    font-size: 2.3rem;
    margin-bottom: 8px;
}

.empty-state strong {
    color: #111827;
    font-size: 1rem;
}

.pagination-row {
    padding: 12px 16px;
    border-top: 1px solid #e5edf3;
}

@media (max-width: 1400px) {
    .application-summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .application-row {
        grid-template-columns: minmax(220px, 1.5fr) minmax(150px, 1fr) minmax(150px, 1fr) minmax(120px, .85fr);
    }
}

@media (max-width: 992px) {
    .applications-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .application-filter-form {
        grid-template-columns: 1fr 1fr auto auto;
    }

    .application-row {
        grid-template-columns: 1fr 1fr;
        align-items: start;
    }

    .application-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .application-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .application-filter-form {
        grid-template-columns: 1fr;
    }

    .application-filter-form .btn {
        width: 100%;
    }

    .application-row {
        grid-template-columns: 1fr;
    }

    .application-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .application-actions .btn {
        width: 100%;
    }
}
</style>
@endpush
@endsection
