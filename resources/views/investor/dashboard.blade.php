@extends('investor.layouts.portal')

@section('title', 'Overview')

@push('styles')
<style>
    .dashboard-heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 24px; }
    .dashboard-heading h1 { font-size: 28px; margin: 0 0 6px; }
    .dashboard-heading p { color: var(--portal-muted); margin: 0; }
    .status-badge { background: #eaf7f0; border: 1px solid #acd6be; border-radius: 999px; color: #155c3f; font-size: 12px; font-weight: 700; padding: 7px 11px; }
    .summary-grid { display: grid; gap: 16px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 24px; }
    .summary-item { background: #fff; border: 1px solid var(--portal-border); border-top: 3px solid var(--accent); min-height: 126px; padding: 20px; }
    .summary-item i { color: var(--accent); font-size: 24px; }
    .summary-item span { color: var(--portal-muted); display: block; font-size: 12px; font-weight: 700; margin-top: 13px; text-transform: uppercase; }
    .summary-item strong { display: block; font-size: 24px; margin-top: 5px; }
    .dashboard-band { background: #fff; border: 1px solid var(--portal-border); margin-bottom: 22px; }
    .dashboard-band-head { align-items: center; border-bottom: 1px solid var(--portal-border); display: flex; justify-content: space-between; padding: 16px 20px; }
    .dashboard-band-head h2 { font-size: 17px; margin: 0; }
    .dashboard-band-head span { color: var(--portal-muted); font-size: 12px; }
    .investment-list { width: 100%; }
    .investment-row { align-items: center; border-bottom: 1px solid #edf1f4; display: grid; gap: 12px; grid-template-columns: minmax(180px, 2fr) repeat(3, minmax(110px, 1fr)); padding: 16px 20px; }
    .investment-row:last-child { border-bottom: 0; }
    .investment-row small { color: var(--portal-muted); display: block; margin-top: 4px; }
    .investment-label { color: var(--portal-muted); display: none; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .empty-state { color: var(--portal-muted); padding: 42px 20px; text-align: center; }
    .notice-band { background: #eef5f8; border-left: 4px solid var(--portal-blue); color: #27485f; font-size: 13px; line-height: 1.6; padding: 16px 18px; }
    @media (max-width: 760px) { .summary-grid { grid-template-columns: 1fr; } .dashboard-heading { align-items: flex-start; flex-direction: column; gap: 12px; } .investment-row { grid-template-columns: 1fr 1fr; } .investment-label { display: block; } }
</style>
@endpush

@section('content')
<div class="dashboard-heading">
    <div>
        <h1>Welcome, {{ $investor->fname }}</h1>
        <p>Your approved investor account and investment applications.</p>
    </div>
    <span class="status-badge"><i class="mdi mdi-check-decagram"></i> Approved investor</span>
</div>

<div class="summary-grid">
    <div class="summary-item" style="--accent: var(--portal-blue)">
        <i class="mdi mdi-file-document-multiple-outline"></i>
        <span>Investment records</span>
        <strong>{{ number_format($summary['applications']) }}</strong>
    </div>
    <div class="summary-item" style="--accent: var(--portal-green)">
        <i class="mdi mdi-check-circle-outline"></i>
        <span>Active records</span>
        <strong>{{ number_format($summary['active']) }}</strong>
    </div>
    <div class="summary-item" style="--accent: var(--portal-amber)">
        <i class="mdi mdi-cash-multiple"></i>
        <span>Committed capital</span>
        <strong>UGX {{ number_format($summary['committed_capital'], 0) }}</strong>
    </div>
</div>

<section class="dashboard-band">
    <div class="dashboard-band-head">
        <h2>My investment records</h2>
        <span>Only records attached to your investor account are shown</span>
    </div>
    @forelse($investments as $investment)
        <div class="investment-row">
            <div>
                <strong>{{ $investment->name }}</strong>
                <small>Investment record #{{ $investment->id }}</small>
            </div>
            <div><span class="investment-label">Capital</span>UGX {{ number_format((float) $investment->amount, 0) }}</div>
            <div><span class="investment-label">Period</span>{{ $investment->period }} year{{ (int) $investment->period === 1 ? '' : 's' }}</div>
            <div><span class="investment-label">Status</span>{{ $investment->status_name }}</div>
        </div>
    @empty
        <div class="empty-state">
            <i class="mdi mdi-file-document-outline" style="font-size:34px"></i>
            <p>No investment application has been connected to your account yet.</p>
        </div>
    @endforelse
</section>

<div class="notice-band">
    <strong>Returns reporting:</strong> This first portal stage does not display projected interest as earned income. Verified returns will appear after the Revolving Fund performance and reconciliation workflow is implemented.
</div>
@endsection
