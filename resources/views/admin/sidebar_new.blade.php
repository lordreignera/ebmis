<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-flex align-items-center justify-content-center">
    <a class="sidebar-brand brand-logo" href="{{ url('admin/home') }}">
      <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS" style="max-height: 50px; width: auto;" />
    </a>
    <a class="sidebar-brand brand-logo-mini" href="{{ url('admin/home') }}">
      <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS" style="max-height: 40px; width: auto;" />
    </a>
  </div>
  
  <ul class="nav">
    <li class="nav-item nav-category">
      <span class="nav-link">EBIMS MAIN NAVIGATION</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ auth()->user()->user_type === 'school' ? route('school.dashboard') : url('admin/home') }}">
        <span class="menu-icon">
          <i class="mdi mdi-speedometer"></i>
        </span>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    @if(auth()->user()->hasRole('Super Administrator') || auth()->user()->hasRole('superadmin'))
    <!-- SUPER ADMIN ONLY: SCHOOL MANAGEMENT -->
    <li class="nav-item nav-category">
      <span class="nav-link">SCHOOL MANAGEMENT</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ url('admin/schools/dashboard') }}">
        <span class="menu-icon">
          <i class="mdi mdi-school"></i>
        </span>
        <span class="menu-title">Schools Overview</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#schools" aria-expanded="false" aria-controls="schools">
        <span class="menu-icon">
          <i class="mdi mdi-domain"></i>
        </span>
        <span class="menu-title">School Management</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="schools">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.schools.index') }}">All Schools</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.schools.index') }}?status=pending">Pending School Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.schools.index') }}?status=approved">Active Schools</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.schools.index') }}?status=suspended">Suspended Schools</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.schools.index') }}?status=rejected">Rejected Schools</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#school-loans" aria-expanded="false" aria-controls="school-loans">
        <span class="menu-icon">
          <i class="mdi mdi-bank"></i>
        </span>
        <span class="menu-title">School Loans</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="school-loans">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><strong class="text-muted ps-3">Create School Loans</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=school&period=daily">School Loan (Daily)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=school&period=weekly">School Loan (Weekly)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=school&period=monthly">School Loan (Monthly)</a></li>
          
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">School Loan Management</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.approvals') }}?type=school">Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.disbursements') }}?type=school">Pending Disbursements</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.active') }}?type=school">Active School Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.repayments.index') }}?type=school">School Loan Repayments</a></li>
          
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.portfolio') }}?type=school">School Loan Portfolio</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#student-loans" aria-expanded="false" aria-controls="student-loans">
        <span class="menu-icon">
          <i class="mdi mdi-account-school"></i>
        </span>
        <span class="menu-title">Student Loans</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="student-loans">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><strong class="text-muted ps-3">Create Student Loans</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=student&period=daily">Student Loan (Daily)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=student&period=weekly">Student Loan (Weekly)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=student&period=monthly">Student Loan (Monthly)</a></li>
          
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">Student Loan Management</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.approvals') }}?type=student">Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.disbursements') }}?type=student">Pending Disbursements</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.active') }}?type=student">Active Student Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.repayments.index') }}?type=student">Student Loan Repayments</a></li>
          
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.portfolio') }}?type=student">Student Loan Portfolio</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Students by School</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#school-advances" aria-expanded="false" aria-controls="school-advances">
        <span class="menu-icon">
          <i class="mdi mdi-cash-fast"></i>
        </span>
        <span class="menu-title">School Advances</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="school-advances">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Create School Advance</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Advance Applications</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Advances Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Advances Pending Disbursements</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Advance Repayments</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Active Advances</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Cleared Advances</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#staff-loans" aria-expanded="false" aria-controls="staff-loans">
        <span class="menu-icon">
          <i class="mdi mdi-account-tie"></i>
        </span>
        <span class="menu-title">Staff Loans</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="staff-loans">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><strong class="text-muted ps-3">Create Staff Loans</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=staff&period=daily">Staff Loan (Daily)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=staff&period=weekly">Staff Loan (Weekly)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.create') }}?type=staff&period=monthly">Staff Loan (Monthly)</a></li>
          
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">Staff Loan Management</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.approvals') }}?type=staff">Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.disbursements') }}?type=staff">Pending Disbursements</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.active') }}?type=staff">Active Staff Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.repayments.index') }}?type=staff">Staff Loan Repayments</a></li>
          
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.school.loans.portfolio') }}?type=staff">Staff Loan Portfolio</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Staff by School</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#payroll" aria-expanded="false" aria-controls="payroll">
        <span class="menu-icon">
          <i class="mdi mdi-cash-multiple"></i>
        </span>
        <span class="menu-title">Payroll Management</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="payroll">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Process Payroll</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Payroll Schedules</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Teacher Payroll</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Staff Salaries</a></li>
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="#">Payroll by School</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Monthly Payroll Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Payroll History</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Payroll Deductions</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Tax Reports</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#school-reports" aria-expanded="false" aria-controls="school-reports">
        <span class="menu-icon">
          <i class="mdi mdi-chart-bar"></i>
        </span>
        <span class="menu-title">School Reports</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="school-reports">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Schools Performance Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">School Loans Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Student Loans Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">School Advances Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Staff Loans Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Payroll Summary Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">School Payments Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Students by School Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">School Staff Report</a></li>
        </ul>
      </div>
    </li>
    @endif
    <!-- END SUPER ADMIN ONLY: SCHOOL MANAGEMENT -->

    <!-- SCHOOL PORTAL SECTION - Only visible to school users -->
    @if(auth()->user()->user_type === 'school')
    <li class="nav-item nav-category">
      <span class="nav-link">MY SCHOOL</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ url('school/dashboard') }}">
        <span class="menu-icon">
          <i class="mdi mdi-view-dashboard"></i>
        </span>
        <span class="menu-title">My School Dashboard</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('school.classes.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-google-classroom"></i>
        </span>
        <span class="menu-title">My Classes</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#my-students" aria-expanded="false" aria-controls="my-students">
        <span class="menu-icon">
          <i class="mdi mdi-school"></i>
        </span>
        <span class="menu-title">My Students</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="my-students">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('school.students.create') }}">Add New Student</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.students.index') }}">All Students</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.students.index') }}?status=active">Active Students</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.students.index') }}?status=graduated">Graduated Students</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.students.index') }}?status=suspended">Suspended Students</a></li>
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.students.template') }}">Download Import Template</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#my-staff" aria-expanded="false" aria-controls="my-staff">
        <span class="menu-icon">
          <i class="mdi mdi-account-tie"></i>
        </span>
        <span class="menu-title">My Staff</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="my-staff">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('school.staff.create') }}">Add New Staff</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.staff.index') }}">All Staff</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.staff.index') }}?staff_type=Teaching">Teaching Staff</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.staff.index') }}?staff_type=Non-Teaching">Non-Teaching Staff</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('school.staff.index') }}?status=active">Active Staff</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#fee-payments" aria-expanded="false" aria-controls="fee-payments">
        <span class="menu-icon">
          <i class="mdi mdi-currency-usd"></i>
        </span>
        <span class="menu-title">Fee Payments</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="fee-payments">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Record Payment</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Students Who Paid</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Students Who Haven't Paid</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Partial Payments</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Payment History</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Fee Structure</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Outstanding Balances</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#my-loans" aria-expanded="false" aria-controls="my-loans">
        <span class="menu-icon">
          <i class="mdi mdi-cash-usd"></i>
        </span>
        <span class="menu-title">My Loans</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="my-loans">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Apply for School Loan</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Apply for Advance</a></li>
          <li class="nav-item"><a class="nav-link" href="#">My Active Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Loan Repayment Schedule</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Make Repayment</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Loan History</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Pending Applications</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#student-loan-requests" aria-expanded="false" aria-controls="student-loan-requests">
        <span class="menu-icon">
          <i class="mdi mdi-account-school"></i>
        </span>
        <span class="menu-title">Student Loan Requests</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="student-loan-requests">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Submit Student Loan Request</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Active Student Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Student Loan Repayments</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Pending Student Requests</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#my-reports" aria-expanded="false" aria-controls="my-reports">
        <span class="menu-icon">
          <i class="mdi mdi-chart-line"></i>
        </span>
        <span class="menu-title">My Reports</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="my-reports">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">School Financial Summary</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Fee Collection Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Student Enrollment Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Staff Report</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Loan Statement</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Payroll Summary</a></li>
        </ul>
      </div>
    </li>
    @endif
    <!-- END SCHOOL PORTAL SECTION -->

    @if(auth()->user()->user_type !== 'school')
    <!-- EBIMS MODULES (For Super Admin & Branch Managers) -->
    <li class="nav-item nav-category">
      <span class="nav-link">EBIMS MODULES</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#members" aria-expanded="false" aria-controls="members">
        <span class="menu-icon">
          <i class="mdi mdi-account-multiple"></i>
        </span>
        <span class="menu-title">Clients</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="members">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.members.create') }}">Add Client</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.members.index') }}?member_type=1">Individual Clients</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.members.index') }}?member_type=2">Group Clients</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.members.index') }}?member_type=3">Corporate Clients</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.members.pending') }}">Clients Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.bulk-sms.create') }}">Send Bulk SMS</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.bulk-sms.index') }}">View Bulk SMS Records</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#groups" aria-expanded="false" aria-controls="groups">
        <span class="menu-icon">
          <i class="mdi mdi-account-group"></i>
        </span>
        <span class="menu-title">Groups</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="groups">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.groups.create') }}">Create Group</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.groups.index') }}">View Groups</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#cashsecurity" aria-expanded="false" aria-controls="cashsecurity">
        <span class="menu-icon">
          <i class="mdi mdi-shield-lock"></i>
        </span>
        <span class="menu-title">Cash Security</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="cashsecurity">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Add Cash Security</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Withdraw Cash Security</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#investments" aria-expanded="false" aria-controls="investments">
        <span class="menu-icon">
          <i class="mdi mdi-trending-up"></i>
        </span>
        <span class="menu-title">Investments</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="investments">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.index') }}">Investment Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.investors') }}">All Investors</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.create-investor') }}">Add New Investor</a></li>
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.investors') }}?type=local">Local Investors</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.investors') }}?type=international">International Investors</a></li>
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.index') }}?status=active">Active Investments</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.index') }}?status=pending">Pending Investments</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.investments.index') }}?status=matured">Matured Investments</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#loans" aria-expanded="false" aria-controls="loans">
        <span class="menu-icon">
          <i class="mdi mdi-briefcase"></i>
        </span>
        <span class="menu-title">Loan Portfolio</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="loans">
        <ul class="nav flex-column sub-menu">
          <!-- Personal Loans Section -->
          <li class="nav-item"><strong class="text-muted ps-3">Personal Loans</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.create') }}?type=personal&period=daily">Personal Loan (Daily)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.create') }}?type=personal&period=weekly">Personal Loan (Weekly)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.create') }}?type=personal&period=monthly">Personal Loan (Monthly)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.esign') }}">eSign Personal Loan</a></li>
          
          <!-- Group Loans Section -->
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">Group Loans</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.create') }}?type=group&period=daily">Group Loan (Daily)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.create') }}?type=group&period=weekly">Group Loan (Weekly)</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.create') }}?type=group&period=monthly">Group Loan (Monthly)</a></li>
          
          <!-- Personal Loan Management -->
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">Personal Loan Management</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.approvals') }}?type=personal">Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.disbursements.pending') }}?type=personal">Pending Disbursements</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.active') }}?type=personal">Active Personal Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.repayments.index') }}?type=personal">Personal Loan Repayments</a></li>
          
          <!-- Group Loan Management -->
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">Group Loan Management</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.approvals') }}?type=group">Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.disbursements.pending') }}?type=group">Pending Disbursements</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.loans.active') }}?type=group">Active Group Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.repayments.index') }}?type=group">Group Loan Repayments</a></li>
          
          <!-- Loan Portfolio Reports -->
          <li class="nav-item"><hr class="dropdown-divider"></li>
          <li class="nav-item"><strong class="text-muted ps-3">Portfolio Analysis</strong></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.portfolio.branch') }}">By Branch</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.portfolio.product') }}">By Product</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.portfolio.individual') }}">Personal Portfolio</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.portfolio.group') }}">Group Portfolio</a></li>
        </ul>
      </div>
    </li>
    @endif
    <!-- END ADMIN ONLY: EBIMS MODULES -->

    @if(auth()->user()->user_type !== 'school')
    <!-- ADMIN ONLY: REPORTS & SETTINGS -->
    <li class="nav-item nav-category">
      <span class="nav-link">REPORTS & SETTINGS</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#reports" aria-expanded="false" aria-controls="reports">
        <span class="menu-icon">
          <i class="mdi mdi-file-chart-outline"></i>
        </span>
        <span class="menu-title">Reports</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="reports">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.pending-loans') }}">Pending Loan Applications</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.disbursed-loans') }}">Disbursed Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.rejected-loans') }}">Rejected Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.loans-due') }}">Loans Due</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.paid-loans') }}">Paid Loans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.loan-repayments') }}">Loans Repayments</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.payment-transactions') }}">Payments Transactions</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.loan-interest') }}">Loans Interest</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.cash-securities') }}">Cash Securities</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.loan-charges') }}">Loan Charges</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#settings" aria-expanded="false" aria-controls="settings">
        <span class="menu-icon">
          <i class="mdi mdi-cog"></i>
        </span>
        <span class="menu-title">Settings</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="settings">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Add System Users</a></li>
          <li class="nav-item"><a class="nav-link" href="#">View System Users</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Add Field Team</a></li>
          <li class="nav-item"><a class="nav-link" href="#">View Field Team</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Loan Products</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Chart of Accounts</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Savings Products</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Fees Types</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Manage Agencies</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Manage Branches</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Manage Security Code</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#inv-approvals" aria-expanded="false" aria-controls="inv-approvals">
        <span class="menu-icon">
          <i class="mdi mdi-check-decagram"></i>
        </span>
        <span class="menu-title">Investment Approvals</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="inv-approvals">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Pending Approvals</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Approved</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Rejected</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#agency" aria-expanded="false" aria-controls="agency">
        <span class="menu-icon">
          <i class="mdi mdi-domain"></i>
        </span>
        <span class="menu-title">Agency</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="agency">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="#">Opening Balances</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Closing Balances</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Reconciliation</a></li>
        </ul>
      </div>
    </li>

