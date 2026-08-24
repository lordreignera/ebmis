@extends('layouts.admin')

@section('title', 'Data Import/Export')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Data Import/Export',
    'heading' => 'Manage Data Import/Export',
    'description' => 'Prepare import and export tools for operational system data.',
    'icon' => 'mdi-import',
])
@endsection
