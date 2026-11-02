@extends('investor.layouts.portal')

@section('title', 'Registration Submitted')

@push('styles')
<style>
    .submitted-panel { background: #fff; border: 1px solid var(--portal-border); margin: 52px auto; max-width: 720px; padding: 42px; text-align: center; }
    .submitted-icon { align-items: center; background: #eaf7f0; border-radius: 50%; color: var(--portal-green); display: inline-flex; font-size: 36px; height: 72px; justify-content: center; width: 72px; }
    .submitted-panel h1 { font-size: 28px; margin: 20px 0 10px; }
    .submitted-panel > p { color: var(--portal-muted); line-height: 1.7; margin: 0 auto; max-width: 560px; }
    .review-steps { border-top: 1px solid var(--portal-border); display: grid; gap: 18px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 30px; padding-top: 28px; text-align: left; }
    .review-step strong { display: block; font-size: 13px; margin-bottom: 5px; }
    .review-step span { color: var(--portal-muted); font-size: 12px; line-height: 1.5; }
    .submitted-actions { align-items: center; display: flex; gap: 18px; justify-content: center; margin-top: 30px; }
    .submitted-actions a { font-weight: 700; text-decoration: none; }
    .submitted-actions .primary { background: var(--portal-green); border-radius: 5px; color: #fff; padding: 12px 18px; }
    @media (max-width: 650px) { .submitted-panel { margin: 20px auto; padding: 30px 20px; } .review-steps { grid-template-columns: 1fr; } .submitted-actions { align-items: stretch; flex-direction: column; } }
</style>
@endpush

@section('content')
<section class="submitted-panel">
    <div class="submitted-icon"><i class="mdi mdi-file-check-outline"></i></div>
    <h1>Registration submitted</h1>
    <p>
        Your investor profile{{ session('registration_email') ? ' for '.session('registration_email') : '' }} has been received.
        Portal access remains locked until a Super Administrator verifies your identity documents and approves the account.
    </p>

    <div class="review-steps">
        <div class="review-step"><strong>1. Information received</strong><span>Your profile and KYC documents are stored for review.</span></div>
        <div class="review-step"><strong>2. Administrative review</strong><span>EBIMS verifies the registration before enabling access.</span></div>
        <div class="review-step"><strong>3. Portal access</strong><span>After approval, sign in with the email and password you registered.</span></div>
    </div>

    <div class="submitted-actions">
        <a class="primary" href="{{ route('investor.login') }}"><i class="mdi mdi-login"></i> Go to investor login</a>
        <a href="{{ route('investor.register') }}">Submit another registration</a>
    </div>
</section>
@endsection
