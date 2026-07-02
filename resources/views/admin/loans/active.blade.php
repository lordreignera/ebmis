@extends('layouts.admin')

@section('title', 'Active Loans')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .followup-strip {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }

    .followup-chip {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: .75rem .9rem;
        background: #fff;
        min-width: 0;
    }

    .followup-chip span {
        display: block;
        color: #64748b;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .followup-chip strong {
        display: block;
        color: #0f172a;
        font-size: 1.15rem;
        line-height: 1.2;
        margin-top: .15rem;
    }

    .loan-list {
        display: grid;
        gap: .65rem;
        overflow-x: visible;
    }

    .loan-list-head,
    .loan-list-row {
        display: grid;
        grid-template-columns: 1.25fr .9fr .82fr .82fr 1fr .78fr .95fr 1.05fr .95fr .8fr;
        gap: .75rem;
        align-items: center;
    }

    .loan-list-head {
        padding: .65rem .85rem;
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        border-bottom: 1px solid #e5e7eb;
    }

    .loan-list-row {
        padding: .85rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        overflow: visible;
    }

    .loan-list-row.is-warning {
        border-color: #facc15;
        background: #fffbeb;
    }

    .loan-cell {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .loan-cell-label {
        display: none;
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: .2rem;
    }

    .loan-money {
        font-weight: 700;
        color: #0f172a;
    }

    .loan-subtext {
        color: #64748b;
        font-size: .78rem;
    }

    .loan-actions {
        display: flex;
        justify-content: flex-start;
    }

    .loan-actions .dropdown-toggle {
        min-width: 104px;
        white-space: nowrap;
        line-height: 1.15;
    }

    .loan-actions .dropdown-menu {
        min-width: 220px;
        background: #ffffff !important;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        padding: .35rem 0;
        z-index: 1060;
    }

    .loan-actions .dropdown-menu.show {
        display: block !important;
    }

    .loan-actions .dropdown-item {
        display: flex;
        align-items: center;
        gap: .45rem;
        background: #ffffff !important;
        color: #111827 !important;
        font-size: .88rem;
        padding: .55rem .85rem;
        white-space: normal;
    }

    .loan-actions .dropdown-item:hover,
    .loan-actions .dropdown-item:focus {
        background: #eef6ff !important;
        color: #0f172a !important;
    }

    .loan-actions .dropdown-item i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        color: inherit !important;
    }

    .loan-actions .dropdown-item.text-danger {
        color: #b91c1c !important;
    }

    .loan-actions .dropdown-divider {
        border-top-color: #e5e7eb !important;
        margin: .25rem 0;
    }

    .active-loans-card,
    .active-loans-card .card-body,
    .active-loans-card .table-container,
    .active-loans-card .loan-list {
        overflow: visible !important;
    }

    .table-container {
        overflow-x: visible;
    }

    .table-header,
    .table-actions {
        flex-wrap: wrap;
        gap: .5rem;
    }

    .table-search,
    .table-search input {
        min-width: 0;
        max-width: 100%;
    }

    #followUpModal .modal-dialog {
        width: min(720px, calc(100% - 2rem));
        max-width: 720px;
        margin-left: auto;
        margin-right: auto;
    }

    #followUpModal .modal-content {
        border: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    #followUpModal .modal-header,
    #followUpModal .modal-footer {
        padding: .65rem 1rem;
    }

    #followUpModal .modal-body {
        max-height: calc(100vh - 190px);
        overflow-y: auto;
        padding: .9rem 1rem;
    }

    #followUpModal .form-label {
        margin-bottom: .25rem;
        font-size: .82rem;
    }

    #followUpModal .form-control,
    #followUpModal .form-select {
        min-height: 36px;
        padding-top: .4rem;
        padding-bottom: .4rem;
    }

    .active-kpi-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .active-kpi-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        min-width: 0;
        overflow: hidden;
    }

    .active-kpi-card .card-body {
        min-height: 136px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .75rem;
    }

    .active-kpi-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .active-kpi-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .active-kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 1.05rem;
    }

    .active-kpi-value {
        color: #0f172a;
        font-size: clamp(1.25rem, 1.7vw, 1.75rem);
        font-weight: 800;
        line-height: 1.08;
        overflow-wrap: anywhere;
    }

    .active-kpi-subtext {
        color: #64748b;
        font-size: .78rem;
        line-height: 1.25;
    }

    .kpi-blue .active-kpi-icon {
        color: #0f5fb8;
        background: #e0f2fe;
    }

    .kpi-teal .active-kpi-icon {
        color: #0f766e;
        background: #ccfbf1;
    }

    .kpi-amber .active-kpi-icon {
        color: #a16207;
        background: #fef3c7;
    }

    .kpi-rose .active-kpi-icon {
        color: #be123c;
        background: #ffe4e6;
    }

    .kpi-red .active-kpi-icon {
        color: #b91c1c;
        background: #fee2e2;
    }

    .kpi-green .active-kpi-icon {
        color: #15803d;
        background: #dcfce7;
    }

    @media (max-width: 1199.98px) {
        .active-kpi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .loan-list-head {
            display: none;
        }

        .loan-list-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .loan-cell-label {
            display: block;
        }

        .followup-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .active-kpi-grid {
            grid-template-columns: 1fr;
        }

        .loan-list-row,
        .followup-strip {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Active Loans</li>
                    </ol>
                </div>
                <h4 class="page-title">Active Loans Management</h4>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="active-kpi-grid">
        <div class="active-kpi-card kpi-blue">
            <div class="card-body">
                <div class="active-kpi-top">
                    <div class="active-kpi-label">Active Loans</div>
                    <span class="active-kpi-icon"><i class="mdi mdi-account-multiple-outline"></i></span>
                </div>
                <div>
                    <div class="active-kpi-value">{{ number_format($stats['total_active'] ?? 0, 0) }}</div>
                    <div class="active-kpi-subtext">Loans with unpaid balances</div>
                </div>
            </div>
        </div>

        <div class="active-kpi-card kpi-teal">
            <div class="card-body">
                <div class="active-kpi-top">
                    <div class="active-kpi-label">Principal Due</div>
                    <span class="active-kpi-icon"><i class="mdi mdi-cash-multiple"></i></span>
                </div>
                <div>
                    <div class="active-kpi-value">{{ number_format($stats['outstanding_principal'] ?? 0, 0) }}</div>
                    <div class="active-kpi-subtext">UGX unpaid principal</div>
                </div>
            </div>
        </div>

        <div class="active-kpi-card kpi-amber">
            <div class="card-body">
                <div class="active-kpi-top">
                    <div class="active-kpi-label">Interest Due</div>
                    <span class="active-kpi-icon"><i class="mdi mdi-percent-outline"></i></span>
                </div>
                <div>
                    <div class="active-kpi-value">{{ number_format($stats['outstanding_interest'] ?? 0, 0) }}</div>
                    <div class="active-kpi-subtext">UGX unpaid interest</div>
                </div>
            </div>
        </div>

        <div class="active-kpi-card kpi-rose">
            <div class="card-body">
                <div class="active-kpi-top">
                    <div class="active-kpi-label">Late Fees Due</div>
                    <span class="active-kpi-icon"><i class="mdi mdi-alert-circle-outline"></i></span>
                </div>
                <div>
                    <div class="active-kpi-value">{{ number_format($stats['outstanding_late_fees'] ?? 0, 0) }}</div>
                    <div class="active-kpi-subtext">UGX after waivers</div>
                </div>
            </div>
        </div>

        <div class="active-kpi-card kpi-red">
            <div class="card-body">
                <div class="active-kpi-top">
                    <div class="active-kpi-label">Overdue Loans</div>
                    <span class="active-kpi-icon"><i class="mdi mdi-calendar-alert"></i></span>
                </div>
                <div>
                    <div class="active-kpi-value">{{ number_format($stats['overdue_count'] ?? 0, 0) }}</div>
                    <div class="active-kpi-subtext">Loans past due date</div>
                </div>
            </div>
        </div>

        <div class="active-kpi-card kpi-green">
            <div class="card-body">
                <div class="active-kpi-top">
                    <div class="active-kpi-label">Today's Collections</div>
                    <span class="active-kpi-icon"><i class="mdi mdi-cash-check"></i></span>
                </div>
                <div>
                    <div class="active-kpi-value">{{ number_format($stats['collections_today'] ?? 0, 0) }}</div>
                    <div class="active-kpi-subtext">UGX collected today</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card active-loans-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter Active Loans</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.loans.active') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Loan code, borrower name, phone...">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="branch" class="form-label">Branch</label>
                            <select class="form-select" id="branch" name="branch">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="product" class="form-label">Product</label>
                            <select class="form-select" id="product" name="product">
                                <option value="">All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="current" {{ request('status') == 'current' ? 'selected' : '' }}>Current</option>
                                <option value="due_today" {{ request('status') == 'due_today' ? 'selected' : '' }}>Due Today</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="restructured" {{ request('status') == 'restructured' ? 'selected' : '' }}>Restructured</option>
                                <option value="risk_followup" {{ request('status') == 'risk_followup' ? 'selected' : '' }}>Risk Follow-up</option>
                                <option value="missing_followup" {{ request('status') == 'missing_followup' ? 'selected' : '' }}>No Follow-up</option>
                                <option value="missing_collateral" {{ request('status') == 'missing_collateral' ? 'selected' : '' }}>No Collateral</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-magnify me-1"></i> Filter
                                </button>
                                <a href="{{ route('admin.loans.active') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Loans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Active Loans ({{ $loans->total() }} total)</h5>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <a class="btn btn-light dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-export me-1"></i> Export
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('admin.loans.active.export', ['format' => 'excel'] + request()->all()) }}">
                                        <i class="mdi mdi-file-excel me-1"></i> Excel
                                    </a>
                                    <a class="dropdown-item" href="{{ route('admin.loans.active.export', ['format' => 'pdf'] + request()->all()) }}">
                                        <i class="mdi mdi-file-pdf me-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($loans->count() > 0)
                        <div class="followup-strip">
                            <div class="followup-chip">
                                <span>Risk follow-up</span>
                                <strong>{{ $stats['risk_followup_count'] ?? 0 }}</strong>
                            </div>
                            <div class="followup-chip">
                                <span>Recorded</span>
                                <strong class="text-success">{{ $stats['followed_up_count'] ?? 0 }}</strong>
                            </div>
                            <div class="followup-chip">
                                <span>Missing</span>
                                <strong class="text-danger">{{ $stats['missing_followup_count'] ?? 0 }}</strong>
                            </div>
                            <div class="followup-chip">
                                <span>Due today</span>
                                <strong class="text-warning">{{ $stats['followup_due_count'] ?? 0 }}</strong>
                            </div>
                            <div class="followup-chip">
                                <span>No collateral</span>
                                <strong class="text-danger">{{ $stats['missing_collateral_count'] ?? 0 }}</strong>
                            </div>
                        </div>
                        <div class="table-container">
                            <div class="table-header">
                                <div class="table-search">
                                    <input type="text" placeholder="Search active loans..." id="quickSearch">
                                </div>
                                <div class="table-actions">
                                    <div class="table-show-entries">
                                        Show 
                                        <select onchange="const url = new URL(window.location.href); url.searchParams.set('per_page', this.value); url.searchParams.delete('page'); window.location.href = url.toString();">
                                            <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                        entries
                                    </div>
                                    <div class="dropdown">
                                        <button class="export-btn dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="mdi mdi-export"></i> Export
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.loans.active.export', ['format' => 'excel'] + request()->all()) }}">
                                                <i class="mdi mdi-file-excel me-1"></i> Excel
                                            </a>
                                            <a class="dropdown-item" href="{{ route('admin.loans.active.export', ['format' => 'pdf'] + request()->all()) }}">
                                                <i class="mdi mdi-file-pdf me-1"></i> PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="loan-list">
                                <div class="loan-list-head">
                                    <div>Borrower</div>
                                    <div>Branch</div>
                                    <div>Loan</div>
                                    <div>Principal</div>
                                    <div>Outstanding</div>
                                    <div>Risk</div>
                                    <div>Responsibility</div>
                                    <div>Security</div>
                                    <div>Follow-up</div>
                                    <div>Actions</div>
                                </div>
                                    @foreach($loans as $index => $loan)
                                        @php
                                            // Determine loan type based on product's period_type
                                            $periodType = $loan->product->period_type ?? 3;
                                            $loanTypeLabel = 'Daily';
                                            if($periodType == 1) {
                                                $loanTypeLabel = 'Weekly';
                                            } elseif($periodType == 2) {
                                                $loanTypeLabel = 'Monthly';
                                            } elseif($periodType == 3) {
                                                $loanTypeLabel = 'Daily';
                                            }
                                            $latestFollowUp = $loan->latest_follow_up ?? null;
                                        @endphp
                                        <div class="loan-list-row {{ ($loan->is_potential_duplicate ?? false) ? 'is-warning' : '' }}" data-loan-search="{{ strtolower($loan->borrower_name . ' ' . $loan->loan_code . ' ' . ($loan->branch_name ?? '') . ' ' . ($loan->assignedTo->name ?? '') . ' ' . ($loan->collateral_summary ?? '') . ' ' . ($loan->risk_classification ?? '')) }}">
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Borrower</span>
                                                <div class="fw-semibold">{{ $loans->firstItem() + $index }}. {{ $loan->borrower_name }}</div>
                                                <div class="loan-subtext">{{ $loan->phone_number ?? 'N/A' }}</div>
                                                @if($loan->is_potential_duplicate ?? false)
                                                    <small class="badge bg-warning text-dark">{{ $loan->duplicate_loans_count ?? 0 }} loans in 2025</small>
                                                @endif
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Branch</span>
                                                <div>{{ $loan->branch_name ?? 'No Branch' }}</div>
                                                <div class="loan-subtext">{{ $loan->product_name ?? 'N/A' }}</div>
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Loan</span>
                                                <div class="account-number">{{ $loan->loan_code }}</div>
                                                <span class="status-badge status-{{ $periodType == 1 ? 'verified' : ($periodType == 2 ? 'pending' : 'individual') }}">
                                                    {{ $loanTypeLabel }}
                                                </span>
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Principal</span>
                                                <div class="loan-money">{{ number_format($loan->principal_amount, 0) }}</div>
                                                @if(isset($loan->disbursement_date))
                                                    <div class="loan-subtext">{{ date('Y-m-d', strtotime($loan->disbursement_date)) }}</div>
                                                @endif
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Outstanding</span>
                                                <div class="loan-money">{{ number_format($loan->outstanding_balance ?? 0, 0) }}</div>
                                                <div class="loan-subtext">P {{ number_format($loan->outstanding_principal ?? 0, 0) }} / I {{ number_format($loan->outstanding_interest ?? 0, 0) }} / L {{ number_format($loan->outstanding_late_fees ?? 0, 0) }}</div>
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Risk</span>
                                                <span class="badge bg-{{ $loan->risk_badge ?? 'secondary' }}">{{ $loan->risk_classification ?? 'Unknown' }}</span>
                                                <div class="loan-subtext">{{ $loan->risk_dpd ?? 0 }} DPD, {{ $loan->risk_overdue_installments ?? 0 }} missed</div>
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Responsibility</span>
                                                <div class="fw-semibold">{{ $loan->assignedTo->name ?? 'Unassigned' }}</div>
                                                <div class="loan-subtext">{{ $loan->assignedTo->designation ?? 'Loan follow-up' }}</div>
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Security</span>
                                                @if($loan->has_collateral ?? false)
                                                    <span class="badge bg-success">Secured</span>
                                                @else
                                                    <span class="badge bg-danger">No security</span>
                                                @endif
                                                <div class="loan-subtext">{{ $loan->collateral_summary ?? 'No collateral recorded' }}</div>
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Follow-up</span>
                                                @if($latestFollowUp)
                                                    <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $latestFollowUp->outcome)) }}</div>
                                                    <div class="loan-subtext">{{ optional($latestFollowUp->follow_up_at)->format('Y-m-d H:i') }} by {{ $latestFollowUp->createdBy->name ?? 'Staff' }}</div>
                                                    @if($latestFollowUp->next_follow_up_date)
                                                        <span class="badge {{ ($loan->follow_up_due ?? false) ? 'bg-warning text-dark' : 'bg-light text-dark' }}">
                                                            Next {{ $latestFollowUp->next_follow_up_date->format('Y-m-d') }}
                                                        </span>
                                                    @endif
                                                @elseif($loan->requires_follow_up ?? false)
                                                    <span class="badge bg-danger">Pending</span>
                                                @else
                                                    <span class="badge bg-light text-dark">Not due</span>
                                                @endif
                                            </div>
                                            <div class="loan-cell">
                                                <span class="loan-cell-label">Actions</span>
                                                <div class="loan-actions">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-primary dropdown-toggle loan-options-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="mdi mdi-dots-vertical"></i> Options
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end loan-options-menu">
                                                            <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}"
                                                               class="dropdown-item" title="View repayment schedules">
                                                                <i class="mdi mdi-calendar-clock"></i> Schedules
                                                            </a>
                                                            <button type="button"
                                                                    class="dropdown-item btn-follow-up"
                                                                    data-loan-id="{{ $loan->id }}"
                                                                    data-loan-type="{{ $loan->loan_type ?? 'personal' }}"
                                                                    data-loan-code="{{ $loan->loan_code }}"
                                                                    data-borrower-name="{{ $loan->borrower_name }}"
                                                                    data-risk-classification="{{ $loan->risk_classification ?? 'Unknown' }}"
                                                                    data-dpd="{{ $loan->risk_dpd ?? 0 }}"
                                                                    data-next-due-amount="{{ $loan->next_due_amount ?? 0 }}"
                                                                    data-next-due-date="{{ $loan->next_due_date ?? '' }}"
                                                                    title="Record collection follow-up">
                                                                <i class="mdi mdi-phone-forward"></i> Follow-up
                                                            </button>
                                                            @if($loan->has_collateral ?? false)
                                                                <button type="button"
                                                                        class="dropdown-item btn-view-collateral"
                                                                        data-loan-id="{{ $loan->id }}"
                                                                        data-loan-type="{{ $loan->loan_type ?? 'personal' }}"
                                                                        data-loan-code="{{ $loan->loan_code }}"
                                                                        data-borrower-name="{{ $loan->borrower_name }}"
                                                                        title="View collateral details">
                                                                    <i class="mdi mdi-shield-check-outline"></i> View Collateral
                                                                </button>
                                                            @else
                                                                <button type="button"
                                                                        class="dropdown-item btn-add-collateral"
                                                                        data-loan-id="{{ $loan->id }}"
                                                                        data-loan-type="{{ $loan->loan_type ?? 'personal' }}"
                                                                        data-loan-code="{{ $loan->loan_code }}"
                                                                        data-borrower-name="{{ $loan->borrower_name }}"
                                                                        data-member-id="{{ $loan->member_id_value ?? '' }}"
                                                                        data-phone="{{ $loan->phone_number ?? '' }}"
                                                                        data-collateral-summary="{{ $loan->collateral_summary ?? 'No collateral recorded' }}"
                                                                        title="Record collateral or cash security for this loan">
                                                                    <i class="mdi mdi-shield-plus-outline"></i> Add Collateral
                                                                </button>
                                                            @endif

                                                            @if(($canReassignLoans ?? false) && ($loan->loan_type ?? 'personal') === 'personal')
                                                                <button type="button"
                                                                        class="dropdown-item btn-reassign-loan"
                                                                        data-loan-id="{{ $loan->id }}"
                                                                        data-loan-code="{{ $loan->loan_code }}"
                                                                        data-borrower-name="{{ $loan->borrower_name }}"
                                                                        data-current-assigned-to="{{ $loan->assigned_to ?? '' }}"
                                                                        data-current-assigned-name="{{ $loan->assignedTo->name ?? 'Unassigned' }}"
                                                                        title="Reassign responsibility for this loan">
                                                                    <i class="mdi mdi-account-switch-outline"></i> Reassign
                                                                </button>
                                                            @endif

                                                            @if(auth()->user()->isSuperAdmin() && ($loan->loan_type ?? 'personal') === 'personal' && (int) ($loan->restructured ?? 0) === 1 && !empty($loan->OLoanID))
                                                                <div class="dropdown-divider"></div>
                                                                <button type="button"
                                                                        class="dropdown-item text-warning btn-revert-restructure"
                                                                        data-loan-id="{{ $loan->id }}"
                                                                        data-loan-code="{{ $loan->loan_code }}"
                                                                        data-original-loan-code="{{ $loan->OLoanID }}"
                                                                        data-borrower-name="{{ $loan->borrower_name }}"
                                                                        title="Revert this restructure and restore the original loan">
                                                                    <i class="mdi mdi-backup-restore"></i> Revert Restructure
                                                                </button>
                                                            @endif

                                                            @if(($loan->is_potential_duplicate ?? false) && auth()->user()->isSuperAdmin())
                                                                <div class="dropdown-divider"></div>
                                                                <button type="button"
                                                                        class="dropdown-item text-danger btn-stop-loan"
                                                                        data-loan-id="{{ $loan->id }}"
                                                                        data-borrower-name="{{ $loan->borrower_name }}"
                                                                        data-loan-code="{{ $loan->loan_code }}"
                                                                        data-disbursement-date="{{ date('Y-m-d', strtotime($loan->disbursement_date)) }}"
                                                                        data-duplicate-count="{{ $loan->duplicate_loans_count ?? 0 }}"
                                                                        title="Stop this loan (Client has {{ $loan->duplicate_loans_count ?? 0 }} loans from 2025)">
                                                                    <i class="mdi mdi-stop-circle"></i> Stop
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                            </div>
                            <div class="modern-pagination">
                                <div class="pagination-info">
                                    @if($loans->total() > 0)
                                        Showing {{ $loans->firstItem() ?? 1 }} to {{ $loans->lastItem() ?? $loans->count() }} of {{ $loans->total() }} entries
                                    @else
                                        No entries found
                                    @endif
                                </div>
                                <div class="pagination-controls">
                                    @if($loans->hasPages())
                                        @if ($loans->onFirstPage())
                                            <span class="pagination-btn" disabled>Previous</span>
                                        @else
                                            <a class="pagination-btn" href="{{ $loans->previousPageUrl() }}">Previous</a>
                                        @endif

                                        <div class="pagination-numbers">
                                            @php
                                                $currentPage = $loans->currentPage();
                                                $lastPage = $loans->lastPage();
                                                $start = max(1, $currentPage - 2);
                                                $end = min($lastPage, $currentPage + 2);
                                                
                                                if ($currentPage <= 3) {
                                                    $end = min(5, $lastPage);
                                                }
                                                if ($currentPage >= $lastPage - 2) {
                                                    $start = max(1, $lastPage - 4);
                                                }
                                            @endphp

                                            @if($start > 1)
                                                <a href="{{ $loans->url(1) }}" class="pagination-btn">1</a>
                                                @if($start > 2)
                                                    <span class="pagination-btn" disabled>...</span>
                                                @endif
                                            @endif

                                            @for ($page = $start; $page <= $end; $page++)
                                                @if ($page == $currentPage)
                                                    <span class="pagination-btn active">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $loans->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                                @endif
                                            @endfor

                                            @if($end < $lastPage)
                                                @if($end < $lastPage - 1)
                                                    <span class="pagination-btn" disabled>...</span>
                                                @endif
                                                <a href="{{ $loans->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                            @endif
                                        </div>

                                        @if ($loans->hasMorePages())
                                            <a class="pagination-btn" href="{{ $loans->nextPageUrl() }}">Next</a>
                                        @else
                                            <span class="pagination-btn" disabled>Next</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="mdi mdi-bank-outline display-4 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Active Loans Found</h5>
                            <p class="text-muted mb-0">No loans match your current filter criteria.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Follow-up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white !important;">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="mdi mdi-phone-forward me-2"></i>Loan Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.loans.follow-ups.store') }}" id="followUpForm">
                @csrf
                <div class="modal-body" style="background: white !important;">
                    <input type="hidden" name="loan_id" id="followup_loan_id">
                    <input type="hidden" name="loan_type" id="followup_loan_type">

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Loan Code</label>
                            <input type="text" class="form-control" id="followup_loan_code" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Borrower</label>
                            <input type="text" class="form-control" id="followup_borrower_name" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Risk Class</label>
                            <input type="text" class="form-control" id="followup_risk" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Contact Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="contact_method" id="followup_contact_method" required>
                                <option value="">Select method</option>
                                <option value="call">Phone Call</option>
                                <option value="sms">SMS</option>
                                <option value="field_visit">Field Visit</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="office_visit">Office Visit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Outcome <span class="text-danger">*</span></label>
                            <select class="form-select" name="outcome" id="followup_outcome" required>
                                <option value="">Select outcome</option>
                                <option value="promised_to_pay">Promised to Pay</option>
                                <option value="willing_to_pay">Willing to Pay</option>
                                <option value="not_reachable">Not Reachable</option>
                                <option value="refused">Refused</option>
                                <option value="reschedule_requested">Reschedule Requested</option>
                                <option value="dispute">Dispute Raised</option>
                                <option value="paid_after_contact">Paid After Contact</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Next Action</label>
                            <select class="form-select" name="next_action" id="followup_next_action">
                                <option value="">Select next action</option>
                                <option value="call_again">Call Again</option>
                                <option value="send_sms">Send SMS</option>
                                <option value="field_visit">Field Visit</option>
                                <option value="escalate_to_manager">Escalate to Manager</option>
                                <option value="restructure">Restructure Review</option>
                                <option value="legal_recovery">Legal Recovery</option>
                                <option value="none">No Further Action</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Promise Date</label>
                            <input type="date" class="form-control" name="promise_date" id="followup_promise_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Promise Amount</label>
                            <input type="number" class="form-control" name="promise_amount" id="followup_promise_amount" min="0" step="0.01">
                            <div class="form-text" id="followup_promise_amount_hint"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Next Follow-up</label>
                            <input type="date" class="form-control" name="next_follow_up_date" id="followup_next_date">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="sms_sent" value="0">
                                <input class="form-check-input" type="checkbox" name="sms_sent" value="1" id="followup_sms_sent">
                                <label class="form-check-label" for="followup_sms_sent">SMS was sent or should be sent</label>
                            </div>
                        </div>
                        <div class="col-12" id="followup_sms_message_wrap" style="display: none;">
                            <label class="form-label fw-bold">SMS Message</label>
                            <textarea class="form-control" name="sms_message" id="followup_sms_message" rows="2" maxlength="1000">Dear client, please contact us about your loan repayment. Thank you.</textarea>
                            <div class="form-text">This will create an SMS record and mark it as sent through the existing SMS module.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Notes <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="notes" id="followup_notes" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: white !important; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="mdi mdi-content-save me-1"></i>Save Follow-up
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($canReassignLoans ?? false)
<div class="modal fade" id="reassignLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white !important;">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="mdi mdi-account-switch-outline me-2"></i>Reassign Loan Responsibility</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reassignLoanForm">
                @csrf
                <div class="modal-body" style="background: white !important;">
                    <div class="mb-3">
                        <label class="form-label">Loan</label>
                        <input type="text" class="form-control" id="reassign_loan_label" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Responsible User</label>
                        <input type="text" class="form-control" id="reassign_current_user" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="reassign_assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select" id="reassign_assigned_to" name="assigned_to" required>
                            <option value="">Select officer or manager</option>
                            @forelse($assignableUsers ?? collect() as $staff)
                                <option value="{{ $staff->id }}">
                                    {{ $staff->name }}
                                    @if($staff->designation)
                                        - {{ $staff->designation }}
                                    @endif
                                    @if($staff->branch)
                                        ({{ $staff->branch->name }})
                                    @endif
                                </option>
                            @empty
                                <option value="" disabled>No active assignment users found</option>
                            @endforelse
                        </select>
                        <div class="form-text">This user becomes responsible for loan follow-up, collection tracking, and performance reporting.</div>
                    </div>
                </div>
                <div class="modal-footer" style="background: white !important; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-info" {{ ($assignableUsers ?? collect())->isEmpty() ? 'disabled' : '' }}>
                        <i class="mdi mdi-content-save me-1"></i> Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Collateral Modal -->
<div class="modal fade" id="loanCollateralModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 640px;">
        <div class="modal-content" style="background: white !important;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="mdi mdi-shield-plus-outline me-2"></i>Loan Collateral</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="loanCollateralForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="background: white !important;">
                    <input type="hidden" id="collateral_loan_id" name="loan_id">
                    <input type="hidden" id="collateral_loan_type" name="loan_type">
                    <input type="hidden" id="collateral_member_id" name="member_id">

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Loan Code</label>
                            <input type="text" class="form-control" id="collateral_loan_code" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Borrower</label>
                            <input type="text" class="form-control" id="collateral_borrower_name" readonly>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted" id="collateral_current_summary"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Collateral Mode</label>
                        <select class="form-select" id="collateral_mode" required>
                            <option value="non_cash">Non-cash collateral</option>
                            <option value="cash_security">Cash security deposit</option>
                        </select>
                        <div class="form-text">Cash security must be completed before the disbursement collateral check passes.</div>
                    </div>

                    <div id="nonCashCollateralFields">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Collateral Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="collateral_field" name="collateral_field">
                                <option value="moveable_assets">Moveable assets</option>
                                <option value="immovable_assets">Immovable assets</option>
                                <option value="stocks_collateral">Business stock</option>
                                <option value="livestock_collateral">Livestock</option>
                                <option value="intellectual_property">Intellectual property</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="collateral_description" name="description" rows="3" placeholder="Describe the pledged asset, owner, location, document reference or estimated value."></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Value (UGX) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control collateral-ugx-input" id="collateral_estimated_value" name="estimated_value" inputmode="decimal" autocomplete="off" placeholder="Market estimate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Forced Sale Value (UGX)</label>
                                <input type="text" class="form-control collateral-ugx-input" id="collateral_forced_sale_value" name="forced_sale_value" inputmode="decimal" autocomplete="off" placeholder="Optional recovery value">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Collateral Document / Photo <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="collateral_document" name="collateral_document" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text">Stored through the same cloud storage flow used for member documents. Accepted: valid PDF, JPG, PNG, max 20MB.</div>
                        </div>
                    </div>

                    <div id="cashSecurityFields" style="display: none;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cash Security Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cash_security_amount" min="1" step="0.01" placeholder="Amount">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="cash_security_payment_type">
                                    <option value="1">Mobile Money</option>
                                    @if(auth()->user()->isSuperAdmin())
                                        <option value="2">Cash</option>
                                        <option value="3">Bank Transfer</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" class="form-control" id="cash_security_phone" placeholder="Client phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Reference / Notes</label>
                                <input type="text" class="form-control" id="cash_security_description" placeholder="Cash security for this loan">
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0 py-2">
                            <i class="mdi mdi-information-outline me-1"></i>
                            Mobile money requests are linked to this exact loan and become valid collateral after callback/status confirmation.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: white !important; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-content-save me-1"></i>Save Collateral
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Collateral Modal -->
<div class="modal fade" id="viewCollateralModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;">
        <div class="modal-content" style="background: white !important;">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title"><i class="mdi mdi-shield-check-outline me-2"></i>Collateral Details</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" style="background: white !important; max-height: 68vh; overflow-y: auto;">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Loan Code</label>
                        <input type="text" class="form-control form-control-sm" id="view_collateral_loan_code" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Borrower</label>
                        <input type="text" class="form-control form-control-sm" id="view_collateral_borrower" readonly>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-success small py-2 px-2 mb-0" id="view_collateral_summary"></div>
                    </div>
                </div>

                <h6 class="small fw-bold mb-1 mt-3">Non-cash Collateral</h6>
                <div id="view_non_cash_collateral" class="small mb-2"></div>

                <h6 class="small fw-bold mb-1 mt-3">Cash Security</h6>
                <div id="view_cash_security" class="small mb-2"></div>

                <h6 class="small fw-bold mb-1 mt-3">Uploaded Evidence</h6>
                <div id="view_collateral_documents" class="small"></div>
            </div>
            <div class="modal-footer py-2" style="background: white !important; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: white !important;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="mdi mdi-calendar-refresh me-2"></i>Reschedule Loan Payments</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rescheduleForm">
                <div class="modal-body" style="background: white !important; padding: 20px;">
                    <input type="hidden" id="reschedule_loan_id">
                    
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Note:</strong> Rescheduling will postpone all pending payments by the specified number of days.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: #000;">Loan Code</label>
                        <input type="text" class="form-control" id="reschedule_loan_code" readonly style="background-color: #f8f9fa;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: #000;">Borrower</label>
                        <input type="text" class="form-control" id="reschedule_borrower_name" readonly style="background-color: #f8f9fa;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: #000;">Current Days Overdue</label>
                        <input type="text" class="form-control text-danger fw-bold" id="reschedule_days_late" readonly style="background-color: #fff3cd;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: #000;">Postpone By (Days) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="reschedule_days" min="1" max="365" required
                               placeholder="Enter number of days to postpone">
                        <div class="form-text">Enter number of days to add to all pending payment dates</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: #000;">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reschedule_reason" rows="3" required
                                  placeholder="Enter reason for rescheduling (e.g., Business closure, Medical emergency, etc.)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: #000;">Apply Late Fee Waiver?</label>
                        @if(auth()->user()->isSuperAdmin())
                            <select class="form-select" id="reschedule_waive_fees">
                                <option value="0">No - Keep existing late fees</option>
                                <option value="1">Yes - Waive all late fees</option>
                            </select>
                        @else
                            <input type="hidden" id="reschedule_waive_fees" value="0">
                            <div class="form-control bg-light">No - only the Super Administrator can waive late fees</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer" style="background: white !important; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-calendar-check me-1"></i>Reschedule Payments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Repayment Modal -->
