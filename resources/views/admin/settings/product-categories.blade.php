@extends('layouts.admin')

@section('title', 'Product Categories')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Product Categories',
    'heading' => 'Manage Product Categories',
    'description' => 'Group loan, savings, fee, and school loan products into operational categories.',
    'icon' => 'mdi-shape-outline',
])
@endsection
