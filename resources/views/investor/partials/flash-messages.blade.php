@if(session('success'))
    <div class="portal-alert portal-alert-success" role="status">{{ session('success') }}</div>
@endif

@if(session('warning'))
    <div class="portal-alert portal-alert-warning" role="status">{{ session('warning') }}</div>
@endif

@if(session('error'))
    <div class="portal-alert portal-alert-error" role="alert">{{ session('error') }}</div>
@endif
