@extends('layouts.admin')

@section('title', 'Edit Cash Security')

@section('content')
<div class="container-fluid cash-security-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Edit Cash Security</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.cash-securities.index') }}">Cash Securities</a></li>
                    <li class="breadcrumb-item active">CS-{{ str_pad((string) $cashSecurity->id, 6, '0', STR_PAD_LEFT) }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.cash-securities.show', $cashSecurity) }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

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
                <a href="{{ route('admin.cash-securities.show', $cashSecurity) }}" class="btn btn-outline-secondary">Cancel</a>
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

@push('styles')
<style>.cash-security-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }</style>
@endpush
