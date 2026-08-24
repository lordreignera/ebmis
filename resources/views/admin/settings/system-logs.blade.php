@extends('layouts.admin')

@section('title', 'System Logs')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'System Logs',
    'heading' => 'Review System Logs',
    'description' => 'Access system activity, diagnostics, and operational log summaries.',
    'icon' => 'mdi-file-document',
])
@endsection
