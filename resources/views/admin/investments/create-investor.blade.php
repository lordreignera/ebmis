@extends('layouts.admin')

@php
    $editing = isset($investor);
@endphp
@section('title', $editing ? 'Edit Investor' : 'Add Investor')

@section('content')
<div class="container-fluid">
    @include('admin.partials.page-header', [
        'title' => $editing ? 'Edit Investor' : 'Add Investor',
        'subtitle' => $editing ? 'Update the investor profile and account status.' : 'Register an investor before creating an investment.',
        'icon' => 'mdi mdi-account-cash',
        'backFallback' => route('admin.investments.investors'),
        'backLabel' => 'Back to Investors',
    ])

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ $editing ? route('admin.investments.update-investor', $investor) : route('admin.investments.store-investor') }}">
            @csrf
            @if($editing) @method('PUT') @endif

            <h5 class="mb-3">Personal information</h5>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Title</label>
                    <select name="title" class="form-select" required>
                        @foreach([1 => 'Mr.', 2 => 'Mrs.', 3 => 'Ms.', 4 => 'Dr.', 5 => 'Prof.'] as $value => $label)
                            <option value="{{ $value }}" @selected((int) old('title', $investor->title ?? 1) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5"><label class="form-label">First name</label><input name="fname" class="form-control" value="{{ old('fname', $investor->fname ?? '') }}" required maxlength="200"></div>
                <div class="col-md-5"><label class="form-label">Last name</label><input name="lname" class="form-control" value="{{ old('lname', $investor->lname ?? '') }}" required maxlength="200"></div>
                <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $investor->email ?? '') }}" required></div>
                <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $investor->phone ?? '') }}" required maxlength="100"></div>
                <div class="col-md-2">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        @foreach(['Male', 'Female'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $investor->gender ?? '') === $gender)>{{ $gender }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Date of birth</label><input type="date" name="dob" class="form-control" value="{{ old('dob', isset($investor) ? \Carbon\Carbon::parse($investor->dob)->format('Y-m-d') : '') }}" required></div>
            </div>

            <h5 class="mt-4 mb-3">Identification and location</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID type</label>
                    <select name="IDtype" class="form-select" required>
                        @foreach(['National ID', 'Passport', 'Driving Permit', 'Other'] as $idType)<option value="{{ $idType }}" @selected(old('IDtype', $investor->IDtype ?? '') === $idType)>{{ $idType }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">ID number</label><input name="IDnumber" class="form-control" value="{{ old('IDnumber', $investor->IDnumber ?? '') }}" required></div>
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <select name="country" class="form-select" required>
                        <option value="">Select country</option>
                        @foreach($countries as $country)<option value="{{ $country->id }}" @selected((string) old('country', $investor->country ?? '') === (string) $country->id)>{{ $country->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city', $investor->city ?? '') }}" required></div>
                <div class="col-md-9"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $investor->address ?? '') }}" required></div>
                <div class="col-md-3"><label class="form-label">Postal/ZIP code</label><input name="zip" class="form-control" value="{{ old('zip', $investor->zip ?? '') }}"></div>
                <div class="col-md-9"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $investor->description ?? '') }}</textarea></div>
                @if($editing)
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach([0 => 'Pending', 1 => 'Active', 2 => 'Suspended', 3 => 'Deactivated'] as $value => $label)<option value="{{ $value }}" @selected((int) old('status', $investor->status) === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> {{ $editing ? 'Save Changes' : 'Register Investor' }}</button>
                <a href="{{ route('admin.investments.investors') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
