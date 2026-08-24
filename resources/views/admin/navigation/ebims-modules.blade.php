@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
    $canAny = fn (array $permissions): bool => collect($permissions)->contains(fn (string $permission): bool => $can($permission));
@endphp

<div class="row">
    <div class="col-md-12 grid-margin">
        <h3 class="font-weight-bold">EBIMS Modules</h3>
        <h6 class="font-weight-normal mb-0">Choose a module workspace. Only modules allowed by your role are shown.</h6>
    </div>
</div>

<div class="row">
    @if($canAny(['view-client-details', 'add-client', 'send-sms-notifications', 'manage-group-members']))
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2"><i class="mdi mdi-account-multiple text-primary"></i> Clients Module</h4>
                <p class="text-muted">Client lists, approvals, groups, and communication.</p>
                <a href="{{ route('admin.modules.clients') }}" class="btn btn-outline-primary btn-sm">
                    <i class="mdi mdi-open-in-new"></i> Open Module
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($canAny(['create-loan-application', 'manage-client-applications', 'manage-loans', 'view-disbursements', 'view-active-loans', 'view-repayment-history', 'manage-late-fees', 'generate-loan-reports']))
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2"><i class="mdi mdi-briefcase text-warning"></i> Loan Portfolio</h4>
                <p class="text-muted">Loans, approvals, disbursements, and portfolio views.</p>
                <a href="{{ route('admin.modules.loan-portfolio') }}" class="btn btn-outline-warning btn-sm">
                    <i class="mdi mdi-open-in-new"></i> Open Module
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($canAny(['view-repayment-history', 'view-active-loans', 'manage-late-fees', 'manage-fees', 'manage-cash-securities', 'manage-savings-accounts']))
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2"><i class="mdi mdi-wallet text-success"></i> Payments & Collections</h4>
                <p class="text-muted">Repayments, fees, securities, and savings.</p>
                <a href="{{ route('admin.modules.collections') }}" class="btn btn-outline-success btn-sm">
                    <i class="mdi mdi-open-in-new"></i> Open Module
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($canAny(['generate-loan-reports', 'view-accounting-reports', 'view-umra-reports']))
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2"><i class="mdi mdi-file-chart-outline text-secondary"></i> Reports & Accounting</h4>
                <p class="text-muted">Reports, UMRA compliance, and accounting.</p>
                <a href="{{ route('admin.modules.reports-accounting') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-open-in-new"></i> Open Module
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($can('manage-investments'))
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2"><i class="mdi mdi-trending-up text-info"></i> Investments Module</h4>
                <p class="text-muted">Investor records and investment portfolio.</p>
                <a href="{{ route('admin.modules.investments') }}" class="btn btn-outline-info btn-sm">
                    <i class="mdi mdi-open-in-new"></i> Open Module
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
