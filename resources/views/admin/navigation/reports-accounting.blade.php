@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
@endphp

@include('admin.partials.page-header', [
    'title' => 'Reports & Accounting',
    'subtitle' => 'Operational reports, UMRA compliance, and general ledger reports.',
    'icon' => 'mdi mdi-file-chart-outline',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

<div class="row">
    @if($can('generate-loan-reports'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Loan Reports</h4>
                        <p class="text-muted">Loan, repayment, payment, and charge reports</p>
                    </div>
                    <div class="icon-lg text-secondary"><i class="mdi mdi-file-chart-outline"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.reports.pending-loans') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-file-clock"></i><span>Pending Loans</span></a>
                    <a href="{{ route('admin.reports.disbursed-loans') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-file-check"></i><span>Disbursed Loans</span></a>
                    <a href="{{ route('admin.reports.rejected-loans') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-file-remove"></i><span>Rejected Loans</span></a>
                    <a href="{{ route('admin.reports.loans-due') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-calendar-alert"></i><span>Loans Due</span></a>
                    <a href="{{ route('admin.reports.paid-loans') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-cash-check"></i><span>Paid Loans</span></a>
                    <a href="{{ route('admin.reports.loan-repayments') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-cash-sync"></i><span>Loan Repayments</span></a>
                    <a href="{{ route('admin.reports.payment-transactions') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-swap-horizontal"></i><span>Payment Transactions</span></a>
                    <a href="{{ route('admin.reports.loan-interest') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-percent"></i><span>Loan Interest</span></a>
                    <a href="{{ route('admin.reports.cash-securities') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-shield-lock"></i><span>Cash Securities</span></a>
                    <a href="{{ route('admin.reports.loan-charges') }}" class="btn btn-outline-secondary btn-sm module-action-btn"><i class="mdi mdi-receipt"></i><span>Loan Charges</span></a>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($can('view-accounting-reports'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Accounting & GL</h4>
                        <p class="text-muted">Journal, chart, and financial statement views</p>
                    </div>
                    <div class="icon-lg text-primary"><i class="mdi mdi-book-open-variant"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.accounting.journal-entries') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-book-open-variant"></i><span>Journal Entries</span></a>
                    <a href="{{ route('admin.accounting.chart-of-accounts') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-chart-tree"></i><span>Chart of Accounts</span></a>
                    <a href="{{ route('admin.accounting.trial-balance') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-scale-balance"></i><span>Trial Balance</span></a>
                    <a href="{{ route('admin.accounting.balance-sheet') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-file-table"></i><span>Balance Sheet</span></a>
                    <a href="{{ route('admin.accounting.income-statement') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-finance"></i><span>Income Statement</span></a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($can('view-umra-reports'))
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">UMRA Compliance</h4>
                        <p class="text-muted">Regulatory reporting and prudential pack exports</p>
                    </div>
                    <div class="icon-lg text-success"><i class="mdi mdi-clipboard-check-outline"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-view-dashboard"></i><span>Portfolio Indicators</span></a>
                    <a href="{{ route('admin.umra.loan-preview') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-file-eye"></i><span>Loan Preview</span></a>
                    <a href="{{ route('admin.umra.loan-records') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-file-document"></i><span>Loan Records</span></a>
                    <a href="{{ route('admin.umra.collateral-register') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-shield-home"></i><span>Collateral Register</span></a>
                    <a href="{{ route('admin.umra.schedule3') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-chart-timeline"></i><span>Schedule 3</span></a>
                    <a href="{{ route('admin.umra.prudential-pack') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-package-variant"></i><span>Prudential Pack</span></a>
                    <a href="{{ route('admin.umra.export-preview') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-export"></i><span>Export Preview</span></a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
