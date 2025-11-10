@extends('layouts.admin')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">System Settings Dashboard</h3>
                        <h6 class="font-weight-normal mb-0">Manage all system configurations and settings from this central location</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Row -->
        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-2">Organization Settings</h4>
                                <p class="text-muted">Manage agencies, branches, and company information</p>
                            </div>
                            <div class="icon-lg text-primary">
                                <i class="mdi mdi-office-building"></i>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.agencies') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="mdi mdi-domain"></i><br>Agencies
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.branches') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="mdi mdi-source-branch"></i><br>Branches
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.company-info') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="mdi mdi-information"></i><br>Company
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-2">Product Settings</h4>
                                <p class="text-muted">Configure loan products, savings, and fees</p>
                            </div>
                            <div class="icon-lg text-success">
                                <i class="mdi mdi-package-variant"></i>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.loan-products') }}" class="btn btn-outline-success btn-sm">
                                    <i class="mdi mdi-cash"></i><br>Loans
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.savings-products') }}" class="btn btn-outline-success btn-sm">
                                    <i class="mdi mdi-piggy-bank"></i><br>Savings
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.fees-products') }}" class="btn btn-outline-success btn-sm">
                                    <i class="mdi mdi-calculator"></i><br>Fees
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-2">Account Settings</h4>
                                <p class="text-muted">Manage system accounts and chart of accounts</p>
                            </div>
                            <div class="icon-lg text-info">
                                <i class="mdi mdi-bank"></i>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.system-accounts') }}" class="btn btn-outline-info btn-sm">
                                    <i class="mdi mdi-bank-outline"></i><br>System
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.chart-accounts') }}" class="btn btn-outline-info btn-sm">
                                    <i class="mdi mdi-chart-line"></i><br>Chart
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.account-types') }}" class="btn btn-outline-info btn-sm">
                                    <i class="mdi mdi-format-list-bulleted-type"></i><br>Types
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-2">Security & Codes</h4>
                                <p class="text-muted">Configure security codes and audit settings</p>
                            </div>
                            <div class="icon-lg text-warning">
                                <i class="mdi mdi-security"></i>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.security-codes') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="mdi mdi-key-variant"></i><br>Security
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.transaction-codes') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="mdi mdi-code-tags"></i><br>Trans
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a href="{{ route('admin.settings.audit-trail') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="mdi mdi-history"></i><br>Audit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Third Row -->
        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-2">System Configuration</h4>
                                <p class="text-muted">Configure email, SMS, and notifications</p>
                            </div>
                            <div class="icon-lg text-danger">
                                <i class="mdi mdi-wrench"></i>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.general-config') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="mdi mdi-settings"></i><br>General
                                </a>
                            </div>
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.email-config') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="mdi mdi-email"></i><br>Email
                                </a>
                            </div>
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.sms-config') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="mdi mdi-message-text"></i><br>SMS
                                </a>
                            </div>
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.notification-config') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="mdi mdi-bell"></i><br>Notify
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-2">Maintenance & Tools</h4>
                                <p class="text-muted">Backup, database maintenance, and system logs</p>
                            </div>
                            <div class="icon-lg text-secondary">
                                <i class="mdi mdi-tools"></i>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.backup') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-backup-restore"></i><br>Backup
                                </a>
                            </div>
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.database-maintenance') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-database"></i><br>DB
                                </a>
                            </div>
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.system-logs') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-file-document"></i><br>Logs
                                </a>
                            </div>
                            <div class="col-3 text-center">
                                <a href="{{ route('admin.settings.data-import') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-import"></i><br>Import
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status Card -->
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Recent System Activity</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-md text-success me-2">
                                        <i class="mdi mdi-check-circle"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1">System Status</p>
                                        <h6 class="mb-0 text-success">Online</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-md text-info me-2">
                                        <i class="mdi mdi-clock"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1">Last Backup</p>
                                        <h6 class="mb-0">{{ now()->format('Y-m-d H:i') }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-md text-warning me-2">
                                        <i class="mdi mdi-database"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1">Database</p>
                                        <h6 class="mb-0 text-warning">Maintenance Due</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-md text-primary me-2">
                                        <i class="mdi mdi-account-multiple"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1">Active Users</p>
                                        <h6 class="mb-0">{{ $stats['total_agencies'] ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Add any dashboard-specific JavaScript here
        console.log('Settings Dashboard Loaded');
    });
</script>
@endsection