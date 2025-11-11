@extends('layouts.admin')

@section('title', 'Security Codes')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Security Codes</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item active">Security Codes</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-bold">Manage Security Codes</h3>
                        <p class="text-muted mb-0">Configure security codes for system operations</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Codes Content -->
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="mdi mdi-information mr-2"></i>
                            <strong>Coming Soon!</strong> Security codes management feature is under development.
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">System Security</h5>
                                        <p class="card-text">Configure authentication codes, verification pins, and security tokens.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="mdi mdi-check text-success"></i> Two-Factor Authentication</li>
                                            <li><i class="mdi mdi-check text-success"></i> Transaction PINs</li>
                                            <li><i class="mdi mdi-check text-success"></i> Emergency Access Codes</li>
                                            <li><i class="mdi mdi-check text-success"></i> API Security Keys</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Access Control</h5>
                                        <p class="card-text">Manage access codes for sensitive operations and restricted areas.</p>
                                        <ul class="list-unstyled">
                                            <li><i class="mdi mdi-check text-success"></i> Disbursement Authorization Codes</li>
                                            <li><i class="mdi mdi-check text-success"></i> Vault Access Codes</li>
                                            <li><i class="mdi mdi-check text-success"></i> Report Access Codes</li>
                                            <li><i class="mdi mdi-check text-success"></i> Admin Override Codes</li>
                                        </ul>
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
