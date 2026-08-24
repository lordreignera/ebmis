@extends('layouts.admin')

@section('title', 'General Settings')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'General Settings',
    'heading' => 'Manage General Settings',
    'description' => 'Configure organization-wide defaults and core system preferences.',
    'icon' => 'mdi-settings',
])
@endsection
