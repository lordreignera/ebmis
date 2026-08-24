@extends('layouts.admin')

@section('content')
<style>
    .settings-action-grid {
        display: grid;
        gap: 0.65rem;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    }

    .settings-action-btn {
        align-items: center;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        justify-content: center;
        min-height: 74px;
        white-space: normal;
    }

    .settings-action-btn i {
        font-size: 1.35rem;
        line-height: 1;
    }

    .settings-action-btn span {
        display: block;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.2;
    }
</style>

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
                        <div class="settings-action-grid mt-3">
                            <div>
                                <a href="{{ route('admin.settings.agencies') }}" class="btn btn-outline-primary btn-sm settings-action-btn">
                                    <i class="mdi mdi-domain"></i>
                                    <span>Agency Management</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.branches') }}" class="btn btn-outline-primary btn-sm settings-action-btn">
                                    <i class="mdi mdi-source-branch"></i>
                                    <span>Branch Management</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.field-users') }}" class="btn btn-outline-primary btn-sm settings-action-btn">
                                    <i class="mdi mdi-account-hard-hat"></i>
                                    <span>Field Users</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.company-info') }}" class="btn btn-outline-primary btn-sm settings-action-btn">
                                    <i class="mdi mdi-information"></i>
                                    <span>Company Information</span>
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
                        <div class="settings-action-grid mt-3">
                            <div>
                                <a href="{{ route('admin.settings.loan-products') }}" class="btn btn-outline-success btn-sm settings-action-btn">
                                    <i class="mdi mdi-cash"></i>
                                    <span>Loan Products</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.school-loan-products') }}" class="btn btn-outline-success btn-sm settings-action-btn">
                                    <i class="mdi mdi-school"></i>
                                    <span>School Loan Products</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.savings-products') }}" class="btn btn-outline-success btn-sm settings-action-btn">
                                    <i class="mdi mdi-piggy-bank"></i>
                                    <span>Savings Products</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.fees-products') }}" class="btn btn-outline-success btn-sm settings-action-btn">
                                    <i class="mdi mdi-calculator"></i>
                                    <span>Fees & Products</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.product-categories') }}" class="btn btn-outline-success btn-sm settings-action-btn">
                                    <i class="mdi mdi-shape-outline"></i>
                                    <span>Product Categories</span>
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
                        <div class="settings-action-grid mt-3">
                            <div>
                                <a href="{{ route('admin.settings.system-accounts') }}" class="btn btn-outline-info btn-sm settings-action-btn">
                                    <i class="mdi mdi-chart-line"></i>
                                    <span>System Accounts / Chart of Accounts</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.account-types') }}" class="btn btn-outline-info btn-sm settings-action-btn">
                                    <i class="mdi mdi-format-list-bulleted-type"></i>
                                    <span>System Account Types</span>
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
                        <div class="settings-action-grid mt-3">
                            <div>
                                <a href="{{ route('admin.settings.security-codes') }}" class="btn btn-outline-warning btn-sm settings-action-btn">
                                    <i class="mdi mdi-key-variant"></i>
                                    <span>Security Codes</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.transaction-codes') }}" class="btn btn-outline-warning btn-sm settings-action-btn">
                                    <i class="mdi mdi-code-tags"></i>
                                    <span>Transaction Codes</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.audit-trail') }}" class="btn btn-outline-warning btn-sm settings-action-btn">
                                    <i class="mdi mdi-history"></i>
                                    <span>Audit Trail Settings</span>
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
                        <div class="settings-action-grid mt-3">
                            <div>
                                <a href="{{ route('admin.settings.general-config') }}" class="btn btn-outline-danger btn-sm settings-action-btn">
                                    <i class="mdi mdi-settings"></i>
                                    <span>General Settings</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.email-config') }}" class="btn btn-outline-danger btn-sm settings-action-btn">
                                    <i class="mdi mdi-email"></i>
                                    <span>Email Configuration</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.sms-config') }}" class="btn btn-outline-danger btn-sm settings-action-btn">
                                    <i class="mdi mdi-message-text"></i>
                                    <span>SMS Configuration</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.notification-config') }}" class="btn btn-outline-danger btn-sm settings-action-btn">
                                    <i class="mdi mdi-bell"></i>
                                    <span>Notification Settings</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.loan-policy-controls') }}" class="btn btn-outline-danger btn-sm settings-action-btn">
                                    <i class="mdi mdi-tune"></i>
                                    <span>Loan Policy Controls</span>
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
                        <div class="settings-action-grid mt-3">
                            <div>
                                <a href="{{ route('admin.settings.backup') }}" class="btn btn-outline-secondary btn-sm settings-action-btn">
                                    <i class="mdi mdi-backup-restore"></i>
                                    <span>Backup & Restore</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.database-maintenance') }}" class="btn btn-outline-secondary btn-sm settings-action-btn">
                                    <i class="mdi mdi-database"></i>
                                    <span>Database Maintenance</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.system-logs') }}" class="btn btn-outline-secondary btn-sm settings-action-btn">
                                    <i class="mdi mdi-file-document"></i>
                                    <span>System Logs</span>
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.settings.data-import') }}" class="btn btn-outline-secondary btn-sm settings-action-btn">
                                    <i class="mdi mdi-import"></i>
                                    <span>Data Import/Export</span>
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Add any dashboard-specific JavaScript here
        console.log('Settings Dashboard Loaded');
    });
</script>
@endsection
