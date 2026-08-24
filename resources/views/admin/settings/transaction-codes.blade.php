@extends('layouts.admin')

@section('title', 'Transaction Codes')

@section('content')
@include('admin.settings.partials.simple-settings-page', [
    'title' => 'Transaction Codes',
    'heading' => 'Manage Transaction Codes',
    'description' => 'Configure transaction codes used for posting, reporting, and reconciliation.',
    'icon' => 'mdi-code-tags',
])
@endsection
