@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-account-circle"></i>
                </span>
                My Profile
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Password Change Reminder for Imported Users -->
@if(Auth::user()->email && str_ends_with(Auth::user()->email, '@ebims.local'))
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="mdi mdi-shield-alert me-3" style="font-size: 2rem;"></i>
                <div>
                    <strong><i class="mdi mdi-lock-alert"></i> Security Notice</strong>
                    <p class="mb-0">You are using a temporary password. Please change your password below to secure your account.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="mdi mdi-account-edit text-primary me-2"></i>
                    Profile Information
                </h4>
                <p class="card-description">Update your account's profile information and email address.</p>
                
                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                    @livewire('profile.update-profile-information-form')
                @endif
            </div>
        </div>
    </div>

    <!-- Update Password -->
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="mdi mdi-lock-reset text-warning me-2"></i>
                    Update Password
                </h4>
                <p class="card-description">Ensure your account is using a long, random password to stay secure.</p>
                
                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                    @livewire('profile.update-password-form')
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Two Factor Authentication -->
    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="mdi mdi-shield-check text-success me-2"></i>
                    Two Factor Authentication
                </h4>
                <p class="card-description">Add additional security to your account using two factor authentication.</p>
                
                @livewire('profile.two-factor-authentication-form')
            </div>
        </div>
    </div>
    @endif

    <!-- Browser Sessions -->
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="mdi mdi-monitor-multiple text-info me-2"></i>
                    Browser Sessions
                </h4>
                <p class="card-description">Manage and logout your active sessions on other browsers and devices.</p>
                
                @livewire('profile.logout-other-browser-sessions-form')
            </div>
        </div>
    </div>
</div>

<!-- Account Roles & Permissions (Read-Only) -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="mdi mdi-account-key text-danger me-2"></i>
                    Account Roles & Permissions
                </h4>
                <p class="card-description text-muted">Your account roles and permissions are managed by system administrators and cannot be changed here.</p>
                
                <div class="table-responsive mt-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(Auth::user()->roles as $role)
                            <tr>
                                <td>
                                    <span class="badge badge-gradient-primary">
                                        <i class="mdi mdi-shield-account"></i> {{ $role->name }}
                                    </span>
                                </td>
                                <td>
                                    @if($role->name === 'Super Administrator')
                                        Full system access including all modules, settings, and user management
                                    @elseif($role->name === 'Branch Manager')
                                        Access to EBIMS modules: Members, Loans, Groups, Reports, and branch operations
                                    @elseif($role->name === 'School Administrator')
                                        School management and administrative functions
                                    @else
                                        {{ $role->name }} role permissions
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <i class="mdi mdi-check-circle"></i> Active
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No roles assigned</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(Auth::user()->branch_id)
                <div class="mt-4">
                    <h5><i class="mdi mdi-office-building text-primary"></i> Branch Assignment</h5>
                    <div class="alert alert-info">
                        <strong>Branch ID:</strong> {{ Auth::user()->branch_id }}<br>
                        <small class="text-muted">Your account is assigned to this branch. Contact your administrator to change branch assignments.</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Match admin theme colors */
    .card {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .card-title {
        font-weight: 600;
        color: #2d3748;
        font-size: 1.1rem;
    }
    
    .card-description {
        color: #718096;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
    
    .alert {
        border-radius: 8px;
        border-left: 4px solid;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        border-left-color: #ffc107;
        color: #856404;
    }
    
    .badge {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
    }
</style>
@endsection