<div class="modal fade" id="quickRepayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content" style="background: white !important;">
            <div class="modal-header" style="background: white !important; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #000;">Record Loan Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickRepayForm">
                <div class="modal-body" style="background: white !important; padding: 20px;">
                    <input type="hidden" id="modal_loan_id">
                    <input type="hidden" id="modal_schedule_id">
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #000; font-weight: 500;">Loan Code</label>
                        <input type="text" class="form-control" id="modal_loan_code" readonly 
                               style="background-color: #f8f9fa;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #000; font-weight: 500;">Due Date</label>
                        <input type="text" class="form-control" id="modal_due_date" readonly 
                               style="background-color: #f8f9fa;">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #000; font-weight: 500;">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="modal_amount" step="0.01" min="1" required
                               placeholder="Enter payment amount">
                        <div class="form-text">Expected amount: UGX <span id="modal_expected_amount">0</span></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #000; font-weight: 500;">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal_phone" required
                               placeholder="Enter phone number">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #000; font-weight: 500;">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_payment_method" required>
                            <option value="">Select Payment Method</option>
                            @if(auth()->user()->isSuperAdmin())
                                <option value="2">Mobile Money</option>
                                <option value="1">Cash</option>
                                <option value="3">Bank Transfer</option>
                            @else
                                <option value="2">Mobile Money</option>
                            @endif
                        </select>
                    </div>
                    
                    <div class="mb-3" id="modal_network_div" style="display: none;">
                        <label class="form-label" style="color: #000; font-weight: 500;">Network <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_network">
                            <option value="">Select Network</option>
                            <option value="MTN">MTN Money</option>
                            <option value="AIRTEL">Airtel Money</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #000; font-weight: 500;">Notes (Optional)</label>
                        <textarea class="form-control" id="modal_notes" rows="2"
                                  placeholder="Enter any additional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background: white !important; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-check me-1"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@push('scripts')
