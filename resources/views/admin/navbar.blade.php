@php
    $navbarTableExists = fn (string $table): bool => \Illuminate\Support\Facades\Schema::hasTable($table);

    $navbarSelfApplicationsCount = $navbarTableExists('client_loan_applications')
        ? \Illuminate\Support\Facades\DB::table('client_loan_applications')
            ->whereIn('status', ['pending_fo_verification', 'pending_scoring', 'pending_fo_review'])
            ->count()
        : 0;

    $navbarPendingApprovalsCount = 0;
    if ($navbarTableExists('personal_loans')) {
        $navbarPendingApprovalsCount += \Illuminate\Support\Facades\DB::table('personal_loans')->where('status', 0)->count();
    }
    if ($navbarTableExists('group_loans')) {
        $navbarPendingApprovalsCount += \Illuminate\Support\Facades\DB::table('group_loans')->where('status', 0)->count();
    }
    if ($navbarTableExists('school_loans')) {
        $navbarPendingApprovalsCount += \Illuminate\Support\Facades\DB::table('school_loans')->where('status', 0)->count();
    }
    if ($navbarTableExists('student_loans')) {
        $navbarPendingApprovalsCount += \Illuminate\Support\Facades\DB::table('student_loans')->where('status', 0)->count();
    }
    if ($navbarTableExists('staff_loans')) {
        $navbarPendingApprovalsCount += \Illuminate\Support\Facades\DB::table('staff_loans')->where('status', 0)->count();
    }

    $navbarDueTodayCount = $navbarTableExists('loan_schedules')
        ? \Illuminate\Support\Facades\DB::table('loan_schedules')
            ->whereDate('payment_date', today())
            ->where('status', '!=', 1)
            ->count()
        : 0;

    $navbarNotificationTotal = $navbarSelfApplicationsCount + $navbarPendingApprovalsCount + $navbarDueTodayCount;
@endphp

