@extends('layouts.admin')

@section('title', 'Late Fee Waiver - Upgrade Period')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">⚠️ Late Fee Waiver - System Upgrade Period</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <strong>Purpose:</strong> Waive late fees that accumulated during the system upgrade period when clients couldn't make payments.
                    </div>

                    <h5>Upgrade Period Details</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Start Date:</th>
                            <td>October 30, 2025</td>
                        </tr>
                        <tr>
                            <th>End Date:</th>
                            <td>November 27, 2025</td>
                        </tr>
                        <tr>
                            <th>Duration:</th>
                            <td>29 days</td>
                        </tr>
                    </table>

                    <h5 class="mt-4">What Will Happen:</h5>
                    <ul>
                        <li>✓ Late fees from BEFORE upgrade period will be KEPT</li>
                        <li>✓ Only late fees from the 29-day upgrade period will be waived</li>
                        <li>✓ Late fees from AFTER upgrade period will not be affected</li>
                        <li>✓ All changes are logged and reversible</li>
                    </ul>

                    <div class="alert alert-warning mt-4">
                        <strong>⚠️ Important:</strong> This action will waive late fees for all affected clients. Make sure you've backed up your database before proceeding.
                    </div>

                    <form method="POST" action="{{ route('admin.late-fees.waive-upgrade-period') }}" 
                          onsubmit="return confirm('Are you sure you want to waive late fees for the upgrade period? This will affect multiple clients.');">
                        @csrf
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm" id="confirm" required>
                            <label class="form-check-label" for="confirm">
                                I confirm that I have backed up the database and understand the implications
                            </label>
                        </div>

                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-eraser"></i> Process Waiver for Upgrade Period
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
