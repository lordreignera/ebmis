@extends('investor.layouts.portal')

@section('title', 'Investor Login')

@push('styles')
<style>
    .login-stage { display: grid; gap: 0; grid-template-columns: minmax(0, 1.05fr) minmax(360px, .95fr); min-height: 610px; }
    .login-context { background: #173f36; color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 58px; }
    .login-context h1 { font-size: 38px; line-height: 1.15; margin: 0 0 18px; max-width: 540px; }
    .login-context p { color: #d8ebe3; font-size: 16px; line-height: 1.7; max-width: 560px; }
    .login-points { display: grid; gap: 16px; margin-top: 38px; }
    .login-point { align-items: flex-start; display: flex; gap: 12px; }
    .login-point i { color: #8bd0af; font-size: 22px; }
    .login-point span { color: #e5f3ed; font-size: 14px; line-height: 1.5; }
    .login-note { border-top: 1px solid rgba(255,255,255,.2); color: #c7ddd4; font-size: 12px; padding-top: 20px; }
    .login-form-panel { background: #fff; display: flex; flex-direction: column; justify-content: center; padding: 58px; }
    .login-form-panel h2 { font-size: 28px; margin: 0 0 8px; }
    .login-form-panel > p { color: var(--portal-muted); margin: 0 0 30px; }
    .field { margin-bottom: 20px; }
    .field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
    .field input { border: 1px solid var(--portal-border); border-radius: 5px; font: inherit; padding: 13px 14px; width: 100%; }
    .field input:focus { border-color: var(--portal-green); box-shadow: 0 0 0 3px rgba(23,107,77,.12); outline: none; }
    .field-error { color: var(--portal-red); display: block; font-size: 12px; margin-top: 6px; }
    .form-row { align-items: center; display: flex; font-size: 13px; justify-content: space-between; margin-bottom: 22px; }
    .form-row label { align-items: center; display: flex; gap: 8px; }
    .login-submit { background: var(--portal-green); border: 0; border-radius: 5px; color: #fff; cursor: pointer; font: inherit; font-weight: 700; padding: 14px; width: 100%; }
    .login-submit:hover { background: var(--portal-green-dark); }
    .register-link { border-top: 1px solid var(--portal-border); margin-top: 28px; padding-top: 22px; text-align: center; }
    @media (max-width: 850px) { .login-stage { grid-template-columns: 1fr; } .login-context { padding: 36px 28px; } .login-form-panel { padding: 38px 28px; } }
</style>
@endpush

@section('content')
<section class="login-stage">
    <div class="login-context">
        <div>
            <h1>Track your investment with clarity.</h1>
            <p>Access your approved investment applications, committed capital, documents, and account status from one secure portal.</p>
            <div class="login-points">
                <div class="login-point"><i class="mdi mdi-shield-check-outline"></i><span>Investor accounts are reviewed by EBIMS before access is granted.</span></div>
                <div class="login-point"><i class="mdi mdi-file-document-outline"></i><span>Your approved investment records remain separate from staff administration.</span></div>
                <div class="login-point"><i class="mdi mdi-chart-timeline-variant"></i><span>Returns will be reported from verified fund performance, not estimates.</span></div>
            </div>
        </div>
        <div class="login-note">Access your personal investor account.</div>
    </div>

    <div class="login-form-panel">
        <h2>Investor login</h2>
        <p>Sign in after your registration has been approved.</p>

        @if($errors->any())
            <div class="portal-alert portal-alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('investor.login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-row">
                <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
            </div>
            <button class="login-submit" type="submit"><i class="mdi mdi-login"></i> Sign in to investor portal</button>
        </form>

        <div class="register-link">New investor? <a href="{{ route('investor.register') }}">Submit your registration</a></div>
    </div>
</section>
@endsection
