@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@include('admin.partials.page-header', [
    'title' => 'Investments Module',
    'subtitle' => 'Investor records, investment portfolio, and investment status views.',
    'icon' => 'mdi mdi-trending-up',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Investment Workflows</h4>
                        <p class="text-muted">Available only to roles with investment access</p>
                    </div>
                    <div class="icon-lg text-info"><i class="mdi mdi-trending-up"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-view-dashboard"></i><span>Investment Dashboard</span></a>
                    <a href="{{ route('admin.investments.investors') }}" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-account-cash"></i><span>All Investors</span></a>
                    <a href="{{ route('admin.investments.create-investor') }}" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-account-plus"></i><span>Add Investor</span></a>
                    <a href="{{ route('admin.investments.index') }}?status=active" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-check-circle"></i><span>Active Investments</span></a>
                    <a href="{{ route('admin.investments.index') }}?status=pending" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-clock-outline"></i><span>Pending Investments</span></a>
                    <a href="{{ route('admin.investments.index') }}?status=matured" class="btn btn-outline-info btn-sm module-action-btn"><i class="mdi mdi-calendar-check"></i><span>Matured Investments</span></a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
