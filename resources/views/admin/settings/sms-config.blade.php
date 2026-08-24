@extends('layouts.admin')

@section('title', 'SMS Configuration')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'SMS Configuration',
    'heading' => 'Manage SMS Configuration',
    'description' => 'Configure SMS provider details, sender IDs, and notification defaults.',
    'icon' => 'mdi-message-text',
])
@endsection
