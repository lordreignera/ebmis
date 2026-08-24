@extends('layouts.admin')

@section('title', 'Bulk SMS Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-message-text"></i> {{ $bulkSms->title ?? 'SMS Campaign' }}
                    </h3>
                    <a href="{{ route('admin.bulk-sms.index') }}" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Records
                    </a>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['total_recipients'] ?? 0 }}</h4>
                                    <small>Total Recipients</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['successful'] ?? 0 }}</h4>
                                    <small>Successful</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['failed'] ?? 0 }}</h4>
                                    <small>Failed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['pending'] ?? 0 }}</h4>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5>Message</h5>
                            <div class="border rounded p-3 bg-light">{{ $bulkSms->message }}</div>
                        </div>
                        <div class="col-md-4">
                            <h5>Campaign Info</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>Status</th>
                                    <td>{{ ucfirst($bulkSms->status ?? 'pending') }}</td>
                                </tr>
                                <tr>
                                    <th>Recipient Type</th>
                                    <td>{{ ucfirst($bulkSms->recipient_type ?? 'all') }}</td>
                                </tr>
                                <tr>
                                    <th>Group</th>
                                    <td>{{ $bulkSms->recipient_group ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Scheduled</th>
                                    <td>{{ optional($bulkSms->scheduled_at)->format('M d, Y H:i') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Completed</th>
                                    <td>{{ optional($bulkSms->completed_at)->format('M d, Y H:i') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Sent By</th>
                                    <td>{{ $bulkSms->sentBy->name ?? 'System' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bulkSms->smsLogs as $log)
                                    <tr>
                                        <td>{{ $log->member ? trim($log->member->fname . ' ' . $log->member->lname) : 'Unknown Member' }}</td>
                                        <td>{{ $log->phone }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($log->sent_at)->format('M d, Y H:i') ?? '-' }}</td>
                                        <td>{{ $log->error_message ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No recipient logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
