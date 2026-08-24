@extends('layouts.admin')

@section('title', 'Backup & Restore')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Backup & Restore',
    'heading' => 'Manage Backup & Restore',
    'description' => 'Prepare system backup, restore, and data protection tooling.',
    'icon' => 'mdi-backup-restore',
])
@endsection
