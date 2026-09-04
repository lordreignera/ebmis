@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
    $canManageStaffPaymentRollout = $user->canManageStaffPaymentRollout();
@endphp

@include('admin.partials.page-header', [
    'title' => 'Expenses & Staff Payments',
    'subtitle' => 'Expense records, approvals, payment processing, and staff payout rollout.',
    'icon' => 'mdi mdi-cash-minus',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

<div class="row">
    @if($can('manage-expenditures'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Expenses</h4>
                        <p class="text-muted">Record and manage organization expenditure payments</p>
                    </div>
                    <div class="icon-lg text-danger"><i class="mdi mdi-cash-minus"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-danger btn-sm module-action-btn"><i class="mdi mdi-format-list-bulleted"></i><span>All Expenses</span></a>
                    <a href="{{ route('admin.expenditures.create') }}" class="btn btn-outline-danger btn-sm module-action-btn"><i class="mdi mdi-plus-circle-outline"></i><span>Add Expense</span></a>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canManageStaffPaymentRollout)
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Staff Payment Rollout</h4>
                        <p class="text-muted">Generate, review, approve, and pay staff performance payouts</p>
                    </div>
                    <div class="icon-lg text-dark"><i class="mdi mdi-account-cash-outline"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.expenditures.rollout') }}" class="btn btn-outline-dark btn-sm module-action-btn"><i class="mdi mdi-account-cash-outline"></i><span>Open Rollout</span></a>
                    @if($can('manage-expenditures'))
                    <a href="{{ route('admin.expenditures.index') }}?type=performance_payout" class="btn btn-outline-dark btn-sm module-action-btn"><i class="mdi mdi-account-clock-outline"></i><span>Staff Payouts</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
