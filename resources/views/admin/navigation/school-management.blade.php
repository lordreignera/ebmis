@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@include('admin.partials.page-header', [
    'title' => 'School Management',
    'subtitle' => 'Open school administration, loans, advances, payroll, and reports from one place.',
    'icon' => 'mdi mdi-school',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

@php
    $loanTypes = [
        'school' => ['title' => 'School Loans', 'icon' => 'mdi-bank', 'class' => 'primary'],
        'student' => ['title' => 'Student Loans', 'icon' => 'mdi-account-school', 'class' => 'success'],
        'staff' => ['title' => 'Staff Loans', 'icon' => 'mdi-account-tie', 'class' => 'info'],
    ];
@endphp

<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Schools</h4>
                        <p class="text-muted">Manage registered schools and approval status</p>
                    </div>
                    <div class="icon-lg text-primary">
                        <i class="mdi mdi-school"></i>
                    </div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-primary btn-sm module-action-btn">
                        <i class="mdi mdi-view-list"></i>
                        <span>Schools Overview</span>
                    </a>
                    <a href="{{ route('admin.schools.create') }}" class="btn btn-outline-primary btn-sm module-action-btn">
                        <i class="mdi mdi-plus"></i>
                        <span>Add School</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @foreach($loanTypes as $type => $meta)
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">{{ $meta['title'] }}</h4>
                        <p class="text-muted">Create, approve, disburse, collect, and review {{ $type }} loan records</p>
                    </div>
                    <div class="icon-lg text-{{ $meta['class'] }}">
                        <i class="mdi {{ $meta['icon'] }}"></i>
                    </div>
                </div>
                <div class="module-action-grid mt-3">
                    <a href="{{ route('admin.school.loans.create') }}?type={{ $type }}&period=daily" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-calendar-today"></i>
                        <span>Daily Loan</span>
                    </a>
                    <a href="{{ route('admin.school.loans.create') }}?type={{ $type }}&period=weekly" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-calendar-week"></i>
                        <span>Weekly Loan</span>
                    </a>
                    <a href="{{ route('admin.school.loans.create') }}?type={{ $type }}&period=monthly" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-calendar-month"></i>
                        <span>Monthly Loan</span>
                    </a>
                    <a href="{{ route('admin.school.loans.approvals') }}?type={{ $type }}" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-check-decagram"></i>
                        <span>Pending Approvals</span>
                    </a>
                    <a href="{{ route('admin.school.loans.disbursements') }}?type={{ $type }}" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-cash-fast"></i>
                        <span>Pending Disbursements</span>
                    </a>
                    <a href="{{ route('admin.school.loans.active') }}?type={{ $type }}" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-progress-clock"></i>
                        <span>Active Loans</span>
                    </a>
                    <a href="{{ route('admin.repayments.index') }}?type={{ $type }}" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-cash-sync"></i>
                        <span>Repayments</span>
                    </a>
                    <a href="{{ route('admin.school.loans.portfolio') }}?type={{ $type }}" class="btn btn-outline-{{ $meta['class'] }} btn-sm module-action-btn">
                        <i class="mdi mdi-chart-box"></i>
                        <span>Portfolio</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">School Advances</h4>
                        <p class="text-muted">Advance workflows kept together for faster access</p>
                    </div>
                    <div class="icon-lg text-warning">
                        <i class="mdi mdi-cash-fast"></i>
                    </div>
                </div>
                <div class="module-action-grid mt-3">
                    @foreach(['Create School Advance', 'Advance Applications', 'Pending Approvals', 'Pending Disbursements', 'Advance Repayments', 'Active Advances', 'Cleared Advances'] as $label)
                    <a href="#" class="btn btn-outline-warning btn-sm module-action-btn">
                        <i class="mdi mdi-open-in-new"></i>
                        <span>{{ $label }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Payroll & Reports</h4>
                        <p class="text-muted">School payroll operations and reporting shortcuts</p>
                    </div>
                    <div class="icon-lg text-danger">
                        <i class="mdi mdi-file-chart"></i>
                    </div>
                </div>
                <div class="module-action-grid mt-3">
                    @foreach(['Process Payroll', 'Payroll Schedules', 'Teacher Payroll', 'Staff Salaries', 'Payroll by School', 'Monthly Payroll Report', 'Schools Performance Report', 'School Loans Report', 'Student Loans Report', 'Staff Loans Report'] as $label)
                    <a href="#" class="btn btn-outline-danger btn-sm module-action-btn">
                        <i class="mdi mdi-open-in-new"></i>
                        <span>{{ $label }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
