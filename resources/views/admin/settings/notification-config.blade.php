@extends('layouts.admin')

@section('title', 'Notification Settings')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Notification Settings',
    'heading' => 'Manage Notification Settings',
    'description' => 'Configure system alerts, reminders, and notification preferences.',
    'icon' => 'mdi-bell',
])
@endsection