<script>
$(document).ready(function() {
    // Quick search functionality - redirect to search URL
    $('#quickSearch').on('keyup', function(e) {
        var value = $(this).val().trim();
        
        // Build URL with search parameter
        var url = new URL(window.location.href);
        
        if (value.length > 0) {
            url.searchParams.set('search', value);
            url.searchParams.delete('page'); // Reset to page 1 when searching
        } else {
            url.searchParams.delete('search');
        }
        
        // Debounce the search (wait 500ms after user stops typing)
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            window.location.href = url.toString();
        }, 500);
    });
    
    // Set search box value from URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var searchValue = urlParams.get('search');
    if (searchValue) {
        $('#quickSearch').val(searchValue);
    }

    // Local fallback for row action dropdowns. This keeps Options working even
    // when Bootstrap's dropdown binding is blocked by older admin scripts.
    $(document).on('click', '.loan-options-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $button = $(this);
        const $menu = $button.closest('.dropdown').find('.loan-options-menu').first();
        const isOpen = $menu.hasClass('show');

        $('.loan-options-menu.show').removeClass('show').hide();
        $('.loan-options-toggle[aria-expanded="true"]').attr('aria-expanded', 'false');

        if (!isOpen) {
            $menu.addClass('show').show();
            $button.attr('aria-expanded', 'true');
        }
    });

    $(document).on('click', '.loan-options-menu', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', function() {
        $('.loan-options-menu.show').removeClass('show').hide();
        $('.loan-options-toggle[aria-expanded="true"]').attr('aria-expanded', 'false');
    });

    // Auto-refresh data every 5 minutes
    setInterval(function() {
        if (!$('.modal').hasClass('show')) {
            window.location.reload();
        }
    }, 300000);

    // Handle stop loan button click
    $(document).on('click', '.btn-stop-loan', function() {
        var loanId = $(this).data('loan-id');
        var borrowerName = $(this).data('borrower-name');
        var loanCode = $(this).data('loan-code');
        var disbursementDate = $(this).data('disbursement-date');
        var duplicateCount = $(this).data('duplicate-count');
        
        confirmStopLoan(loanId, borrowerName, loanCode, disbursementDate, duplicateCount);
    });

    $(document).on('click', '.btn-revert-restructure', function() {
        confirmRevertRestructure(
            $(this).data('loan-id'),
            $(this).data('loan-code'),
            $(this).data('original-loan-code'),
            $(this).data('borrower-name')
        );
    });

    $(document).on('click', '.btn-follow-up', function() {
        $('#followUpForm')[0].reset();
        $('#followup_loan_id').val($(this).data('loan-id'));
        $('#followup_loan_type').val($(this).data('loan-type'));
        $('#followup_loan_code').val($(this).data('loan-code'));
        $('#followup_borrower_name').val($(this).data('borrower-name'));
        $('#followup_risk').val(($(this).data('risk-classification') || 'Unknown') + ' / ' + ($(this).data('dpd') || 0) + ' DPD');

        const nextDueAmount = parseFloat($(this).data('next-due-amount')) || 0;
        const nextDueDate = $(this).data('next-due-date') || '';
        $('#followup_promise_amount').val(nextDueAmount > 0 ? nextDueAmount.toFixed(2) : '');
        $('#followup_promise_amount_hint').text(
            nextDueAmount > 0
                ? 'Auto-filled from next unpaid schedule' + (nextDueDate ? ' due ' + nextDueDate : '') + '.'
                : 'No unpaid schedule amount found.'
        );

        toggleFollowUpSmsFields();
        $('#followUpModal').modal('show');
    });

    $('#followup_contact_method, #followup_next_action, #followup_sms_sent').on('change', function() {
        if ($('#followup_contact_method').val() === 'sms' || $('#followup_next_action').val() === 'send_sms') {
            $('#followup_sms_sent').prop('checked', true);
        }

        toggleFollowUpSmsFields();
    });

    $('#followUpForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalHtml = submitButton.html();

        submitButton.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Saving...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                $('#followUpModal').modal('hide');
                Swal.fire('Saved', response.message || 'Follow-up recorded successfully.', 'success')
                    .then(() => window.location.reload());
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                let message = xhr.responseJSON?.message || 'Failed to save follow-up.';

                if (errors) {
                    const firstField = Object.keys(errors)[0];
                    if (firstField && errors[firstField] && errors[firstField][0]) {
                        message = errors[firstField][0];
                    }
                }

                Swal.fire('Not saved', message, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $(document).on('click', '.btn-reassign-loan', function() {
        const loanId = $(this).data('loan-id');
        const loanCode = $(this).data('loan-code') || '';
        const borrowerName = $(this).data('borrower-name') || '';
        const currentAssignedTo = $(this).data('current-assigned-to') || '';
        const currentAssignedName = $(this).data('current-assigned-name') || 'Unassigned';
        const assignUrl = '{{ route("admin.loans.active.assign", ":loan") }}'.replace(':loan', loanId);

        $('#reassignLoanForm').attr('action', assignUrl);
        $('#reassign_loan_label').val(`${loanCode} - ${borrowerName}`);
        $('#reassign_current_user').val(currentAssignedName);
        $('#reassign_assigned_to').val(currentAssignedTo);
        $('#reassignLoanModal').modal('show');
    });

    $(document).on('click', '.btn-add-collateral', function() {
        $('#loanCollateralForm')[0].reset();
        $('#collateral_loan_id').val($(this).data('loan-id'));
        $('#collateral_loan_type').val($(this).data('loan-type'));
        $('#collateral_member_id').val($(this).data('member-id') || '');
        $('#collateral_loan_code').val($(this).data('loan-code'));
        $('#collateral_borrower_name').val($(this).data('borrower-name'));
        $('#cash_security_phone').val($(this).data('phone') || '');
        $('#cash_security_description').val('Cash security for loan ' + ($(this).data('loan-code') || ''));
        $('#collateral_current_summary').text('Current collateral: ' + ($(this).data('collateral-summary') || 'No collateral recorded'));
        toggleCollateralMode();
        $('#loanCollateralModal').modal('show');
    });

    $(document).on('click', '.btn-view-collateral', function() {
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type') || 'personal';
        const detailUrl = '{{ route("admin.loans.collateral.show", ":loan") }}'.replace(':loan', loanId);

        $('#view_collateral_loan_code').val($(this).data('loan-code') || '');
        $('#view_collateral_borrower').val($(this).data('borrower-name') || '');
        $('#view_collateral_summary').html('<i class="mdi mdi-loading mdi-spin me-1"></i>Loading collateral details...');
        $('#view_non_cash_collateral').html('<div class="text-muted">Loading...</div>');
        $('#view_cash_security').html('<div class="text-muted">Loading...</div>');
        $('#view_collateral_documents').html('<div class="text-muted">Loading...</div>');
        $('#viewCollateralModal').modal('show');

        $.ajax({
            url: detailUrl,
            method: 'GET',
            data: {
                loan_type: loanType
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                if (!response.success) {
                    $('#view_collateral_summary').removeClass('alert-success').addClass('alert-danger').text(response.message || 'Could not load collateral.');
                    return;
                }

                $('#view_collateral_loan_code').val(response.loan?.code || '');
                $('#view_collateral_borrower').val(response.loan?.borrower || '');
                $('#view_collateral_summary').removeClass('alert-danger').addClass('alert-success').text(response.loan?.summary || 'Collateral recorded.');
                renderCollateralDetails(response);
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Could not load collateral details.';
                $('#view_collateral_summary').removeClass('alert-success').addClass('alert-danger').text(message);
                $('#view_non_cash_collateral').html('<div class="text-muted">No details loaded.</div>');
                $('#view_cash_security').html('<div class="text-muted">No details loaded.</div>');
                $('#view_collateral_documents').html('<div class="text-muted">No details loaded.</div>');
            }
        });
    });

    $('#collateral_mode').on('change', toggleCollateralMode);
    $('.collateral-ugx-input').on('input', function() {
        this.value = formatCollateralUgxInput(this.value);
    });

    $('#loanCollateralForm').on('submit', function(e) {
        e.preventDefault();

        const mode = $('#collateral_mode').val();
        const submitButton = $(this).find('button[type="submit"]');
        const originalHtml = submitButton.html();

        submitButton.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Saving...');

        if (mode === 'cash_security') {
            saveLoanCashSecurity(submitButton, originalHtml);
            return;
        }

        const formData = new FormData();
        formData.append('loan_id', $('#collateral_loan_id').val());
        formData.append('loan_type', $('#collateral_loan_type').val());
        formData.append('collateral_field', $('#collateral_field').val());
        formData.append('description', $('#collateral_description').val());
        formData.append('estimated_value', parseCollateralUgxInput($('#collateral_estimated_value').val()));
        formData.append('forced_sale_value', parseCollateralUgxInput($('#collateral_forced_sale_value').val()));
        formData.append('_token', '{{ csrf_token() }}');

        const documentFile = $('#collateral_document')[0]?.files?.[0];
        if (documentFile) {
            formData.append('collateral_document', documentFile);
        }

        $.ajax({
            url: '{{ route("admin.loans.collateral.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                $('#loanCollateralModal').modal('hide');
                Swal.fire('Saved', response.message || 'Collateral recorded successfully.', 'success')
                    .then(() => window.location.reload());
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                let message = xhr.responseJSON?.message || 'Failed to save collateral.';

                if (errors) {
                    const firstField = Object.keys(errors)[0];
                    if (firstField && errors[firstField] && errors[firstField][0]) {
                        message = errors[firstField][0];
                    }
                }

                Swal.fire('Not saved', message, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
});

function renderCollateralDetails(response) {
    const nonCash = response.non_cash || [];
    const cashSecurities = response.cash_securities || [];
    const documents = response.documents || [];

    if (nonCash.length) {
        $('#view_non_cash_collateral').html(nonCash.map(function(item) {
            return `
                <div class="border rounded px-2 py-1 mb-1">
                    <div class="fw-semibold">${escapeHtml(item.type || 'Collateral')}</div>
                    <div class="small text-muted" style="white-space: pre-wrap;">${escapeHtml(item.description || '')}</div>
                </div>
            `;
        }).join(''));
    } else {
        $('#view_non_cash_collateral').html('<div class="text-muted">No non-cash collateral recorded.</div>');
    }

    if (cashSecurities.length) {
        $('#view_cash_security').html(cashSecurities.map(function(item) {
            return `
                <div class="border rounded px-2 py-1 mb-1">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>UGX ${escapeHtml(item.amount_formatted || '0')}</strong>
                        <span class="badge ${item.status === 'Paid' ? 'bg-success' : (item.status === 'Pending' ? 'bg-warning text-dark' : 'bg-danger')}">${escapeHtml(item.status || '')}</span>
                    </div>
                    <div class="small text-muted">${escapeHtml(item.payment_type || '')}${item.reference ? ' / Ref: ' + escapeHtml(item.reference) : ''}</div>
                    <div class="small">${escapeHtml(item.description || '')}</div>
                </div>
            `;
        }).join(''));
    } else {
        $('#view_cash_security').html('<div class="text-muted">No cash security linked to this loan.</div>');
    }

    if (documents.length) {
        $('#view_collateral_documents').html(documents.map(function(item) {
            return `
                <div class="border rounded px-2 py-1 mb-1 d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold">${escapeHtml(item.name || 'Collateral document')}</div>
                        <div class="small text-muted">${escapeHtml(item.type || '')}${item.uploaded_at ? ' / ' + escapeHtml(item.uploaded_at) : ''}</div>
                        ${item.estimated_value_formatted ? '<div class="small"><strong>Estimated:</strong> UGX ' + escapeHtml(item.estimated_value_formatted) + '</div>' : ''}
                        ${item.forced_sale_value_formatted ? '<div class="small"><strong>FSV:</strong> UGX ' + escapeHtml(item.forced_sale_value_formatted) + '</div>' : ''}
                        ${item.description ? '<div class="small">' + escapeHtml(item.description) + '</div>' : ''}
                    </div>
                    ${item.url ? '<a class="btn btn-sm btn-outline-primary" href="' + escapeHtml(item.url) + '" target="_blank" rel="noopener"><i class="mdi mdi-open-in-new"></i> View</a>' : ''}
                </div>
            `;
        }).join(''));
    } else {
        $('#view_collateral_documents').html('<div class="text-muted">No collateral evidence uploaded.</div>');
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCollateralUgxInput(value) {
    const sanitized = String(value ?? '').replace(/[^\d.]/g, '');
    const parts = sanitized.split('.');
    const whole = parts.shift().replace(/^0+(?=\d)/, '');
    const decimal = parts.join('').slice(0, 2);
    const formattedWhole = whole ? Number(whole).toLocaleString('en-US') : '';

    return decimal || sanitized.endsWith('.')
        ? `${formattedWhole}.${decimal}`
        : formattedWhole;
}

function parseCollateralUgxInput(value) {
    return String(value ?? '').replace(/,/g, '');
}

function toggleCollateralMode() {
    const mode = $('#collateral_mode').val();
    const memberId = $('#collateral_member_id').val();

    $('#nonCashCollateralFields').toggle(mode === 'non_cash');
    $('#cashSecurityFields').toggle(mode === 'cash_security');
    $('#collateral_field, #collateral_description, #collateral_estimated_value').prop('required', mode === 'non_cash');
    $('#collateral_document').prop('required', mode === 'non_cash');
    $('#cash_security_amount').prop('required', mode === 'cash_security');

    if (mode === 'cash_security' && !memberId) {
        Swal.fire('Cash security unavailable', 'This loan has no member link on this page. Please record non-cash collateral or open the member account.', 'warning');
        $('#collateral_mode').val('non_cash');
        $('#nonCashCollateralFields').show();
        $('#cashSecurityFields').hide();
    }
}

function saveLoanCashSecurity(submitButton, originalHtml) {
    const memberId = $('#collateral_member_id').val();
    const amount = parseFloat($('#cash_security_amount').val() || '0');

    if (!memberId) {
        Swal.fire('Missing member', 'Cash security must be linked to a member.', 'error');
        submitButton.prop('disabled', false).html(originalHtml);
        return;
    }

    if (!amount || amount <= 0) {
        Swal.fire('Amount required', 'Enter a valid cash security amount.', 'error');
        submitButton.prop('disabled', false).html(originalHtml);
        return;
    }

    $.ajax({
        url: '{{ route("admin.cash-securities.store") }}',
        method: 'POST',
        data: {
            member_id: memberId,
            loan_id: $('#collateral_loan_id').val(),
            loan_type: $('#collateral_loan_type').val(),
            amount: amount,
            payment_type: $('#cash_security_payment_type').val(),
            description: $('#cash_security_description').val() || ('Cash security for loan ' + $('#collateral_loan_code').val()),
            member_phone: $('#cash_security_phone').val(),
            member_name: $('#collateral_borrower_name').val(),
            _token: '{{ csrf_token() }}'
        },
        headers: {
            'Accept': 'application/json'
        },
        success: function(response) {
            if (!response.success) {
                Swal.fire('Not saved', response.message || 'Cash security could not be recorded.', 'error');
                return;
            }

            if (response.transaction_reference) {
                Swal.fire({
                    title: 'Payment request sent',
                    text: 'Waiting for mobile money confirmation...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });
                pollLoanCashSecurityStatus(response.transaction_reference, 0);
                return;
            }

            $('#loanCollateralModal').modal('hide');
            Swal.fire('Saved', response.message || 'Cash security recorded successfully.', 'success')
                .then(() => window.location.reload());
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            let message = xhr.responseJSON?.message || 'Failed to record cash security.';

            if (errors) {
                const firstField = Object.keys(errors)[0];
                if (firstField && errors[firstField] && errors[firstField][0]) {
                    message = errors[firstField][0];
                }
            }

            Swal.fire('Not saved', message, 'error');
        },
        complete: function() {
            submitButton.prop('disabled', false).html(originalHtml);
        }
    });
}

function pollLoanCashSecurityStatus(transactionRef, attempts) {
    if (attempts > 24) {
        Swal.fire('Still pending', 'The mobile money request is still pending. Refresh later or check cash security status from the member account.', 'info')
            .then(() => window.location.reload());
        return;
    }

    $.ajax({
        url: '/admin/cash-securities/check-status/' + encodeURIComponent(transactionRef),
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.status === 'completed') {
                $('#loanCollateralModal').modal('hide');
                Swal.fire('Confirmed', response.message || 'Cash security confirmed successfully.', 'success')
                    .then(() => window.location.reload());
                return;
            }

            if (response.status === 'failed') {
                Swal.fire('Payment failed', response.message || 'The cash security mobile money payment failed.', 'error');
                return;
            }

            setTimeout(function() {
                pollLoanCashSecurityStatus(transactionRef, attempts + 1);
            }, 5000);
        },
        error: function() {
            setTimeout(function() {
                pollLoanCashSecurityStatus(transactionRef, attempts + 1);
            }, 5000);
        }
    });
}

function toggleFollowUpSmsFields() {
    if ($('#followup_sms_sent').is(':checked')) {
        $('#followup_sms_message_wrap').show();
        if (!$('#followup_sms_message').val().trim()) {
            $('#followup_sms_message').val('Dear client, please contact us about your loan repayment. Thank you.');
        }
    } else {
        $('#followup_sms_message_wrap').hide();
        $('#followup_sms_message').val('');
    }
}

function quickRepay(loanId, loanCode, dueAmount, phone) {
    $('#modal_loan_id').val(loanId);
    $('#modal_loan_code').val(loanCode);
    $('#modal_phone').val(phone);
    
    // Fetch next schedule for this loan
    $.ajax({
        url: '/admin/loans/' + loanId + '/next-schedule',
        method: 'GET',
        success: function(response) {
            if (response.success && response.schedule) {
                $('#modal_schedule_id').val(response.schedule.id);
                $('#modal_due_date').val(response.schedule.payment_date);
                $('#modal_amount').val(response.schedule.payment);
                $('#modal_expected_amount').text(new Intl.NumberFormat().format(response.schedule.payment));
            } else {
                $('#modal_schedule_id').val('');
                $('#modal_due_date').val('No pending schedule');
                $('#modal_amount').val(dueAmount || 0);
                $('#modal_expected_amount').text(new Intl.NumberFormat().format(dueAmount || 0));
            }
        },
        error: function() {
            $('#modal_schedule_id').val('');
            $('#modal_due_date').val('N/A');
            $('#modal_amount').val(dueAmount || 0);
            $('#modal_expected_amount').text(new Intl.NumberFormat().format(dueAmount || 0));
        }
    });
    
    $('#quickRepayModal').modal('show');
}

// Handle payment method change
$('#modal_payment_method').change(function() {
    if ($(this).val() === '2') { // Mobile Money (legacy: 1=cash, 2=mm, 3=bank)
        $('#modal_network_div').show();
        $('#modal_network').prop('required', true);
    } else {
        $('#modal_network_div').hide();
        $('#modal_network').prop('required', false);
    }
});

// Handle quick repayment form submission
$('#quickRepayForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = {
        loan_id: $('#modal_loan_id').val(),
        schedule_id: $('#modal_schedule_id').val(),
        amount: $('#modal_amount').val(),
        type: $('#modal_payment_method').val(),
        payment_method: $('#modal_payment_method').val(),
        network: $('#modal_network').val(),
        phone: $('#modal_phone').val(),
        details: $('#modal_notes').val(),
        platform: 'Web',
        _token: '{{ csrf_token() }}'
    };
    
    $.ajax({
        url: '{{ route("admin.loans.repayments.quick") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#quickRepayModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            var message = xhr.responseJSON?.message || 'An error occurred';
            Swal.fire('Error!', message, 'error');
        }
    });
});

// Auto-detect network from phone number
$('#modal_phone').on('input', function() {
    if ($('#modal_payment_method').val() === '2') {
        var phone = $(this).val().replace(/[^0-9]/g, '');
        
        if (phone.length >= 9) {
            if (phone.match(/^256(77|78|76)/)) {
                $('#modal_network').val('MTN');
            } else if (phone.match(/^256(70|75|74|71)/)) {
                $('#modal_network').val('AIRTEL');
            }
        }
    }
});

// Show reschedule modal
function showRescheduleModal(loanId, loanCode, borrowerName, daysLate) {
    $('#reschedule_loan_id').val(loanId);
    $('#reschedule_loan_code').val(loanCode);
    $('#reschedule_borrower_name').val(borrowerName);
    $('#reschedule_days_late').val(daysLate + ' days overdue');
    $('#reschedule_days').val(daysLate); // Suggest postponing by same number of days
    $('#reschedule_reason').val('');
    $('#reschedule_waive_fees').val('0');
    $('#rescheduleModal').modal('show');
}

// Handle reschedule form submission
$('#rescheduleForm').on('submit', function(e) {
    e.preventDefault();
    
    var loanId = $('#reschedule_loan_id').val();
    var days = parseInt($('#reschedule_days').val());
    var reason = $('#reschedule_reason').val();
    var waiveFees = $('#reschedule_waive_fees').val();
    
    if (!days || days < 1) {
        Swal.fire('Error!', 'Please enter valid number of days', 'error');
        return;
    }
    
    if (!reason || reason.trim().length < 10) {
        Swal.fire('Error!', 'Please provide a detailed reason (at least 10 characters)', 'error');
        return;
    }
    
    // Confirm before rescheduling
    Swal.fire({
        title: 'Confirm Rescheduling',
        html: `
            <div class="text-start">
                <p><strong>Loan:</strong> ${$('#reschedule_loan_code').val()}</p>
                <p><strong>Postpone by:</strong> ${days} days</p>
                <p><strong>Waive late fees:</strong> ${waiveFees == '1' ? 'Yes' : 'No'}</p>
                <hr>
                <p class="text-muted">This will update all pending payment schedules for this loan.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Reschedule',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Rescheduling loan payments...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: '/admin/loans/' + loanId + '/reschedule',
                method: 'POST',
                timeout: 60000,
                data: {
                    days: days,
                    reason: reason,
                    waive_fees: waiveFees,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#rescheduleModal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: response.message || 'Loan payments rescheduled successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    var message = xhr.statusText === 'timeout'
                        ? 'The reschedule request timed out after 60 seconds. Please refresh and check whether the schedules were updated before trying again.'
                        : (xhr.responseJSON?.message || 'Failed to reschedule loan payments');
                    Swal.fire('Error!', message, 'error');
                }
            });
        }
    });
});

// Confirm and stop loan
function confirmStopLoan(loanId, borrowerName, loanCode, disbursementDate, duplicateCount) {
    Swal.fire({
        title: 'Stop This Loan?',
        html: `
            <div class="text-start">
                <p><strong>Borrower:</strong> ${borrowerName}</p>
                <p><strong>Loan Code:</strong> ${loanCode}</p>
                <p><strong>Disbursed:</strong> ${disbursementDate}</p>
                <p class="text-warning"><strong>⚠️ Client has ${duplicateCount} loans from 2025</strong></p>
                <hr>
                <p class="text-danger"><strong>Warning:</strong> This will mark the loan as STOPPED.</p>
                <p class="text-muted">The loan will no longer appear in active loans and no further payments can be made.</p>
                <p class="text-muted"><small>Note: This action can be reversed by updating the loan status in the database if needed.</small></p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Stop This Loan',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Enter reason for stopping this loan (required)',
        inputAttributes: {
            'aria-label': 'Enter reason for stopping',
            'rows': 3
        },
        inputValidator: (value) => {
            if (!value || value.trim().length < 10) {
                return 'Please provide a detailed reason (at least 10 characters)'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Stopping loan...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: '/admin/loans/' + loanId + '/stop',
                method: 'POST',
                data: {
                    reason: result.value,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message || 'Loan stopped successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Failed to stop loan';
                    Swal.fire('Error!', message, 'error');
                }
            });
        }
    });
}

function confirmRevertRestructure(loanId, loanCode, originalLoanCode, borrowerName) {
    Swal.fire({
        title: 'Revert Restructure?',
        html: `
            <div class="text-start">
                <p><strong>Borrower:</strong> ${borrowerName}</p>
                <p><strong>Restructured loan:</strong> ${loanCode}</p>
                <p><strong>Original loan:</strong> ${originalLoanCode}</p>
                <hr>
                <p class="text-muted">This will delete the generated restructure loan and its unpaid schedules, then restore the original loan to active status.</p>
                <p class="text-danger"><strong>Blocked automatically:</strong> loans with repayments, paid schedules, disbursements, collateral, follow-ups, charges, or posted accounting entries.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59f00',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Revert',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Processing...',
            text: 'Reverting loan restructure...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("admin.loans.revert-restructure", ":loan") }}'.replace(':loan', loanId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire('Reverted', response.message || 'Loan restructure reverted successfully.', 'success').then(() => {
                    window.location.href = response.redirect || '{{ route("admin.loans.active") }}';
                });
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to revert loan restructure.';
                Swal.fire('Not reverted', message, 'error');
            }
        });
    });
}

// Show restructure modal (placeholder for future implementation)
function showRestructureModal(loanId, loanCode) {
    Swal.fire({
        title: 'Loan Restructuring',
        html: `
            <div class="text-start">
                <p><strong>Loan:</strong> ${loanCode}</p>
                <p class="text-muted">Loan restructuring allows you to modify the loan terms including:</p>
                <ul class="text-start">
                    <li>Change interest rate</li>
                    <li>Extend loan period</li>
                    <li>Adjust installment amounts</li>
                    <li>Consolidate with other loans</li>
                </ul>
                <p class="text-info"><i class="mdi mdi-information me-2"></i>This feature requires additional approval and documentation.</p>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Contact Admin',
        showCancelButton: true
    });
}
</script>
@endpush
@endsection
