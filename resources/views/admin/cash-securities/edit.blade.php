@extends('layouts.admin')

@section('title', 'Edit Cash Security')

@section('content')
<div class="container-fluid cash-security-page">
    @include('admin.partials.page-header', [
        'title' => 'Edit Cash Security',
        'subtitle' => 'CS-' . str_pad((string) $cashSecurity->id, 6, '0', STR_PAD_LEFT),
        'icon' => 'mdi mdi-shield-edit-outline',
        'backFallback' => route('admin.cash-securities.show', $cashSecurity),
        'backLabel' => 'Back',
    ])

    @include('admin.cash-securities.partials.alerts')

    <form method="POST" action="{{ route('admin.cash-securities.update', $cashSecurity) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('admin.cash-securities.partials.form')
        </div>
        <div class="card-footer bg-white d-flex justify-content-between gap-2">
            @if($cashSecurity->can_delete)
                <button type="submit" form="deleteCashSecurityForm" class="btn btn-outline-danger"
                        onclick="return confirm('Delete this pending or failed cash security?')">
                    <i class="mdi mdi-delete-outline me-1"></i> Delete
                </button>
            @else
                <span></span>
            @endif
            <div class="d-flex gap-2">
                @include('admin.partials.back-button', [
                    'fallback' => route('admin.cash-securities.show', $cashSecurity),
                    'label' => 'Cancel',
                    'class' => 'btn btn-outline-secondary',
                ])
                <button class="btn btn-dark"><i class="mdi mdi-content-save-outline me-1"></i> Save Changes</button>
            </div>
        </div>
    </form>

    @if($cashSecurity->can_delete)
        <form id="deleteCashSecurityForm" method="POST" action="{{ route('admin.cash-securities.destroy', $cashSecurity) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@endsection