<!-- partial:partials/_navbar.html -->
<nav class="navbar p-0 fixed-top d-flex flex-row" style="background: #ffffff !important; height: 64px !important; box-shadow: 0 1px 6px rgba(17,24,39,0.08) !important; border-bottom: 1px solid #e5e7eb !important;">
    <!-- Mobile Brand -->
    <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center" style="background: #ffffff !important; width: 260px !important; border-right: 1px solid #e5e7eb !important;">
        <a class="custom-brand" href="{{ url('admin/home') }}">
            <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="Emuria" style="max-height: 40px; width: auto;">
        </a>
    </div>
    
    <!-- Main Navbar Content -->
    <div class="navbar-menu-wrapper flex-grow d-flex align-items-center" style="padding: 0 1.5rem !important;">
        <!-- Sidebar Toggle (Hidden as per requirement) -->
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize" style="display: none !important;">
            <span class="mdi mdi-menu"></span>
        </button>
        
        <!-- Search Bar -->
        <div class="d-none d-lg-flex align-items-center flex-grow-1 ebims-navbar-search" style="max-width: 600px; margin-left: 2rem;">
            <form class="w-100" id="globalSearchForm" style="margin: 0;">
                <div class="input-group" style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 2px;">
                    <span class="input-group-text" style="background: transparent; border: none; color: #111827;">
                        <i class="mdi mdi-magnify"></i>
                    </span>
                    <input type="text" class="form-control" id="globalSearchInput" autocomplete="off" placeholder="Search pages, reports, ledgers, settings..." 
                           style="background: transparent; border: none; color: #111827 !important; padding: 0.5rem 1rem;">
                </div>
                <div class="ebims-global-search-results" id="globalSearchResults" aria-live="polite"></div>
            </form>
        </div>
        
        <!-- Right Side Items -->
        <ul class="navbar-nav navbar-nav-right ms-auto d-flex align-items-center">
            <!-- Quick Actions Button -->
            <li class="nav-item dropdown d-none d-lg-block me-3">
                <a class="nav-link btn create-new-button" 
                   id="createbuttonDropdown" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false" 
                   href="#"
                   style="background: #2563eb !important;
                          color: white !important;
                          padding: 0.5rem 1.25rem !important;
                          border-radius: 6px !important;
                          font-weight: 500 !important;
                          font-size: 0.875rem !important;
                          border: none !important;
                          box-shadow: none !important;
                          transition: all 0.3s ease !important;">
                    <i class="mdi mdi-plus-circle me-1"></i> QUICK ACTIONS
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" 
                     aria-labelledby="createbuttonDropdown"
                     style="border-radius: 10px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; min-width: 250px !important;">
                    <h6 class="p-3 mb-0 fw-bold" style="color: #333; border-bottom: 1px solid #e9ecef;">Quick Actions</h6>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.loans.active', ['type' => 'personal']) }}" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-bank text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">Active Loans</p>
                            <small class="text-muted">Loan responsibility and follow-up</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.client-applications.index') }}" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-file-account text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">Self Applications</p>
                            <small class="text-muted">FO verification and scoring queue</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.umra.dashboard') }}" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-chart-donut text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">UMRA Reports</p>
                            <small class="text-muted">Risk and compliance dashboard</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.accounting.journal-entries') }}" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-book-open-page-variant text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">Ledgers</p>
                            <small class="text-muted">Journal entries and GL records</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.expenditures.index') }}" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-cash-multiple text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">Expenditures</p>
                            <small class="text-muted">Expenses and payout rollout</small>
                        </div>
                    </a>
                </div>
            </li>
            
            <!-- Grid Icon -->
            <li class="nav-item nav-settings d-none d-lg-block me-2">
                <a class="nav-link d-flex align-items-center justify-content-center" 
                   href="#"
                   style="width: 40px; 
                          height: 40px; 
                          background: #f3f4f6;
                          border: 1px solid #e5e7eb;
                          border-radius: 50%; 
                          color: #111827 !important;
                          transition: all 0.3s ease !important;">
                    <i class="mdi mdi-view-grid" style="font-size: 1.25rem;"></i>
                </a>
            </li>
            
            <!-- Messages -->
            <li class="nav-item dropdown me-2">
                <a class="nav-link count-indicator d-flex align-items-center justify-content-center" 
                   id="messageDropdown" 
                   href="#" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false"
                   style="width: 40px; 
                          height: 40px; 
                          background: #f3f4f6;
                          border: 1px solid #e5e7eb;
                          border-radius: 50%; 
                          color: #111827 !important;
                          position: relative;
                          transition: all 0.3s ease !important;">
                    <i class="mdi mdi-email" style="font-size: 1.25rem;"></i>
                    <span class="count bg-success" style="position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; border-radius: 50%;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" 
                     aria-labelledby="messageDropdown"
                     style="border-radius: 10px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; min-width: 300px !important;">
                    <h6 class="p-3 mb-0 fw-bold" style="color: #333; border-bottom: 1px solid #e9ecef;">Messages</h6>
                    <a class="dropdown-item preview-item" style="padding: 15px 20px !important;">
                        <div class="text-center w-100">
                            <i class="mdi mdi-email-outline text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0 mt-2">No new messages</p>
                        </div>
                    </a>
                </div>
            </li>
            
            <!-- Notifications -->
            <li class="nav-item dropdown me-3">
                <a class="nav-link count-indicator d-flex align-items-center justify-content-center" 
                   id="notificationDropdown" 
                   href="#" 
                   data-bs-toggle="dropdown"
                   style="width: 40px; 
                          height: 40px; 
                          background: #f3f4f6;
                          border: 1px solid #e5e7eb;
                          border-radius: 50%; 
                          color: #111827 !important;
                          position: relative;
                          transition: all 0.3s ease !important;">
                    <i class="mdi mdi-bell {{ $navbarNotificationTotal > 0 ? 'text-danger' : '' }}" style="font-size: 1.25rem;"></i>
                    @if($navbarNotificationTotal > 0)
                        <span class="navbar-alert-count">{{ $navbarNotificationTotal > 99 ? '99+' : $navbarNotificationTotal }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" 
                     aria-labelledby="notificationDropdown"
                     style="border-radius: 10px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; min-width: 350px !important;">
                    <h6 class="p-3 mb-0 fw-bold d-flex justify-content-between align-items-center" style="color: #333; border-bottom: 1px solid #e9ecef;">
                        <span>Notifications</span>
                        @if($navbarNotificationTotal > 0)
                            <span class="badge bg-danger">{{ number_format($navbarNotificationTotal) }}</span>
                        @endif
                    </h6>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.client-applications.index') }}" style="padding: 12px 20px !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon {{ $navbarSelfApplicationsCount > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-file-account text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content flex-grow-1">
                            <p class="preview-subject mb-1 fw-medium">Self Applications</p>
                            <p class="text-muted small mb-0">{{ number_format($navbarSelfApplicationsCount) }} needing FO/scoring attention</p>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.loans.approvals') }}" style="padding: 12px 20px !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon {{ $navbarPendingApprovalsCount > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-clock text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content flex-grow-1">
                            <p class="preview-subject mb-1 fw-medium">Pending Approvals</p>
                            <p class="text-muted small mb-0">{{ number_format($navbarPendingApprovalsCount) }} loans awaiting approval</p>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="{{ route('admin.loans.active', ['type' => 'personal', 'status' => 'due_today']) }}" style="padding: 12px 20px !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon {{ $navbarDueTodayCount > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-calendar-today text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content flex-grow-1">
                            <p class="preview-subject mb-1 fw-medium">Clients Due Today</p>
                            <p class="text-muted small mb-0">{{ number_format($navbarDueTodayCount) }} schedules need repayment follow-up today</p>
                        </div>
                    </a>
                    @if($navbarNotificationTotal === 0)
                        <div class="dropdown-divider m-0"></div>
                        <p class="p-3 mb-0 text-center text-muted fw-medium">No items need attention right now</p>
                    @endif
                </div>
            </li>
            
            <!-- User Profile Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link d-flex align-items-center" 
                   href="#" 
                   id="userDropdown" 
                   role="button" 
                   data-bs-toggle="dropdown"
                   aria-haspopup="true" 
                   aria-expanded="false" 
                   style="color: #111827 !important;
                          cursor: pointer; 
                          padding: 0.5rem 1rem !important;
                          background: #f3f4f6;
                          border: 1px solid #e5e7eb;
                          border-radius: 25px;
                          transition: all 0.3s ease !important;">
                    <div class="d-flex align-items-center">
                        <img src="{{ Auth::user()->profile_photo_url ?? asset('admin/assets/images/faces/face4.jpg') }}" 
                             alt="Profile" 
                             class="rounded-circle me-2" 
                             width="32" 
                             height="32"
                             style="border: 2px solid #d1d5db;">
                        <span class="d-none d-xl-inline fw-medium">{{ Auth::user()->name ?? 'Super Admin' }}</span>
                        <i class="mdi mdi-chevron-down ms-2"></i>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end" 
                     aria-labelledby="userDropdown"
                     style="min-width: 250px !important; 
                            border-radius: 10px !important;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                            border: 1px solid #e5e7eb !important;
                            margin-top: 10px !important;">
                    <div class="dropdown-header" style="background: #f8f9fa; padding: 15px 20px; border-radius: 10px 10px 0 0;">
                        <div class="d-flex align-items-center">
                            <img src="{{ Auth::user()->profile_photo_url ?? asset('admin/assets/images/faces/face4.jpg') }}" 
                                 alt="Profile" 
                                 class="rounded-circle me-3" 
                                 width="48" 
                                 height="48"
                                 style="border: 2px solid #4fd1c7;">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #333;">{{ Auth::user()->name ?? 'User' }}</h6>
                                <small class="text-muted">{{ Auth::user()->email ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item d-flex align-items-center" 
                       href="{{ route('profile.show') }}" 
                       style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <i class="mdi mdi-account-outline me-3" style="font-size: 1.25rem; color: #4299e1;"></i>
                        <div>
                            <span class="d-block">My Profile</span>
                            @if(Auth::user()->email && str_ends_with(Auth::user()->email, '@ebims.local'))
                                <small class="text-warning d-block" style="font-size: 0.75rem;">
                                    <i class="mdi mdi-lock-alert"></i> Change your password
                                </small>
                            @endif
                        </div>
                    </a>
                    @if(Auth::user()->isSuperAdmin())
                        <a class="dropdown-item d-flex align-items-center"
                           href="{{ route('admin.settings.dashboard') }}"
                           style="padding: 12px 20px !important; transition: background 0.2s !important;">
                            <i class="mdi mdi-settings-outline me-3" style="font-size: 1.25rem; color: #48bb78;"></i>
                            <span>Settings</span>
                        </a>
                    @endif
                    <a class="dropdown-item d-flex align-items-center" 
                       href="#" 
                       style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <i class="mdi mdi-help-circle-outline me-3" style="font-size: 1.25rem; color: #ed8936;"></i>
                        <span>Help & Support</span>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <form method="POST" action="{{ route('logout') }}" class="mb-0">
                        @csrf
                        <button type="submit" 
                                class="dropdown-item d-flex align-items-center" 
                                style="padding: 12px 20px !important; 
                                       background: #ffffff;
                                       border: none; 
                                       width: 100%; 
                                       cursor: pointer; 
                                       transition: background 0.2s !important;
                                       color: #dc3545 !important;">
                            <i class="mdi mdi-logout me-3" style="font-size: 1.25rem;"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </li>
        </ul>
        
        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-format-line-spacing"></span>
        </button>
    </div>
</nav>

<style>
/* Navbar Hover Effects */
.navbar .nav-link:hover {
    background: #eef6ff !important;
    color: #111827 !important;
}

.navbar .create-new-button:hover {
    background: #1d4ed8 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.18) !important;
}

/* Dropdown Item Hover */
.dropdown-item:hover {
    background-color: #f8f9fa !important;
    color: #111827 !important;
}

.navbar .dropdown-menu {
    background: #ffffff !important;
    color: #111827 !important;
    border: 1px solid #e5e7eb !important;
}

.navbar .dropdown-item,
.navbar .dropdown-item span,
.navbar .dropdown-item p,
.navbar .dropdown-item small {
    color: #111827 !important;
}

.navbar .dropdown-item:focus,
.navbar .dropdown-item:active,
.navbar .dropdown-item.active {
    background: #e5e7eb !important;
    color: #111827 !important;
}

.navbar form .dropdown-item[type="submit"],
.navbar form .dropdown-item[type="submit"] span {
    color: #b91c1c !important;
}

.navbar form .dropdown-item[type="submit"]:hover,
.navbar form .dropdown-item[type="submit"]:focus {
    background: #fee2e2 !important;
    color: #991b1b !important;
}

.navbar-alert-count {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #dc2626;
    border: 2px solid #ffffff;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    line-height: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Search Input Placeholder */
.navbar input::placeholder {
    color: #6b7280 !important;
}

.ebims-navbar-search {
    position: relative;
}

.ebims-navbar-search form {
    position: relative;
}

.ebims-global-search-results {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
    display: none;
    left: 0;
    max-height: 420px;
    overflow-y: auto;
    position: absolute;
    right: 0;
    top: calc(100% + 0.45rem);
    z-index: 1060;
}

.ebims-global-search-results.is-open {
    display: block;
}

.ebims-search-state,
.ebims-search-result {
    align-items: center;
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0.9rem;
}

.ebims-search-state {
    color: #64748b;
    font-size: 0.86rem;
}

.ebims-search-result {
    color: #111827 !important;
    text-decoration: none !important;
    border-bottom: 1px solid #f1f5f9;
}

.ebims-search-result:last-child {
    border-bottom: 0;
}

.ebims-search-result:hover,
.ebims-search-result:focus,
.ebims-search-result.is-active {
    background: #f8fafc;
}

.ebims-search-icon {
    align-items: center;
    background: #e0f2fe;
    border-radius: 6px;
    color: #0369a1;
    display: inline-flex;
    flex: 0 0 34px;
    height: 34px;
    justify-content: center;
    width: 34px;
}

.ebims-search-title {
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.2;
}

.ebims-search-subtitle {
    color: #64748b;
    font-size: 0.76rem;
    line-height: 1.25;
    margin-top: 0.1rem;
}

.ebims-search-type {
    color: #2563eb;
    flex: 0 0 auto;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0;
    margin-left: auto;
    text-transform: uppercase;
}

/* Ensure navbar stays on top */
.navbar.fixed-top {
    z-index: 1030 !important;
}

/* Add margin to main content to account for fixed navbar */
.main-panel {
    padding-top: 64px !important;
}

@media (max-width: 991px) {
    .navbar .navbar-brand-wrapper {
        width: auto !important;
        min-width: 150px !important;
        padding: 0 0.5rem !important;
    }

    .navbar .navbar-menu-wrapper {
        padding: 0 0.5rem !important;
    }

    .navbar .navbar-nav-right .nav-item {
        margin-right: 0.25rem !important;
    }

    .navbar .navbar-nav-right .nav-link {
        padding: 0.35rem !important;
    }

    .navbar .dropdown-menu {
        position: fixed !important;
        top: 70px !important;
        left: 0.75rem !important;
        right: 0.75rem !important;
        min-width: auto !important;
        max-width: none !important;
    }
}

@media (max-width: 575px) {
    .navbar #messageDropdown,
    .navbar #notificationDropdown {
        display: none !important;
    }
}

/* Force dropdown to work */
.dropdown-menu.show {
    display: block !important;
}
</style>

<script>
(function() {
    const searchUrl = @json(route('admin.global-search'));
    let activeIndex = -1;
    let lastResults = [];
    let searchTimer = null;
    let currentController = null;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openResults(resultsBox) {
        resultsBox.classList.add('is-open');
    }

    function closeResults(resultsBox) {
        resultsBox.classList.remove('is-open');
        activeIndex = -1;
    }

    function renderState(resultsBox, message) {
        resultsBox.innerHTML = '<div class="ebims-search-state">' + escapeHtml(message) + '</div>';
        openResults(resultsBox);
    }

    function setActiveResult(resultsBox, index) {
        const links = Array.from(resultsBox.querySelectorAll('.ebims-search-result'));
        links.forEach((link) => link.classList.remove('is-active'));

        if (!links.length) {
            activeIndex = -1;
            return;
        }

        activeIndex = Math.max(0, Math.min(index, links.length - 1));
        links[activeIndex].classList.add('is-active');
        links[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    function renderResults(resultsBox, results) {
        lastResults = results || [];
        activeIndex = -1;

        if (!lastResults.length) {
            renderState(resultsBox, 'No matching pages or modules found.');
            return;
        }

        resultsBox.innerHTML = lastResults.map(function(result, index) {
            return [
                '<a class="ebims-search-result" href="', escapeHtml(result.url), '" data-index="', index, '">',
                    '<span class="ebims-search-icon"><i class="mdi ', escapeHtml(result.icon || 'mdi-magnify'), '"></i></span>',
                    '<span class="flex-grow-1">',
                        '<span class="ebims-search-title">', escapeHtml(result.title), '</span>',
                        '<span class="ebims-search-subtitle">', escapeHtml(result.subtitle), '</span>',
                    '</span>',
                    '<span class="ebims-search-type">', escapeHtml(result.type), '</span>',
                '</a>'
            ].join('');
        }).join('');

        openResults(resultsBox);
    }

    function performSearch(input, resultsBox) {
        const query = input.value.trim();

        if (query.length < 2) {
            resultsBox.innerHTML = '';
            closeResults(resultsBox);
            return;
        }

        if (currentController) {
            currentController.abort();
        }

        currentController = new AbortController();
        renderState(resultsBox, 'Searching...');

        fetch(searchUrl + '?q=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json' },
            signal: currentController.signal
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Search failed');
                }

                return response.json();
            })
            .then(function(payload) {
                renderResults(resultsBox, payload.results || []);
            })
            .catch(function(error) {
                if (error.name === 'AbortError') {
                    return;
                }

                renderState(resultsBox, 'Search is unavailable right now.');
            });
    }

    function initGlobalSearch() {
        const form = document.getElementById('globalSearchForm');
        const input = document.getElementById('globalSearchInput');
        const resultsBox = document.getElementById('globalSearchResults');

        if (!form || !input || !resultsBox || form.dataset.ebimsSearchReady === '1') {
            return;
        }

        form.dataset.ebimsSearchReady = '1';

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            if (activeIndex >= 0 && lastResults[activeIndex]) {
                window.location.href = lastResults[activeIndex].url;
                return;
            }

            if (lastResults[0]) {
                window.location.href = lastResults[0].url;
            } else {
                performSearch(input, resultsBox);
            }
        });

        input.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                performSearch(input, resultsBox);
            }, 250);
        });

        input.addEventListener('keydown', function(event) {
            const links = resultsBox.querySelectorAll('.ebims-search-result');

            if (event.key === 'Escape') {
                closeResults(resultsBox);
                return;
            }

            if (!links.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActiveResult(resultsBox, activeIndex + 1);
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActiveResult(resultsBox, activeIndex <= 0 ? links.length - 1 : activeIndex - 1);
            }
        });

        resultsBox.addEventListener('mouseover', function(event) {
            const link = event.target.closest('.ebims-search-result');
            if (link) {
                setActiveResult(resultsBox, Number(link.dataset.index || 0));
            }
        });

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.ebims-navbar-search')) {
                closeResults(resultsBox);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalSearch);
    } else {
        initGlobalSearch();
    }
})();
</script>

