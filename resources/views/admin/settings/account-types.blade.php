@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-tag-multiple"></i>
        </span> Account Types
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Account Types</li>
        </ul>
    </nav>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">Manage Account Types</h4>
                        <p class="text-muted mt-2">Define and manage different types of accounts in your system</p>
                    </div>
                    <button type="button" class="btn btn-gradient-primary btn-fw" data-bs-toggle="modal" data-bs-target="#addAccountTypeModal">
                        <i class="mdi mdi-plus"></i> Add Account Type
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type Name</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Static data for now -->
                            <tr>
                                <td>1</td>
                                <td class="font-weight-bold">Bank</td>
                                <td>Bank accounts and financial institutions</td>
                                <td><span class="badge badge-info">Asset</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td class="font-weight-bold">Accounts Receivable (A/R)</td>
                                <td>Money owed to the organization</td>
                                <td><span class="badge badge-info">Asset</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td class="font-weight-bold">Fixed Assets</td>
                                <td>Long-term tangible assets</td>
                                <td><span class="badge badge-info">Asset</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td class="font-weight-bold">Current Liability</td>
                                <td>Short-term obligations</td>
                                <td><span class="badge badge-warning">Liability</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td class="font-weight-bold">Other Current Assets</td>
                                <td>Short-term assets other than cash</td>
                                <td><span class="badge badge-info">Asset</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td class="font-weight-bold">Other Current Liabilities</td>
                                <td>Short-term liabilities not elsewhere classified</td>
                                <td><span class="badge badge-warning">Liability</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td class="font-weight-bold">Other</td>
                                <td>Miscellaneous account types</td>
                                <td><span class="badge badge-secondary">Other</span></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-4" role="alert">
                    <i class="mdi mdi-information"></i>
                    <strong>Note:</strong> This is a reference page showing the available account types in the system. 
                    These types are used when creating system accounts.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Type Modal -->
<div class="modal fade" id="addAccountTypeModal" tabindex="-1" aria-labelledby="addAccountTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="addAccountTypeModalLabel">Add Account Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: white;">
                <div class="alert alert-warning" role="alert">
                    <i class="mdi mdi-alert"></i>
                    Account type management functionality will be implemented in a future update.
                    Currently, account types are predefined in the system.
                </div>
            </div>
            <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
