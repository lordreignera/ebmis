@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $icon = $icon ?? null;
    $backFallback = $backFallback ?? null;
    $backLabel = $backLabel ?? 'Back';
    $actions = $actions ?? null;
@endphp

<div class="ebims-page-header">
    <div class="ebims-page-heading">
        @if($icon)
            <span class="ebims-page-icon"><i class="{{ $icon }}"></i></span>
        @endif
        <div>
            <h3>{{ $title }}</h3>
            @if($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if($backFallback || $actions)
        <div class="ebims-page-actions">
            @if($actions)
                {!! $actions !!}
            @endif

            @if($backFallback)
                @include('admin.partials.back-button', [
                    'fallback' => $backFallback,
                    'label' => $backLabel,
                ])
            @endif
        </div>
    @endif
</div>