<script>
// IMMEDIATE dropdown toggle - no waiting
(function() {
    console.log('🔧 Navbar dropdown fix loading...');
    
    function initDropdown() {
        const userDropdown = document.getElementById('userDropdown');
        const dropdownMenu = userDropdown ? userDropdown.nextElementSibling : null;
        
        if (!userDropdown || !dropdownMenu) {
            console.log('⏳ Waiting for dropdown elements...');
            setTimeout(initDropdown, 100);
            return;
        }
        
        console.log('✅ Found dropdown elements, attaching handlers');
        
        // Remove any existing listeners
        const newDropdown = userDropdown.cloneNode(true);
        userDropdown.parentNode.replaceChild(newDropdown, userDropdown);
        
        // Get fresh references
        const trigger = document.getElementById('userDropdown');
        const menu = trigger.nextElementSibling;
        
        // Add click handler
        trigger.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🖱️ Dropdown clicked!');
            
            const isVisible = menu.style.display === 'block';
            
            // Close all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(function(m) {
                m.style.display = 'none';
                m.classList.remove('show');
            });
            
            // Toggle this one
            if (!isVisible) {
                menu.style.display = 'block';
                menu.classList.add('show');
                console.log('✅ Dropdown opened');
            } else {
                menu.style.display = 'none';
                menu.classList.remove('show');
                console.log('❌ Dropdown closed');
            }
        };
        
        // Close on outside click
        document.onclick = function(e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = 'none';
                menu.classList.remove('show');
            }
        };
        
        console.log('✅ Dropdown ready!');
    }
    
    // Try immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropdown);
    } else {
        initDropdown();
    }
})();
</script>

