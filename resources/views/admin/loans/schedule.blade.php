@extends('admin.layout')

@section('title', 'Loan Schedule Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt"></i>
                        Loan Payment Schedules
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#generate-schedule-modal">
                            <i class="fas fa-plus"></i> Generate Schedule
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Filter Controls -->
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Search & Filters</h5>
                        </div>
                        <div class="card-body">
                            <form id="filter-form">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="search_loan">Search Loan</label>
                                            <input type="text" class="form-control" name="search_loan" id="search_loan" 
                                                   placeholder="Enter loan code or member name...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="loan_type">Loan Type</label>
                                            <select class="form-control" name="loan_type" id="loan_type">
                                                <option value="">All Loan Types</option>
                                                <option value="personal">Personal Loans</option>
                                                <option value="group">Group Loans</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="payment_frequency">Payment Frequency</label>
                                            <select class="form-control" name="payment_frequency" id="payment_frequency">
                                                <option value="">All Frequencies</option>
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-block">Search</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Loans with Schedules -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h5 class="card-title">Active Loan Schedules</h5>
                            <div class="card-tools">
                                <span class="badge badge-primary">{{ count($loans) }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($loans) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="schedules-table">
                                    <thead>
                                        <tr>
                                            <th>Loan Code</th>
                                            <th>Member/Group</th>
                                            <th>Principal</th>
                                            <th>Period</th>
                                            <th>Frequency</th>
                                            <th>Payment Amount</th>
                                            <th>Next Due</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($loans as $loan)
                                        <tr>
                                            <td>{{ $loan['code'] }}</td>
                                            <td>{{ $loan['member_name'] }}</td>
                                            <td>UGX {{ number_format($loan['principal'], 2) }}</td>
                                            <td>{{ $loan['loan_period'] }} {{ ucfirst($loan['loan_period_type']) }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($loan['payment_frequency']) }}</span>
                                            </td>
                                            <td>UGX {{ number_format($loan['payment_amount'], 2) }}</td>
                                            <td>
                                                @if($loan['next_due_date'])
                                                    {{ \Carbon\Carbon::parse($loan['next_due_date'])->format('d-M-Y') }}
                                                    @if(\Carbon\Carbon::parse($loan['next_due_date'])->isPast())
                                                        <span class="badge badge-danger ml-1">Overdue</span>
                                                    @elseif(\Carbon\Carbon::parse($loan['next_due_date'])->isToday())
                                                        <span class="badge badge-warning ml-1">Due Today</span>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($loan['status'] === 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @elseif($loan['status'] === 'completed')
                                                    <span class="badge badge-primary">Completed</span>
                                                @elseif($loan['status'] === 'overdue')
                                                    <span class="badge badge-danger">Overdue</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($loan['status']) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-info btn-sm view-schedule-btn" 
                                                        data-loan-id="{{ $loan['id'] }}"
                                                        data-loan-type="{{ $loan['type'] }}"
                                                        data-loan-code="{{ $loan['code'] }}">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm regenerate-btn" 
                                                        data-loan-id="{{ $loan['id'] }}"
                                                        data-loan-type="{{ $loan['type'] }}"
                                                        data-loan-code="{{ $loan['code'] }}">
                                                    <i class="fas fa-redo"></i> Regenerate
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm export-btn" 
                                                        data-loan-id="{{ $loan['id'] }}"
                                                        data-loan-type="{{ $loan['type'] }}">
                                                    <i class="fas fa-download"></i> Export
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No loan schedules found. Use the filters above to search for specific loans.
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Schedule Modal -->
<div class="modal fade" id="generate-schedule-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Generate Payment Schedule</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="generate-schedule-form">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="select_loan">Select Loan *</label>
                        <input type="text" class="form-control" id="select_loan" placeholder="Search for a loan by code or member name...">
                        <input type="hidden" name="loan_id" id="selected_loan_id">
                        <input type="hidden" name="loan_type" id="selected_loan_type">
                        <div id="loan-search-results" class="mt-2" style="display: none;">
                            <!-- Search results will be populated here -->
                        </div>
                    </div>
                    
                    <!-- Selected Loan Info -->
                    <div id="selected-loan-details" style="display: none;">
                        <div class="alert alert-info">
                            <h6>Selected Loan Details:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Code:</strong> <span id="detail-code"></span><br>
                                    <strong>Member:</strong> <span id="detail-member"></span><br>
                                    <strong>Principal:</strong> UGX <span id="detail-principal"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Interest Rate:</strong> <span id="detail-rate"></span>%<br>
                                    <strong>Period:</strong> <span id="detail-period"></span><br>
                                    <strong>Payment Frequency:</strong> <span id="detail-frequency"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date *</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="schedule_type">Schedule Type *</label>
                                <select class="form-control" name="schedule_type" id="schedule_type" required>
                                    <option value="">Select schedule type</option>
                                    <option value="equal_installments">Equal Installments</option>
                                    <option value="reducing_balance">Reducing Balance</option>
                                    <option value="flat_rate">Flat Rate</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="replace_existing" id="replace_existing" value="1">
                            <label class="form-check-label" for="replace_existing">
                                Replace existing schedule (if any)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" 
                                  placeholder="Any notes about this schedule generation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Generate Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule View Modal -->
<div class="modal fade" id="schedule-view-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    Payment Schedule - <span id="schedule-loan-code"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="schedule-view-content">
                    <!-- Schedule content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="print-schedule">
                    <i class="fas fa-print"></i> Print Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Regenerate Schedule Modal -->
<div class="modal fade" id="regenerate-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">Regenerate Payment Schedule</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="regenerate-form">
                @csrf
                <input type="hidden" name="loan_id" id="regenerate_loan_id">
                <input type="hidden" name="loan_type" id="regenerate_loan_type">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning!</strong> This will replace the existing payment schedule for loan 
                        <strong id="regenerate-loan-code"></strong>. Any existing payment history will be preserved.
                    </div>
                    
                    <div class="form-group">
                        <label for="regenerate_start_date">New Start Date *</label>
                        <input type="date" class="form-control" name="start_date" id="regenerate_start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="regenerate_reason">Reason for Regeneration *</label>
                        <textarea class="form-control" name="reason" id="regenerate_reason" rows="3" required 
                                  placeholder="Please explain why the schedule is being regenerated..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-redo"></i> Regenerate Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#schedules-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[6, 'asc']] // Sort by next due date
    });
    
    // Loan search for schedule generation
    $('#select_loan').on('input', function() {
        const query = $(this).val();
        if (query.length >= 3) {
            searchLoansForSchedule(query);
        } else {
            $('#loan-search-results').hide();
        }
    });
    
    function searchLoansForSchedule(query) {
        $.ajax({
            url: '{{ route("admin.loan-management.search") }}',
            type: 'GET',
            data: { query: query, for_schedule: true },
            success: function(response) {
                if (response.loans && response.loans.length > 0) {
                    let html = '<div class="list-group">';
                    response.loans.forEach(loan => {
                        html += `<a href="#" class="list-group-item list-group-item-action loan-select-schedule" 
                                   data-loan-id="${loan.id}" 
                                   data-loan-type="${loan.type}"
                                   data-code="${loan.code}"
                                   data-member="${loan.member_name}"
                                   data-principal="${loan.principal}"
                                   data-rate="${loan.interest_rate}"
                                   data-period="${loan.loan_period} ${loan.loan_period_type}"
                                   data-frequency="${loan.payment_frequency}">
                                   <strong>${loan.code}</strong> - ${loan.member_name} 
                                   <br><small>Principal: UGX ${new Intl.NumberFormat().format(loan.principal)}</small>
                                 </a>`;
                    });
                    html += '</div>';
                    $('#loan-search-results').html(html).show();
                } else {
                    $('#loan-search-results').html('<div class="alert alert-warning">No loans found</div>').show();
                }
            }
        });
    }
    
    // Handle loan selection for schedule generation
    $(document).on('click', '.loan-select-schedule', function(e) {
        e.preventDefault();
        
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        const code = $(this).data('code');
        const member = $(this).data('member');
        const principal = $(this).data('principal');
        const rate = $(this).data('rate');
        const period = $(this).data('period');
        const frequency = $(this).data('frequency');
        
        $('#selected_loan_id').val(loanId);
        $('#selected_loan_type').val(loanType);
        $('#detail-code').text(code);
        $('#detail-member').text(member);
        $('#detail-principal').text(new Intl.NumberFormat().format(principal));
        $('#detail-rate').text(rate);
        $('#detail-period').text(period);
        $('#detail-frequency').text(frequency);
        
        $('#select_loan').val(code + ' - ' + member);
        $('#selected-loan-details').show();
        $('#loan-search-results').hide();
    });
    
    // View schedule button
    $('.view-schedule-btn').on('click', function() {
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        const loanCode = $(this).data('loan-code');
        
        $('#schedule-loan-code').text(loanCode);
        $('#schedule-view-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading schedule...</div>');
        $('#schedule-view-modal').modal('show');
        
        $.ajax({
            url: '{{ route("admin.loan-management.schedule.view") }}',
            type: 'GET',
            data: { loan_id: loanId, loan_type: loanType },
            success: function(response) {
                $('#schedule-view-content').html(response.html);
            },
            error: function() {
                $('#schedule-view-content').html('<div class="alert alert-danger">Error loading schedule</div>');
            }
        });
    });
    
    // Regenerate schedule button
    $('.regenerate-btn').on('click', function() {
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        const loanCode = $(this).data('loan-code');
        
        $('#regenerate_loan_id').val(loanId);
        $('#regenerate_loan_type').val(loanType);
        $('#regenerate-loan-code').text(loanCode);
        $('#regenerate_start_date').val(new Date().toISOString().split('T')[0]);
        
        $('#regenerate-modal').modal('show');
    });
    
    // Export schedule button
    $('.export-btn').on('click', function() {
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        
        window.open('{{ route("admin.loan-management.schedule.export") }}?loan_id=' + loanId + '&loan_type=' + loanType, '_blank');
    });
    
    // Generate schedule form submission
    $('#generate-schedule-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.schedule.generate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Schedule generated successfully!');
                    $('#generate-schedule-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Schedule generation failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while generating the schedule';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Regenerate schedule form submission
    $('#regenerate-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Regenerating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.schedule.regenerate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Schedule regenerated successfully!');
                    $('#regenerate-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Schedule regeneration failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while regenerating the schedule';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Print schedule
    $('#print-schedule').on('click', function() {
        const content = $('#schedule-view-content').html();
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Payment Schedule</title>
                    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .table { width: 100%; border-collapse: collapse; }
                        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
                        .table th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    });
    
    // Filter form
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        window.location.href = window.location.pathname + '?' + formData;
    });
    
    // Reset modal forms when closed
    $('#generate-schedule-modal').on('hidden.bs.modal', function() {
        $('#generate-schedule-form')[0].reset();
        $('#selected-loan-details').hide();
        $('#loan-search-results').hide();
    });
});
</script>
@endpush