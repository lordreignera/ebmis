@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @if($officerDashboardMode ?? false)
        @include('admin.officer-dashboard')
    @else
        @include('admin.body')
    @endif
@endsection
