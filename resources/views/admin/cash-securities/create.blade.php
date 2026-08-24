@extends('layouts.admin')

@section('title', 'Add Cash Security')

@section('content')
<div class="container-fluid cash-security-page">
    @include('admin.partials.page-header', [
        'title' => 'Add Cash Security',
        'subtitle' => 'Record a cash security deposit and link it to the client or loan.',
        'icon' => 'mdi mdi-shield-plus-outline',
        'backFallback' => route('admin.cash-securities.index'),
        'backLabel' => 'Back',
    ])

    @include('admin.cash-securities.partials.alerts')

    <form method="POST" action="{{ route('admin.cash-securities.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('admin.cash-securities.partials.form')
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            @include('admin.partials.back-button', [
                'fallback' => route('admin.cash-securities.index'),
                'label' => 'Cancel',
                'class' => 'btn btn-outline-secondary',
            ])
            <button class="btn btn-dark"><i class="mdi mdi-content-save-outline me-1"></i> Save Security</button>
        </div>
    </form>
</div>
@endsection
