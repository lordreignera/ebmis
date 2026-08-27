@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
    $canManageSensitiveLoanOperations = $user->isSuperAdmin()
        || in_array($user->user_type, ['administrator', 'admin'], true)
        || $user->hasRole(['Administrator', 'admin']);
    $loanStates = [
        ['code' => 0, 'label' => 'Pending', 'detail' => 'Awaiting review', 'icon' => 'mdi-clock-outline', 'class' => 'pending', 'route' => 'admin.portfolio.pending'],
        ['code' => 1, 'label' => 'Approved', 'detail' => 'Awaiting disbursement', 'icon' => 'mdi-check-decagram', 'class' => 'approved', 'route' => 'admin.portfolio.approved'],
        ['code' => 2, 'label' => 'Active', 'detail' => 'Disbursed and collecting', 'icon' => 'mdi-play-circle', 'class' => 'active', 'route' => 'admin.portfolio.running'],
        ['code' => 3, 'label' => 'Closed', 'detail' => 'Fully settled', 'icon' => 'mdi-check-circle', 'class' => 'closed', 'route' => 'admin.portfolio.paid'],
        ['code' => 4, 'label' => 'Rejected', 'detail' => 'Application declined', 'icon' => 'mdi-close-octagon', 'class' => 'rejected', 'route' => 'admin.portfolio.rejected'],
        ['code' => '5 → R loan', 'label' => 'Restructured', 'detail' => 'Replacement facility', 'icon' => 'mdi-file-refresh', 'class' => 'restructured', 'route' => 'admin.portfolio.restructured'],
        ['code' => 6, 'label' => 'Stopped', 'detail' => 'Collections stopped', 'icon' => 'mdi-stop-circle', 'class' => 'stopped', 'route' => 'admin.portfolio.stopped'],
    ];
@endphp

@include('admin.partials.page-header', [
    'title' => 'Loan Portfolio Module',
    'subtitle' => 'Loan creation, approvals, disbursement queues, active loans, and portfolio views.',
    'icon' => 'mdi mdi-briefcase',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

<div class="row">
    @if($can('create-loan-application'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Create Loans</h4>
                        <p class="text-muted">Start personal and group loan applications</p>
                    </div>
                    <div class="icon-lg text-primary"><i class="mdi mdi-cash-plus"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.loans.create') }}?type=personal&period=daily" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-calendar-today"></i><span>Personal Daily</span></a>
                    <a href="{{ route('admin.loans.create') }}?type=personal&period=weekly" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-calendar-week"></i><span>Personal Weekly</span></a>
                    <a href="{{ route('admin.loans.create') }}?type=personal&period=monthly" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-calendar-month"></i><span>Personal Monthly</span></a>
                    <a href="{{ route('admin.loans.create') }}?type=group&period=daily" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account-group"></i><span>Group Daily</span></a>
                    <a href="{{ route('admin.loans.create') }}?type=group&period=weekly" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account-group-outline"></i><span>Group Weekly</span></a>
                    <a href="{{ route('admin.loans.create') }}?type=group&period=monthly" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-calendar-month-outline"></i><span>Group Monthly</span></a>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($can('manage-client-applications') || $can('manage-loans') || $can('view-disbursements'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Approvals & Disbursements</h4>
                        <p class="text-muted">Review applications and move approved loans to disbursement</p>
                    </div>
                    <div class="icon-lg text-success"><i class="mdi mdi-check-decagram"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    @if($can('manage-client-applications'))
                    <a href="{{ route('admin.client-applications.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-file-account"></i><span>Self-Applied Applications</span></a>
                    <a href="{{ route('client.apply') }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-open-in-new"></i><span>Self Application Form</span></a>
                    @endif
                    @if($can('manage-loans'))
                    <a href="{{ route('admin.loans.approvals') }}?type=personal" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-account-check"></i><span>Personal Approvals</span></a>
                    <a href="{{ route('admin.loans.approvals') }}?type=group" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-account-group"></i><span>Group Approvals</span></a>
                    <a href="{{ route('admin.loans.rejected') }}?type=personal" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-close-octagon"></i><span>Rejected Personal</span></a>
                    <a href="{{ route('admin.loans.rejected') }}?type=group" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-close-circle"></i><span>Rejected Group</span></a>
                    @endif
                    @if($can('view-disbursements'))
                    <a href="{{ route('admin.loans.disbursements.pending') }}?type=personal" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-cash-fast"></i><span>Personal Disbursements</span></a>
                    <a href="{{ route('admin.loans.disbursements.pending') }}?type=group" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-cash-multiple"></i><span>Group Disbursements</span></a>
                    <a href="{{ route('admin.disbursements.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-format-list-checks"></i><span>All Disbursements</span></a>
                    @endif
                    @if($can('manage-loans'))
                    <a href="{{ route('admin.loans.esign') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-draw"></i><span>eSign Personal Loan</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row">
    @if($can('view-active-loans'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Active Loan Work</h4>
                        <p class="text-muted">Open active loan lists and operational work queues</p>
                    </div>
                    <div class="icon-lg text-warning"><i class="mdi mdi-progress-clock"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.loans.active') }}?type=personal&per_page=20" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-account-cash"></i><span>Active Personal Loans</span></a>
                    <a href="{{ route('admin.loans.active') }}?type=group&per_page=20" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-account-group"></i><span>Active Group Loans</span></a>
                    <a href="{{ route('admin.loans.active.collections') }}?per_page=20" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-format-list-checks"></i><span>Collections Queue</span></a>
                    <a href="{{ route('admin.loans.active.risk-follow-up') }}?per_page=20" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-alert"></i><span>Risk Follow-up</span></a>
                    <a href="{{ route('admin.loans.active.security-gaps') }}?per_page=20" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-shield-alert"></i><span>Security Gaps</span></a>
                    @if($canManageSensitiveLoanOperations)
                    <a href="{{ route('admin.loans.active.operations') }}?per_page=20" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-tools"></i><span>Loan Operations</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($can('generate-loan-reports'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Loan Lifecycle & Portfolio</h4>
                        <p class="text-muted">Loan states and the linked restructured replacement facility</p>
                    </div>
                    <div class="icon-lg text-info"><i class="mdi mdi-chart-box"></i></div>
                </div>
                <div class="portfolio-state-grid mt-3">
                    @foreach($loanStates as $state)
                        <a href="{{ route($state['route']) }}" class="portfolio-state-card state-{{ $state['class'] }}">
                            <span class="portfolio-state-code">Status {{ $state['code'] }}</span>
                            <i class="mdi {{ $state['icon'] }}"></i>
                            <strong>{{ $state['label'] }}</strong>
                            <small>{{ $state['detail'] }}</small>
                        </a>
                    @endforeach
                    <a href="{{ route('admin.portfolio.overdue') }}" class="portfolio-state-card state-overdue">
                        <span class="portfolio-state-code">Derived</span>
                        <i class="mdi mdi-alarm"></i>
                        <strong>Overdue</strong>
                        <small>Active with missed dues</small>
                    </a>
                </div>
                <div class="portfolio-analysis-grid mt-3 pt-3 border-top">
                    <a href="{{ route('admin.portfolio.branch') }}" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-source-branch"></i><span>By Branch</span></a>
                    <a href="{{ route('admin.portfolio.product') }}" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-package-variant"></i><span>By Product</span></a>
                    <a href="{{ route('admin.portfolio.group') }}" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-account-multiple"></i><span>Group Loans</span></a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection
