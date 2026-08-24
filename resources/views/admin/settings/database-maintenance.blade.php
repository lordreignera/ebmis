@extends('layouts.admin')

@section('title', 'Database Maintenance')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Database Maintenance',
    'heading' => 'Manage Database Maintenance',
    'description' => 'Review database maintenance, optimization, and housekeeping tools.',
    'icon' => 'mdi-database',
])
@endsection
