@php
    $landingUser = auth()->user();
    $landingCan = fn (string $permission): bool => $landingUser->isSuperAdmin() || $landingUser->can($permission);
    $landingHasWorkspaceAccess = \App\Support\EbmisPermissionRegistry::userCanAccessWorkspace($landingUser);
    $landingModulePermissions = config('ebmis_permissions.module_permissions', []);
    $landingCanModule = fn (string $module): bool => $landingHasWorkspaceAccess
        && collect($landingModulePermissions[$module] ?? [])
            ->contains(fn (string $permission): bool => $landingCan($permission));
    $landingModules = [
        'clients' => ['label' => 'Clients Module', 'icon' => 'mdi-account-multiple', 'class' => 'primary', 'route' => 'admin.modules.clients'],
        'loan-portfolio' => ['label' => 'Loan Portfolio', 'icon' => 'mdi-briefcase', 'class' => 'warning', 'route' => 'admin.modules.loan-portfolio'],
        'collections' => ['label' => 'Payments & Collections', 'icon' => 'mdi-wallet', 'class' => 'success', 'route' => 'admin.modules.collections'],
        'reports-accounting' => ['label' => 'Reports & Accounting', 'icon' => 'mdi-file-chart-outline', 'class' => 'secondary', 'route' => 'admin.modules.reports-accounting'],
        'expenditures' => ['label' => 'Expenses & Staff Payments', 'icon' => 'mdi-cash-minus', 'class' => 'danger', 'route' => 'admin.modules.expenditures'],
        'investments' => ['label' => 'Investments Module', 'icon' => 'mdi-trending-up', 'class' => 'info', 'route' => 'admin.modules.investments'],
    ];
    $landingCanExpenditures = $landingHasWorkspaceAccess
        && ($landingCanModule('expenditures') || $landingUser->canManageStaffPaymentRollout());
    $landingHasModule = collect(array_keys($landingModules))->contains(
        fn (string $module): bool => $module === 'expenditures'
            ? $landingCanExpenditures
            : $landingCanModule($module)
    );
@endphp

<div class="row">
    <div class="col-12 grid-margin">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; flex: 0 0 48px;">
                        <i class="mdi mdi-view-dashboard-outline mdi-24px"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1">Welcome to EBIMS</h3>
                        <p class="text-muted mb-0">
                            Your account is active. Use the sidebar or the permitted module below to continue.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($landingHasModule)
    <div class="row">
        @foreach($landingModules as $module => $details)
            @php($moduleAllowed = $module === 'expenditures' ? $landingCanExpenditures : $landingCanModule($module))
            @if($moduleAllowed)
                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-2">
                                <i class="mdi {{ $details['icon'] }} text-{{ $details['class'] }}"></i>
                                {{ $details['label'] }}
                            </h4>
                            <p class="text-muted">Open the work assigned to your role.</p>
                            <a href="{{ route($details['route']) }}" class="btn btn-outline-{{ $details['class'] }} btn-sm">
                                <i class="mdi mdi-open-in-new"></i> Open Module
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@else
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm">
                <i class="mdi mdi-information-outline me-1"></i>
                No operational modules have been assigned to your role yet. Please contact an administrator if you need access.
            </div>
        </div>
    </div>
@endif