@endif
<!-- END ADMIN ONLY: REPORTS & SETTINGS -->

@if(auth()->user()->hasRole('Super Administrator') || auth()->user()->hasRole('superadmin'))
        <!-- ACCESS CONTROL SECTION - ADMIN ONLY -->
    <li class="nav-item nav-category">
      <span class="nav-link">ACCESS CONTROL</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.access-control.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-shield-check"></i>
        </span>
        <span class="menu-title">Access Control Dashboard</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#access-users" aria-expanded="false" aria-controls="access-users">
        <span class="menu-icon">
          <i class="mdi mdi-account-supervisor"></i>
        </span>
        <span class="menu-title">User Management</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="access-users">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">All Users</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.create') }}">Add New User</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}?filter=super_admin">Super Admins</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}?filter=branch">Branch Users</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}?filter=school">School Users</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}?filter=pending">Pending Approvals</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#access-roles" aria-expanded="false" aria-controls="access-roles">
        <span class="menu-icon">
          <i class="mdi mdi-account-key"></i>
        </span>
        <span class="menu-title">Roles & Permissions</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="access-roles">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.roles.index') }}">All Roles</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.roles.create') }}">Create Role</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.permissions.index') }}">All Permissions</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.permissions.create') }}">Add Permission</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Role Assignments</a></li>
        </ul>
      </div>
    </li>

