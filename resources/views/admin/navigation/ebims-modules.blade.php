@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
    $modulePermissions = config('ebmis_permissions.module_permissions', []);
    $canModule = fn (string $module): bool => collect($modulePermissions[$module] ?? [])
        ->contains(fn (string $permission): bool => $can($permission));
    $canManageStaffPaymentRollout = $user->canManageStaffPaymentRollout();
@endphp

<div class="row">
    <div class="col-md-12 grid-margin">
        <h3 class="font-weight-bold">EBIMS Modules</h3>
        <h6 class="font-weight-normal mb-0">Choose a module workspace. Only modules allowed by your role are shown.</h6>
    </div>
</div>

<div class="row">
    @if($canModule('clients'))
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

    @if($canModule('loan-portfolio'))
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

    @if($canModule('collections'))
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

    @if($canModule('reports-accounting'))
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

    @if($canModule('expenditures') || $canManageStaffPaymentRollout)
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2"><i class="mdi mdi-cash-minus text-danger"></i> Expenses & Staff Payments</h4>
                <p class="text-muted">Expense entry, approvals, payments, and staff rollout access.</p>
                <a href="{{ route('admin.modules.expenditures') }}" class="btn btn-outline-danger btn-sm">
                    <i class="mdi mdi-open-in-new"></i> Open Module
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($canModule('investments'))
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
