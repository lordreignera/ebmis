@include('admin.settings.partials.back-to-dashboard')

<div class="row page-title-header">
    <div class="col-12">
        <div class="page-header">
            <h4 class="page-title">{{ $title }}</h4>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                <li class="breadcrumb-item active">{{ $title }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h3 class="font-weight-bold">{{ $heading ?? $title }}</h3>
                        <p class="text-muted mb-0">{{ $description }}</p>
                    </div>
                    <div class="icon-lg text-primary">
                        <i class="mdi {{ $icon ?? 'mdi-settings' }}"></i>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0">
                    <i class="mdi mdi-information-outline me-1"></i>
                    This settings page is available from the dashboard. Configuration controls can be added here as the module is completed.
                </div>
            </div>
        </div>
    </div>
</div>
