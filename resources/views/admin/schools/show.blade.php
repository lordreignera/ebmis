@extends('layouts.admin')

@section('title', 'School Details')

@section('content')
<!-- Header -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="font-weight-bold" style="color: #000000;">School Details</h3>
                                <p class="text-muted mb-0">{{ $school->school_name }}</p>
                            </div>
                            <div>
                                <a href="{{ route('admin.schools.index') }}" class="btn btn-light">
                                    <i class="mdi mdi-arrow-left"></i> Back to List
                                </a>
                                <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-primary">
                                    <i class="mdi mdi-pencil"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Status and Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 style="color: #000000;">Status: 
                                            @if($school->status == 'pending')
                                                <span class="badge bg-warning text-dark">Pending Review</span>
                                            @elseif($school->status == 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($school->status == 'suspended')
                                                <span class="badge bg-secondary">Suspended</span>
                                            @else
                                                <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </h5>
                                        <p class="mb-0 text-muted">Assessment: 
                                            @if($school->assessment_complete)
                                                <span class="badge bg-success">Complete</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Incomplete</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        @if($school->status == 'pending')
                                            <form action="{{ route('admin.schools.approve', $school) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this school?')">
                                                    <i class="mdi mdi-check"></i> Approve School
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                                <i class="mdi mdi-close"></i> Reject School
                                            </button>
                                        @elseif($school->status == 'approved')
                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#suspendModal">
                                                <i class="mdi mdi-pause"></i> Suspend School
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Basic Information</h4>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th style="color: #000000; width: 40%;">School Name:</th>
                                        <td class="text-wrap">{{ $school->school_name }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">School Type:</th>
                                        <td class="text-wrap">{{ $school->school_type ?? 'Not specified' }}</td>
                                    </tr>
                                    @if($school->school_types)
                                    <tr>
                                        <th style="color: #000000;" class="align-top">Additional School Types:</th>
                                        <td>
                                            @php
                                                $schoolTypes = is_array($school->school_types) ? $school->school_types : json_decode($school->school_types, true);
                                            @endphp
                                            @if($schoolTypes && is_array($schoolTypes))
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($schoolTypes as $type)
                                                        <span class="badge bg-info mb-1">{{ $type }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th style="color: #000000;">Ownership:</th>
                                        <td class="text-wrap">{{ $school->ownership ?? 'Not specified' }}</td>
                                    </tr>
                                    @if($school->ownership_details)
                                    <tr>
                                        <th style="color: #000000;" class="align-top">Ownership Details:</th>
                                        <td>
                                            @php
                                                $ownershipDetails = is_array($school->ownership_details) ? $school->ownership_details : json_decode($school->ownership_details, true);
                                            @endphp
                                            @if($ownershipDetails && is_array($ownershipDetails))
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Owner Name</th>
                                                            <th>Percentage (%)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($ownershipDetails as $owner)
                                                            <tr>
                                                                <td class="text-wrap">{{ $owner['name'] ?? 'N/A' }}</td>
                                                                <td>{{ number_format($owner['percentage'] ?? 0, 2) }}%</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th style="color: #000000;">Date Established:</th>
                                        <td class="text-wrap">{{ $school->date_of_establishment ? \Carbon\Carbon::parse($school->date_of_establishment)->format('d M Y') : 'Not specified' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Registration Number:</th>
                                        <td class="text-wrap">{{ $school->registration_number ?? 'Not specified' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">TIN:</th>
                                        <td class="text-wrap">{{ $school->tin ?? 'Not specified' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Contact Information</h4>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th style="color: #000000; width: 40%;">Contact Person:</th>
                                        <td class="text-wrap">{{ $school->contact_person }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Position:</th>
                                        <td class="text-wrap">{{ $school->contact_position }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Email:</th>
                                        <td class="text-wrap"><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Phone:</th>
                                        <td class="text-wrap">{{ $school->phone }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Alternative Phone:</th>
                                        <td class="text-wrap">{{ $school->alternative_phone ?? 'Not specified' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Website:</th>
                                        <td class="text-wrap">{{ $school->website ?? 'Not specified' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Location Details</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th style="color: #000000; width: 50%;">District:</th>
                                                <td class="text-wrap">{{ $school->district_other ?? $school->district }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">County/Subcounty:</th>
                                                <td class="text-wrap">{{ $school->county_other ?? $school->county ?? 'Not specified' }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Parish:</th>
                                                <td class="text-wrap">{{ $school->parish_other ?? $school->parish ?? 'Not specified' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-4">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th style="color: #000000; width: 50%;">Village:</th>
                                                <td class="text-wrap">{{ $school->village_other ?? $school->village ?? 'Not specified' }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Physical Address:</th>
                                                <td class="text-wrap">{{ $school->physical_address ?? 'Not specified' }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">GPS Coordinates:</th>
                                                <td class="text-wrap">{{ $school->gps_coordinates ?? 'Not specified' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-4">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th style="color: #000000; width: 45%;">Administrator Name:</th>
                                                <td class="text-wrap">{{ $school->administrator_name ?? 'Not specified' }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000; width: 45%;">Administrator Email:</th>
                                                <td class="text-wrap" style="word-break: break-all;">{{ $school->administrator_email ?? 'Not specified' }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000; width: 45%;">Administrator Phone:</th>
                                                <td class="text-wrap">{{ $school->administrator_contact_number ?? 'Not specified' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staffing & Enrollment -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Staffing & Enrollment</h4>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th style="color: #000000; width: 50%;">Teaching Staff:</th>
                                        <td>{{ $school->total_teaching_staff ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Non-Teaching Staff:</th>
                                        <td>{{ $school->total_non_teaching_staff ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Current Enrollment:</th>
                                        <td>{{ $school->current_student_enrollment ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Student Capacity:</th>
                                        <td>{{ $school->maximum_student_capacity ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Average School Fees:</th>
                                        <td class="text-wrap">UGX {{ number_format($school->average_tuition_fees_per_term ?? 0) }}</td>
                                    </tr>
                                    @if($school->income_sources && json_decode($school->income_sources))
                                    <tr>
                                        <th style="color: #000000;" class="align-top">Other Income:</th>
                                        <td>
                                            <ul class="mb-0 small ps-3">
                                                @php
                                                    $sources = json_decode($school->income_sources);
                                                    $amounts = json_decode($school->income_amounts);
                                                @endphp
                                                @foreach($sources as $index => $source)
                                                    <li class="text-wrap">{{ $source }}: UGX {{ number_format($amounts[$index] ?? 0) }}/month</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($school->student_fees_file_path)
                                    <tr>
                                        <th style="color: #000000;">Student Fees File:</th>
                                        <td>
                                            <a href="{{ Storage::url($school->student_fees_file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="mdi mdi-file-excel"></i> View File
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Infrastructure</h4>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th style="color: #000000; width: 40%;">Classrooms:</th>
                                        <td>{{ $school->number_of_classrooms ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Dormitories:</th>
                                        <td>{{ $school->number_of_dormitories ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Toilets:</th>
                                        <td>{{ $school->number_of_toilets ?? '0' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Electricity:</th>
                                        <td class="text-wrap">{{ $school->has_electricity ? 'Yes' : 'No' }}
                                            @if($school->has_electricity && $school->electricity_provider)
                                                ({{ $school->electricity_provider }})
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Water Supply:</th>
                                        <td class="text-wrap">{{ $school->water_source ?? 'Not specified' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="color: #000000;">Internet Access:</th>
                                        <td class="text-wrap">{{ $school->has_internet_access ? 'Yes' : 'No' }}
                                            @if($school->has_internet_access && $school->internet_provider)
                                                ({{ $school->internet_provider }})
                                            @endif
                                        </td>
                                    </tr>
                                    @if($school->transport_assets)
                                    <tr>
                                        <th style="color: #000000;" class="align-top">Transport Assets:</th>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @php
                                                    $transportAssets = is_array($school->transport_assets) ? $school->transport_assets : json_decode($school->transport_assets, true);
                                                @endphp
                                                @if($transportAssets && is_array($transportAssets))
                                                    @foreach($transportAssets as $asset)
                                                        <span class="badge bg-secondary mb-1">{{ $asset }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-wrap">{{ $school->transport_assets }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($school->learning_resources_available)
                                    <tr>
                                        <th style="color: #000000;" class="align-top">Learning Resources:</th>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @php
                                                    $learningResources = is_array($school->learning_resources_available) ? $school->learning_resources_available : json_decode($school->learning_resources_available, true);
                                                @endphp
                                                @if($learningResources && is_array($learningResources))
                                                    @foreach($learningResources as $resource)
                                                        <span class="badge bg-primary mb-1">{{ $resource }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-wrap">{{ $school->learning_resources_available }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                @if($school->assessment_complete)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Financial Information</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 style="color: #000000;">Cash Flow</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th style="color: #000000; width: 60%;">First Month Revenue:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->first_month_revenue ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Last Month Expenditure:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->last_month_expenditure ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Past 2 Terms Shortfall:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->past_two_terms_shortfall ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Expected Shortfall:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->expected_shortfall_this_term ?? 0) }}</td>
                                            </tr>
                                            @if($school->unpaid_students_list)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Unpaid Students:</th>
                                                <td class="text-wrap small">{{ $school->unpaid_students_list }}</td>
                                            </tr>
                                            @endif
                                            @if($school->reserve_funds_status)
                                            <tr>
                                                <th style="color: #000000;">Reserve Funds:</th>
                                                <td class="text-wrap">{{ $school->reserve_funds_status }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 style="color: #000000;">Performance</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th style="color: #000000; width: 60%;">Avg Monthly Income:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->average_monthly_income ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Avg Monthly Expenses:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->average_monthly_expenses ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <th style="color: #000000;">Profit/Surplus:</th>
                                                <td class="text-wrap">UGX {{ number_format($school->profit_or_surplus ?? 0) }}</td>
                                            </tr>
                                            @if($school->banking_institutions_used)
                                            <tr>
                                                <th style="color: #000000;">Banking Institutions:</th>
                                                <td class="text-wrap">{{ $school->banking_institutions_used }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <th style="color: #000000;">Audited Statements:</th>
                                                <td>{{ $school->has_audited_statements ? 'Yes' : 'No' }}</td>
                                            </tr>
                                            @if($school->expense_breakdown)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Expense Breakdown:</th>
                                                <td>
                                                    @php
                                                        $expenses = json_decode($school->expense_breakdown, true);
                                                    @endphp
                                                    @if($expenses)
                                                        <ul class="mb-0 small ps-3">
                                                            @foreach($expenses as $category => $amount)
                                                                <li class="text-wrap">{{ $category }}: UGX {{ number_format($amount) }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                            @if($school->expense_categories && $school->expense_amounts)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Monthly Expense Categories:</th>
                                                <td>
                                                    @php
                                                        $expenseCategories = is_array($school->expense_categories) ? $school->expense_categories : json_decode($school->expense_categories, true);
                                                        $expenseAmounts = is_array($school->expense_amounts) ? $school->expense_amounts : json_decode($school->expense_amounts, true);
                                                    @endphp
                                                    @if($expenseCategories && $expenseAmounts && is_array($expenseCategories) && is_array($expenseAmounts))
                                                        <ul class="mb-0 small ps-3">
                                                            @foreach($expenseCategories as $index => $category)
                                                                <li class="text-wrap">{{ $category }}: UGX {{ number_format($expenseAmounts[$index] ?? 0) }}/month</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 style="color: #000000;">Institutional Standing</h6>
                                        <table class="table table-borderless table-sm">
                                            @if($school->current_assets_list)
                                            <tr>
                                                <th style="color: #000000; width: 50%;" class="align-top">Current Assets:</th>
                                                <td class="text-wrap small">{{ $school->current_assets_list }}</td>
                                            </tr>
                                            @endif
                                            @if($school->current_liabilities_list)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Current Liabilities:</th>
                                                <td class="text-wrap small">{{ $school->current_liabilities_list }}</td>
                                            </tr>
                                            @endif
                                            @if($school->debtors_creditors_list)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Debtors/Creditors:</th>
                                                <td class="text-wrap small">{{ $school->debtors_creditors_list }}</td>
                                            </tr>
                                            @endif
                                            @if($school->ministry_of_education_standing)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">MOE Standing:</th>
                                                <td class="text-wrap">{{ $school->ministry_of_education_standing }}</td>
                                            </tr>
                                            @endif
                                            @if($school->license_validity_status)
                                            <tr>
                                                <th style="color: #000000;">License Status:</th>
                                                <td class="text-wrap">{{ $school->license_validity_status }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <th style="color: #000000;">Outstanding Loans:</th>
                                                <td>{{ $school->has_outstanding_loans ? 'Yes' : 'No' }}</td>
                                            </tr>
                                            @if($school->has_outstanding_loans && $school->outstanding_loans_details)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Loan Details:</th>
                                                <td class="text-wrap small">{{ $school->outstanding_loans_details }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <th style="color: #000000;">Assets as Collateral:</th>
                                                <td>{{ $school->has_assets_as_collateral ? 'Yes' : 'No' }}</td>
                                            </tr>
                                            @if($school->has_assets_as_collateral && $school->collateral_assets_details)
                                            <tr>
                                                <th style="color: #000000;" class="align-top">Collateral Details:</th>
                                                <td class="text-wrap small">{{ $school->collateral_assets_details }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Request -->
                @if($school->loan_amount_requested)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;"><i class="mdi mdi-cash"></i> Loan Request</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-1 text-muted">Requested Amount</p>
                                        <h4 style="color: #000000;">UGX {{ number_format($school->loan_amount_requested) }}</h4>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-1 text-muted">Repayment Period</p>
                                        <h4 style="color: #000000;">{{ $school->preferred_repayment_period ?? 'N/A' }}</h4>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-1 text-muted">Monthly Installment</p>
                                        <h4 style="color: #000000;">UGX {{ number_format($school->proposed_monthly_installment ?? 0) }}</h4>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-1 text-muted">Previous Loans</p>
                                        <h4 style="color: #000000;">{{ $school->has_received_loan_before ? 'Yes' : 'No' }}</h4>
                                    </div>
                                </div>
                                @if($school->loan_purpose)
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <p class="mb-1 text-muted">Purpose</p>
                                        <p style="color: #000000;">{{ $school->loan_purpose }}</p>
                                    </div>
                                </div>
                                @endif
                                @if($school->has_received_loan_before && $school->previous_loan_details)
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <p class="mb-1 text-muted">Previous Loan Details</p>
                                        <p style="color: #000000;">{{ $school->previous_loan_details }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Supporting Documents -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Supporting Documents</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Registration Certificate
                                                @if($school->registration_certificate_path)
                                                    <a href="{{ Storage::url($school->registration_certificate_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                School License
                                                @if($school->school_license_path)
                                                    <a href="{{ Storage::url($school->school_license_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Audited Financial Statements
                                                @if($school->audited_statements_path)
                                                    <a href="{{ Storage::url($school->audited_statements_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Bank Statements
                                                @if($school->bank_statements_path)
                                                    <a href="{{ Storage::url($school->bank_statements_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Owner National ID
                                                @if($school->owner_national_id_path)
                                                    <a href="{{ Storage::url($school->owner_national_id_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Land Title
                                                @if($school->land_title_path)
                                                    <a href="{{ Storage::url($school->land_title_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Existing Loan Agreements
                                                @if($school->existing_loan_agreements_path)
                                                    <a href="{{ Storage::url($school->existing_loan_agreements_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-download"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-warning text-dark">Not Uploaded</span>
                                                @endif
                                            </li>
                                            @if($school->license_copy_path)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                License Copy
                                                <a href="{{ Storage::url($school->license_copy_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="mdi mdi-download"></i> View
                                                </a>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Timeline -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">Timeline</h4>
                                <ul class="timeline">
                                    <li>
                                        <p class="mb-0"><strong>Registered:</strong> {{ $school->created_at->format('d M Y, h:i A') }}</p>
                                    </li>
                                    @if($school->assessment_complete)
                                    <li>
                                        <p class="mb-0"><strong>Assessment Completed:</strong> {{ $school->updated_at->format('d M Y, h:i A') }}</p>
                                    </li>
                                    @endif
                                </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject School Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.schools.reject', $school) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" name="rejection_reason" rows="4" required placeholder="Please provide a detailed reason for rejecting this school..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Suspend School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.schools.suspend', $school) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Suspension</label>
                        <textarea class="form-control" name="suspension_reason" rows="4" required placeholder="Please provide a detailed reason for suspending this school..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Suspend School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Custom gap utility for flex wrapping */
.gap-1 {
    gap: 0.25rem !important;
}

/* Ensure text wraps properly in table cells */
.text-wrap {
    word-wrap: break-word;
    white-space: normal;
}

/* Make tables more compact */
.table-sm th, .table-sm td {
    padding: 0.5rem;
    font-size: 0.875rem;
}

/* Ensure cards have equal height in rows */
.h-100 {
    height: 100%;
}

.timeline {
    list-style: none;
    padding-left: 0;
}
.timeline li {
    padding: 10px 0;
    border-left: 2px solid #007bff;
    padding-left: 20px;
    margin-left: 10px;
    position: relative;
}
.timeline li:before {
    content: '';
    width: 12px;
    height: 12px;
    background: #007bff;
    border: 2px solid #fff;
    border-radius: 50%;
    position: absolute;
    left: -7px;
    top: 15px;
}
</style>
@endsection
