@extends('layouts.admin')

@section('title', 'Check Current User')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">🔐 Currently Logged In As:</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <td width="200"><strong>User ID:</strong></td>
                            <td><span class="badge bg-info fs-5">{{ auth()->id() }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Full Name:</strong></td>
                            <td><h5 class="mb-0">{{ auth()->user()->name }}</h5></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{{ auth()->user()->email }}</td>
                        </tr>
                        <tr>
                            <td><strong>Auth Check:</strong></td>
                            <td>
                                @if(auth()->check())
                                    <span class="badge bg-success">✓ Authenticated</span>
                                @else
                                    <span class="badge bg-danger">✗ Not Authenticated</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <div class="alert alert-info mt-3">
                        <h5>📝 What This Means:</h5>
                        <p>When you create members, loans, fees, or any other records, they will be marked as "Added By: <strong>{{ auth()->user()->name }}</strong>"</p>
                        <p class="mb-0"><strong>If this shows the wrong user:</strong></p>
                        <ol>
                            <li>Click "Logout" in the top right</li>
                            <li>Close ALL browser tabs</li>
                            <li>Clear your browser cache and cookies</li>
                            <li>Log in again with YOUR credentials</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Test -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">🔍 Your Recent Activity</h4>
                </div>
                <div class="card-body">
                    <h5>Recently Added Members:</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Added By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentMembers = \App\Models\Member::with('addedBy')
                                        ->orderBy('datecreated', 'desc')
                                        ->limit(5)
                                        ->get();
                                @endphp
                                @foreach($recentMembers as $member)
                                <tr class="{{ $member->added_by == auth()->id() ? 'table-success' : '' }}">
                                    <td>{{ $member->fname }} {{ $member->lname }}</td>
                                    <td>
                                        <span class="badge {{ $member->added_by == auth()->id() ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $member->addedBy->name ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>{{ $member->datecreated ? $member->datecreated->format('M d, Y H:i') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mt-4">Recently Added Loans:</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Loan Code</th>
                                    <th>Amount</th>
                                    <th>Added By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentLoans = \App\Models\PersonalLoan::with('addedBy')
                                        ->whereNotNull('added_by')
                                        ->orderBy('datecreated', 'desc')
                                        ->limit(5)
                                        ->get();
                                @endphp
                                @foreach($recentLoans as $loan)
                                <tr class="{{ $loan->added_by == auth()->id() ? 'table-success' : '' }}">
                                    <td>{{ $loan->code }}</td>
                                    <td>{{ number_format($loan->principal) }} UGX</td>
                                    <td>
                                        <span class="badge {{ $loan->added_by == auth()->id() ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $loan->addedBy->name ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>{{ $loan->datecreated ? $loan->datecreated->format('M d, Y H:i') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-success">
                        <strong>Green rows</strong> = Records YOU added<br>
                        <strong>Gray rows</strong> = Records added by other users
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
