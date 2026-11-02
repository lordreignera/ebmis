@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @if($limitedDashboardMode ?? false)
        @include('admin.dashboard-landing')
    @elseif($officerDashboardMode ?? false)
        @include('admin.officer-dashboard')
    @else
        @include('admin.body')
    @endif
@endsection
