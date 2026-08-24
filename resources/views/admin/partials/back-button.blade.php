@php
    $fallback = $fallback ?? route('admin.home');
    $label = $label ?? 'Back';
    $class = $class ?? 'btn btn-outline-secondary btn-sm';
@endphp

<a href="{{ $fallback }}" class="{{ $class }} ebims-back-button" data-fallback-url="{{ $fallback }}">
    <i class="mdi mdi-arrow-left me-1"></i>{{ $label }}
</a>

@once
    @push('scripts')
        <script>
            document.addEventListener('click', function (event) {
                const backButton = event.target.closest('.ebims-back-button');

                if (!backButton) {
                    return;
                }

                const referrer = document.referrer ? new URL(document.referrer) : null;
                const sameOriginReferrer = referrer && referrer.origin === window.location.origin;

                if (sameOriginReferrer && window.history.length > 1) {
                    event.preventDefault();
                    window.history.back();
                }
            });
        </script>
    @endpush
@endonce