@endif
<!-- END ADMIN ONLY: ACCESS CONTROL -->

@if(auth()->user()->hasRole('Super Administrator') || auth()->user()->hasRole('superadmin'))
    <!-- SYSTEM SETTINGS SECTION - ADMIN ONLY -->
    <li class="nav-item nav-category">
      <span class="nav-link">SYSTEM SETTINGS</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.settings.dashboard') }}">
        <span class="menu-icon">
          <i class="mdi mdi-settings"></i>
        </span>
        <span class="menu-title">Settings Dashboard</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#organization-settings" aria-expanded="false" aria-controls="organization-settings">
        <span class="menu-icon">
          <i class="mdi mdi-city"></i>
        </span>
        <span class="menu-title">Organization</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="organization-settings">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.agencies') }}">Agency Management</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.branches') }}">Branch Management</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.company-info') }}">Company Information</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#product-settings" aria-expanded="false" aria-controls="product-settings">
        <span class="menu-icon">
          <i class="mdi mdi-package-variant-closed"></i>
        </span>
        <span class="menu-title">Product Settings</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="product-settings">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.loan-products') }}">Loan Products</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.school-loan-products') }}">School Loan Products</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.savings-products') }}">Savings Products</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.fees-products') }}">Fees & Products</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.product-categories') }}">Product Categories</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#account-settings" aria-expanded="false" aria-controls="account-settings">
        <span class="menu-icon">
          <i class="mdi mdi-book-open-variant"></i>
        </span>
        <span class="menu-title">Account Settings</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="account-settings">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.system-accounts') }}">System/Chart Accounts</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.account-types') }}">Account Types</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#security-settings" aria-expanded="false" aria-controls="security-settings">
        <span class="menu-icon">
          <i class="mdi mdi-lock"></i>
        </span>
        <span class="menu-title">Security & Codes</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="security-settings">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.security-codes') }}">Security Codes</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.transaction-codes') }}">Transaction Codes</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.audit-trail') }}">Audit Trail Settings</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#system-config" aria-expanded="false" aria-controls="system-config">
        <span class="menu-icon">
          <i class="mdi mdi-tune"></i>
        </span>
        <span class="menu-title">System Configuration</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="system-config">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.general-config') }}">General Settings</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.email-config') }}">Email Configuration</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.sms-config') }}">SMS Configuration</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.notification-config') }}">Notification Settings</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" data-bs-toggle="collapse" href="#maintenance-tools" aria-expanded="false" aria-controls="maintenance-tools">
        <span class="menu-icon">
          <i class="mdi mdi-hammer-wrench"></i>
        </span>
        <span class="menu-title">Maintenance & Tools</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="maintenance-tools">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.backup') }}">Backup & Restore</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.database-maintenance') }}">Database Maintenance</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.system-logs') }}">System Logs</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.data-import') }}">Data Import/Export</a></li>
        </ul>
      </div>
    </li>