<script>
(function() {
    function closeActionDropdowns(exceptMenu) {
        document.querySelectorAll('#createbuttonDropdown, #messageDropdown, #notificationDropdown').forEach(function(toggle) {
            const menu = toggle.parentElement ? toggle.parentElement.querySelector('.dropdown-menu') : null;
            if (!menu || menu === exceptMenu) {
                return;
            }

            menu.classList.remove('show');
            menu.style.display = 'none';
            toggle.setAttribute('aria-expanded', 'false');
        });
    }

    function initActionDropdown(toggleId) {
        const toggle = document.getElementById(toggleId);
        const menu = toggle && toggle.parentElement ? toggle.parentElement.querySelector('.dropdown-menu') : null;

        if (!toggle || !menu || toggle.dataset.ebimsDropdownReady === '1') {
            return;
        }

        toggle.dataset.ebimsDropdownReady = '1';
        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            const willOpen = !menu.classList.contains('show');
            closeActionDropdowns(menu);
            menu.classList.toggle('show', willOpen);
            menu.style.display = willOpen ? 'block' : 'none';
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    }

    function initNavbarActionDropdowns() {
        initActionDropdown('createbuttonDropdown');
        initActionDropdown('messageDropdown');
        initActionDropdown('notificationDropdown');
    }

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#createbuttonDropdown, #messageDropdown, #notificationDropdown, .navbar .dropdown-menu')) {
            closeActionDropdowns();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeActionDropdowns();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbarActionDropdowns);
    } else {
        initNavbarActionDropdowns();
    }
})();
</script>
