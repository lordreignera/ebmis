@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
@endphp

@include('admin.partials.page-header', [
    'title' => 'Payments & Collections',
    'subtitle' => 'Repayments, fees, cash securities, and savings operations.',
    'icon' => 'mdi mdi-wallet',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Loan Collections</h4>
                        <p class="text-muted">Repayment and collection work queues</p>
                    </div>
                    <div class="icon-lg text-warning"><i class="mdi mdi-cash-sync"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    @if($can('view-repayment-history'))
                    <a href="{{ route('admin.repayments.index') }}?type=personal" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-cash-refund"></i><span>Personal Repayments</span></a>
                    <a href="{{ route('admin.repayments.index') }}?type=group" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-account-cash"></i><span>Group Repayments</span></a>
                    <a href="{{ route('admin.repayments.pending') }}" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-clock-alert"></i><span>Pending Repayments</span></a>
                    <a href="{{ route('admin.repayments.history') }}" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-history"></i><span>Repayment History</span></a>
                    @endif
                    @if($can('manage-late-fees'))
                    <a href="{{ route('admin.late-fees.index') }}" class="btn btn-outline-warning btn-sm module-action-btn"><i class="mdi mdi-timer-alert"></i><span>Late Fees</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Client Payments</h4>
                        <p class="text-muted">Fees, cash securities, and savings workflows</p>
                    </div>
                    <div class="icon-lg text-success"><i class="mdi mdi-wallet"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    @if($can('manage-fees'))
                    <a href="{{ route('admin.fees.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-receipt"></i><span>All Fees</span></a>
                    <a href="{{ route('admin.fees.create') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-cash-plus"></i><span>Record Fee</span></a>
                    @endif
                    @if($can('manage-cash-securities'))
                    <a href="{{ route('admin.cash-securities.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-shield-lock"></i><span>Cash Securities</span></a>
                    <a href="{{ route('admin.cash-securities.create') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-shield-plus"></i><span>Add Security</span></a>
                    @endif
                    @if($can('manage-savings-accounts'))
                    <a href="{{ route('admin.savings.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-piggy-bank"></i><span>Savings Accounts</span></a>
                    <a href="{{ route('admin.savings.create') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-plus"></i><span>New Savings</span></a>
                    <a href="{{ route('admin.savings.pending') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-clock-outline"></i><span>Pending Savings</span></a>
                    <a href="{{ route('admin.savings.approved') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-check-circle"></i><span>Approved Savings</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
