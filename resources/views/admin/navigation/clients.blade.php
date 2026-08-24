@extends('layouts.admin')

@section('content')
@include('admin.navigation.partials.module-styles')

@php
    $user = auth()->user();
    $can = fn (string $permission): bool => $user->isSuperAdmin() || $user->can($permission);
@endphp

@include('admin.partials.page-header', [
    'title' => 'Clients Module',
    'subtitle' => 'Client records, approvals, groups, and SMS communication.',
    'icon' => 'mdi mdi-account-multiple',
    'backFallback' => route('admin.modules.dashboard'),
    'backLabel' => 'Back to EBIMS Modules',
])

<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Client Records</h4>
                        <p class="text-muted">Open filtered client lists and registration tools</p>
                    </div>
                    <div class="icon-lg text-primary"><i class="mdi mdi-account-multiple"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    @if($can('view-client-details'))
                    <a href="{{ route('admin.members.index') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-format-list-bulleted"></i><span>All Clients</span></a>
                    <a href="{{ route('admin.members.index') }}?status=approved" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account-check"></i><span>Approved Clients</span></a>
                    <a href="{{ route('admin.members.pending') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account-clock"></i><span>Pending Approval</span></a>
                    <a href="{{ route('admin.members.index') }}?status=suspended" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account-off"></i><span>Suspended Clients</span></a>
                    <a href="{{ route('admin.members.index') }}?member_type=1" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account"></i><span>Individual Clients</span></a>
                    <a href="{{ route('admin.members.index') }}?member_type=3" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-domain"></i><span>Corporate Clients</span></a>
                    @endif
                    @if($can('add-client'))
                    <a href="{{ route('admin.members.create') }}" class="btn btn-outline-primary btn-sm module-action-btn"><i class="mdi mdi-account-plus"></i><span>Add Client</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($can('manage-group-members') || $can('send-sms-notifications'))
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Groups & Communication</h4>
                        <p class="text-muted">Group client workflows and outbound SMS</p>
                    </div>
                    <div class="icon-lg text-success"><i class="mdi mdi-account-group"></i></div>
                </div>
                <div class="module-action-grid mt-3">
                    @if($can('manage-group-members'))
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-account-group"></i><span>All Groups</span></a>
                    <a href="{{ route('admin.groups.create') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-account-multiple-plus"></i><span>Create Group</span></a>
                    @endif
                    @if($can('view-client-details'))
                    <a href="{{ route('admin.members.index') }}?member_type=2" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-account-multiple"></i><span>Group Clients</span></a>
                    @endif
                    @if($can('send-sms-notifications'))
                    <a href="{{ route('admin.bulk-sms.create') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-message-plus"></i><span>Send Bulk SMS</span></a>
                    <a href="{{ route('admin.bulk-sms.index') }}" class="btn btn-outline-success btn-sm module-action-btn"><i class="mdi mdi-message-text"></i><span>SMS Records</span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
