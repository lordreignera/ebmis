@extends('layouts.admin')

@section('title', 'Email Configuration')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Email Configuration',
    'heading' => 'Manage Email Configuration',
    'description' => 'Configure sender details, notification templates, and email delivery settings.',
    'icon' => 'mdi-email',
])
@endsection
