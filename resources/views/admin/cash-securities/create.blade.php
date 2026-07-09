@extends('layouts.admin')

@section('title', 'Add Cash Security')

@section('content')
<div class="container-fluid cash-security-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">Add Cash Security</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.cash-securities.index') }}">Cash Securities</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.cash-securities.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.cash-securities.partials.alerts')

    <form method="POST" action="{{ route('admin.cash-securities.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('admin.cash-securities.partials.form')
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.cash-securities.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-dark"><i class="mdi mdi-content-save-outline me-1"></i> Save Security</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>.cash-security-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }</style>
@endpush