@endif
<!-- END ADMIN ONLY: SYSTEM SETTINGS -->

  </ul>
</nav>

<script>
// Fix sidebar accordion - only one submenu open at a time
document.addEventListener('DOMContentLoaded', function() {
    // Function to manage sidebar menus
    function manageSidebarMenus() {
        const currentPath = window.location.pathname;
        const currentParams = new URLSearchParams(window.location.search);
        const currentType = currentParams.get('type');
        const currentPeriod = currentParams.get('period');
        
        // STEP 1: Close ALL menus first
        const allCollapseMenus = document.querySelectorAll('.sidebar .collapse');
        allCollapseMenus.forEach(function(collapse) {
            collapse.classList.remove('show');
            const collapseId = collapse.getAttribute('id');
            const toggle = document.querySelector(`[data-bs-toggle="collapse"][href="#${collapseId}"]`);
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.add('collapsed');
            }
        });
        
        // STEP 2: Find and open ONLY the correct menu
        const allMenuSections = document.querySelectorAll('.sidebar > .nav > .nav-item.menu-items > .collapse');
        let menuToOpen = null;
        
        allMenuSections.forEach(function(collapse) {
            if (menuToOpen) return; // Already found the menu to open
            
            const submenuLinks = collapse.querySelectorAll('.sub-menu a');
            
            for (let i = 0; i < submenuLinks.length; i++) {
                const link = submenuLinks[i];
                const linkHref = link.getAttribute('href');
                
                if (linkHref) {
                    try {
                        const linkUrl = new URL(linkHref, window.location.origin);
                        const linkPath = linkUrl.pathname;
                        const linkParams = new URLSearchParams(linkUrl.search);
                        const linkType = linkParams.get('type');
                        const linkPeriod = linkParams.get('period');
                        
                        // Check for exact match
                        if (linkPath === currentPath) {
                            // If both have type/period params, they must match exactly
                            if (currentType && linkType) {
                                if (currentType === linkType) {
                                    // Type matches, check period if present
                                    if (currentPeriod && linkPeriod) {
                                        if (currentPeriod === linkPeriod) {
                                            menuToOpen = collapse;
                                            break;
                                        }
                                    } else if (!currentPeriod && !linkPeriod) {
                                        menuToOpen = collapse;
                                        break;
                                    } else if (!currentPeriod || !linkPeriod) {
                                        menuToOpen = collapse;
                                        break;
                                    }
                                }
                            } else if (!currentType && !linkType) {
                                // No type parameter - simple path match
                                menuToOpen = collapse;
                                break;
                            }
                        }
                    } catch (e) {
                        // Invalid URL, skip
                    }
                }
            }
        });
        
        // STEP 3: Open the found menu
        if (menuToOpen) {
            menuToOpen.classList.add('show');
            const collapseId = menuToOpen.getAttribute('id');
            const toggle = document.querySelector(`[data-bs-toggle="collapse"][href="#${collapseId}"]`);
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.remove('collapsed');
            }
        }
    }
    
    // Run immediately
    manageSidebarMenus();
    
    // Run again after a short delay to override any Bootstrap auto-open behavior
    setTimeout(manageSidebarMenus, 100);
    setTimeout(manageSidebarMenus, 500);
    
    // Get only the MAIN menu collapse toggles (not submenu items)
    const mainMenuToggles = document.querySelectorAll('.sidebar > .nav > .nav-item.menu-items > [data-bs-toggle="collapse"]');
    
    mainMenuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            const targetCollapse = document.querySelector(targetId);
            
            if (!targetCollapse) return;
            
            // Get the current state before Bootstrap processes the click
            const isCurrentlyExpanded = targetCollapse.classList.contains('show');
            
            // Close ALL other open collapses in the sidebar FIRST (but not the one being clicked)
            const allCollapses = document.querySelectorAll('.sidebar > .nav > .nav-item.menu-items > .collapse');
            allCollapses.forEach(function(collapse) {
                if (collapse !== targetCollapse && collapse.classList.contains('show')) {
                    // Remove the 'show' class
                    collapse.classList.remove('show');
                    
                    // Find the toggle link for this collapse and update its state
                    const collapseId = collapse.getAttribute('id');
                    const collapseToggle = document.querySelector(`.sidebar [data-bs-toggle="collapse"][href="#${collapseId}"]`);
                    if (collapseToggle) {
                        collapseToggle.setAttribute('aria-expanded', 'false');
                        collapseToggle.classList.add('collapsed');
                    }
                }
            });
        });
    });
    
    // Prevent submenu links from triggering collapse on parent menu
    const submenuLinks = document.querySelectorAll('.sidebar .sub-menu .nav-link');
    submenuLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Stop propagation to prevent parent collapse handlers
            e.stopPropagation();
        });
    });
});
</script>
