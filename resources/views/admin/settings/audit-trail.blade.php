@extends('layouts.admin')

@section('title', 'Audit Trail Settings')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Audit Trail Settings',
    'heading' => 'Manage Audit Trail Settings',
    'description' => 'Configure audit logging behavior for sensitive system activity.',
    'icon' => 'mdi-history',
])
@endsection
