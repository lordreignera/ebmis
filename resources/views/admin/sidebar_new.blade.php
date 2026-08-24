<nav class="sidebar sidebar-offcanvas" id="sidebar">
  @php
    $sidebarUser = auth()->user();
    $sidebarCan = fn (string $permission): bool => $sidebarUser->isSuperAdmin() || $sidebarUser->can($permission);
    $sidebarCanAny = fn (array $permissions): bool => collect($permissions)->contains(fn (string $permission): bool => $sidebarCan($permission));
    $sidebarCanManageSensitiveLoanOperations = $sidebarUser->isSuperAdmin()
        || in_array($sidebarUser->user_type, ['administrator', 'admin'], true)
        || $sidebarUser->hasRole(['Administrator', 'admin']);
    $sidebarCanManageStaffPaymentRollout = $sidebarUser->canManageStaffPaymentRollout();
  @endphp

  <div class="sidebar-brand-wrapper d-flex align-items-center justify-content-center">
    <a class="sidebar-brand brand-logo" href="{{ url('admin/home') }}">
      <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS" style="max-height: 50px; width: auto;" />
    </a>
    <a class="sidebar-brand brand-logo-mini" href="{{ url('admin/home') }}">
      <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS" style="max-height: 40px; width: auto;" />
    </a>
  </div>

  <div class="sidebar-menu-tools">
    <div class="sidebar-menu-search">
      <i class="mdi mdi-magnify"></i>
      <input type="search" id="sidebar-menu-search-input" placeholder="Find menu item" autocomplete="off">
      <button type="button" id="sidebar-menu-search-clear" aria-label="Clear menu search">
        <i class="mdi mdi-close"></i>
      </button>
    </div>
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
      <a class="nav-link" href="{{ route('admin.schools.dashboard') }}">
        <span class="menu-icon">
          <i class="mdi mdi-school"></i>
        </span>
        <span class="menu-title">School Management</span>
      </a>
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

    @if(auth()->user()->user_type !== 'school' && $sidebarCan('access-ebmis-modules'))
    <!-- EBIMS MODULES (For operational EBIMS staff) -->
    <li class="nav-item nav-category">
      <span class="nav-link">EBIMS MODULES</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.modules.dashboard') }}">
        <span class="menu-icon">
          <i class="mdi mdi-apps"></i>
        </span>
        <span class="menu-title">All EBIMS Modules</span>
      </a>
    </li>

    @if($sidebarCanAny(['view-client-details', 'add-client', 'send-sms-notifications', 'manage-group-members']))
    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.modules.clients') }}">
        <span class="menu-icon">
          <i class="mdi mdi-account-multiple"></i>
        </span>
        <span class="menu-title">Clients Module</span>
      </a>
    </li>
    @endif

    @if($sidebarCanAny(['create-loan-application', 'manage-client-applications', 'manage-loans', 'view-disbursements', 'view-active-loans', 'view-repayment-history', 'manage-late-fees', 'generate-loan-reports']))
    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.modules.loan-portfolio') }}">
        <span class="menu-icon">
          <i class="mdi mdi-briefcase"></i>
        </span>
        <span class="menu-title">Loan Portfolio</span>
      </a>
    </li>
    @endif

    @if($sidebarCanAny(['view-repayment-history', 'view-active-loans', 'manage-late-fees', 'manage-fees', 'manage-cash-securities', 'manage-savings-accounts']))
    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.modules.collections') }}">
        <span class="menu-icon">
          <i class="mdi mdi-wallet"></i>
        </span>
        <span class="menu-title">Payments & Collections</span>
      </a>
    </li>
    @endif

    @if($sidebarCanAny(['generate-loan-reports', 'view-accounting-reports', 'view-umra-reports']))
    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.modules.reports-accounting') }}">
        <span class="menu-icon">
          <i class="mdi mdi-file-chart-outline"></i>
        </span>
        <span class="menu-title">Reports & Accounting</span>
      </a>
    </li>
    @endif

    @if($sidebarCan('manage-investments'))
    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('admin.modules.investments') }}">
        <span class="menu-icon">
          <i class="mdi mdi-trending-up"></i>
        </span>
        <span class="menu-title">Investments Module</span>
      </a>
    </li>
    @endif

    <!-- END ADMIN ONLY: EBIMS MODULES -->

    @endif

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

@if(auth()->user()->isSuperAdmin() || auth()->user()->isAdministrator())
    <!-- SYSTEM SETTINGS SECTION - SUPER ADMIN + ADMINISTRATOR ONLY -->
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

@endif
<!-- END SUPER ADMIN + ADMINISTRATOR ONLY: SYSTEM SETTINGS -->

  </ul>

  <div class="sidebar-help-panel">
    <a href="{{ route('admin.help.guide') }}" class="sidebar-help-link">
      <span class="sidebar-help-icon"><i class="mdi mdi-help-circle-outline"></i></span>
      <span>
        <strong>Need help?</strong>
        <small>Open EBIMS user guide</small>
      </span>
    </a>
  </div>
</nav>

<style>
.sidebar-menu-tools {
  position: relative;
  z-index: 3;
  padding: 10px 18px 6px;
}

.sidebar-menu-search {
  align-items: center;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  display: flex;
  min-height: 38px;
  padding: 0 8px;
  pointer-events: auto;
  position: relative;
}

.sidebar-menu-search i {
  color: #9aa4b2;
  font-size: 18px;
  line-height: 1;
}

.sidebar-menu-search input {
  background: transparent;
  border: 0;
  color: #f8fafc;
  cursor: text;
  flex: 1;
  font-size: 13px;
  min-width: 0;
  outline: 0;
  padding: 8px 6px;
  pointer-events: auto;
  position: relative;
  z-index: 2;
}

.sidebar-menu-search input::placeholder {
  color: #8b95a4;
}

.sidebar-menu-search button {
  align-items: center;
  background: transparent;
  border: 0;
  display: none;
  height: 26px;
  justify-content: center;
  padding: 0;
  pointer-events: auto;
  width: 26px;
  z-index: 2;
}

.sidebar-menu-search.has-query button {
  display: inline-flex;
}

.sidebar .sub-menu .nav-item.sidebar-menu-overflow {
  display: none;
}

.sidebar .sub-menu.sidebar-menu-expanded .nav-item.sidebar-menu-overflow,
.sidebar .sub-menu.sidebar-menu-searching .nav-item.sidebar-menu-overflow {
  display: list-item;
}

.sidebar .sidebar-show-more .nav-link {
  color: #cbd5e1 !important;
  font-size: 12px;
  font-weight: 600;
}

.sidebar .sidebar-show-more .nav-link i {
  font-size: 15px;
  margin-right: 6px;
}

.sidebar .sidebar-menu-empty {
  color: #8b95a4;
  display: none;
  font-size: 12px;
  padding: 10px 24px;
}

.sidebar.sidebar-filtering .nav-item.sidebar-search-hidden {
  display: none !important;
}

.sidebar.sidebar-filtering .sidebar-menu-empty.is-visible {
  display: block;
}

.sidebar-help-panel {
  flex-shrink: 0;
  margin-top: auto;
  padding: 12px 18px 18px;
}

.sidebar-help-link {
  align-items: center;
  background: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 8px;
  color: #111827 !important;
  display: flex;
  gap: 10px;
  padding: 12px;
  text-decoration: none !important;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.sidebar-help-link:hover {
  border-color: #2563eb;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.14);
  color: #111827 !important;
  transform: translateY(-1px);
}

.sidebar-help-icon {
  align-items: center;
  background: #eff6ff;
  border-radius: 8px;
  color: #2563eb;
  display: inline-flex;
  flex: 0 0 34px;
  font-size: 20px;
  height: 34px;
  justify-content: center;
  width: 34px;
}

.sidebar-help-link strong,
.sidebar-help-link small {
  display: block;
  line-height: 1.2;
}

.sidebar-help-link strong {
  font-size: 13px;
}

.sidebar-help-link small {
  color: #64748b;
  font-size: 11px;
  margin-top: 2px;
}
</style>

<script>
// Fix sidebar accordion - only one submenu open at a time
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const compactLimit = 6;

    function setupCompactSubmenus() {
        if (!sidebar) return;

        const submenus = sidebar.querySelectorAll('.sub-menu');
        submenus.forEach(function(submenu) {
            if (submenu.dataset.compactReady === 'true') return;

            const items = Array.from(submenu.children).filter(function(item) {
                return item.classList.contains('nav-item') && item.querySelector('a.nav-link[href]');
            });

            if (items.length <= compactLimit + 2) {
                submenu.dataset.compactReady = 'true';
                return;
            }

            items.slice(compactLimit).forEach(function(item) {
                item.classList.add('sidebar-menu-overflow');
            });

            const moreCount = items.length - compactLimit;
            const moreItem = document.createElement('li');
            moreItem.className = 'nav-item sidebar-show-more';
            moreItem.innerHTML = '<button type="button" class="nav-link border-0 bg-transparent w-100 text-start"><i class="mdi mdi-chevron-down"></i><span>Show all ' + items.length + ' links</span></button>';
            submenu.appendChild(moreItem);

            const moreButton = moreItem.querySelector('button');
            moreButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const expanded = submenu.classList.toggle('sidebar-menu-expanded');
                moreButton.querySelector('i').className = expanded ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down';
                moreButton.querySelector('span').textContent = expanded
                    ? 'Show fewer links'
                    : 'Show all ' + items.length + ' links';
            });

            submenu.dataset.compactReady = 'true';
            submenu.dataset.moreCount = String(moreCount);
        });
    }

    function revealCurrentMenuItem() {
        if (!sidebar) return;

        const currentUrl = new URL(window.location.href);
        sidebar.querySelectorAll('.sub-menu a.nav-link[href]').forEach(function(link) {
            try {
                const linkUrl = new URL(link.getAttribute('href'), window.location.origin);
                if (linkUrl.pathname === currentUrl.pathname && linkUrl.search === currentUrl.search) {
                    const submenu = link.closest('.sub-menu');
                    if (submenu) {
                        submenu.classList.add('sidebar-menu-expanded');
                        const moreButton = submenu.querySelector('.sidebar-show-more button');
                        if (moreButton) {
                            moreButton.querySelector('i').className = 'mdi mdi-chevron-up';
                            moreButton.querySelector('span').textContent = 'Show fewer links';
                        }
                    }
                }
            } catch (e) {
                // Skip placeholder or invalid links.
            }
        });
    }

    function setupSidebarSearch() {
        if (!sidebar) return;

        const input = document.getElementById('sidebar-menu-search-input');
        const clear = document.getElementById('sidebar-menu-search-clear');
        const wrapper = sidebar.querySelector('.sidebar-menu-search');
        if (!input || !clear || !wrapper) return;

        const empty = document.createElement('li');
        empty.className = 'nav-item sidebar-menu-empty';
        empty.textContent = 'No matching menu items';
        sidebar.querySelector(':scope > .nav')?.appendChild(empty);

        const topLevelItems = Array.from(sidebar.querySelectorAll(':scope > .nav > .nav-item'))
            .filter(function(item) {
                return !item.classList.contains('sidebar-menu-empty');
            });

        const searchableItems = topLevelItems
            .filter(function(item) {
                return !item.classList.contains('nav-category');
            })
            .map(function(item) {
                const directLink = item.querySelector(':scope > .nav-link');
                const childLinks = Array.from(item.querySelectorAll('.sub-menu .nav-item a.nav-link[href]'));
                const keywords = [
                    directLink ? directLink.textContent : '',
                    item.dataset.search || '',
                    childLinks.map(function(link) { return link.textContent; }).join(' ')
                ].join(' ').replace(/\s+/g, ' ').trim().toLowerCase();

                return {
                    item: item,
                    directLink: directLink,
                    childLinks: childLinks,
                    collapse: item.querySelector(':scope > .collapse'),
                    toggle: item.querySelector(':scope > [data-bs-toggle="collapse"]'),
                    keywords: keywords,
                    wasOpen: item.querySelector(':scope > .collapse')?.classList.contains('show') || false,
                };
            });

        const categories = topLevelItems
            .filter(function(item) {
                return item.classList.contains('nav-category');
            })
            .map(function(category) {
                const controlledItems = [];
                let sibling = category.nextElementSibling;

                while (sibling && !sibling.classList.contains('nav-category')) {
                    if (sibling.classList.contains('nav-item') && !sibling.classList.contains('sidebar-menu-empty')) {
                        controlledItems.push(sibling);
                    }
                    sibling = sibling.nextElementSibling;
                }

                return {
                    category: category,
                    controlledItems: controlledItems,
                };
            });

        function setCollapseState(record, open) {
            if (!record.collapse) return;

            record.collapse.classList.toggle('show', open);
            if (record.toggle) {
                record.toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                record.toggle.classList.toggle('collapsed', !open);
            }
        }

        function clearChildFiltering(record) {
            record.childLinks.forEach(function(link) {
                const childItem = link.closest('.nav-item');
                if (childItem) {
                    childItem.classList.remove('sidebar-search-hidden');
                }
            });

            const submenu = record.item.querySelector('.sub-menu');
            if (submenu) {
                submenu.classList.remove('sidebar-menu-searching');
            }
        }

        function restoreMenuState() {
            searchableItems.forEach(function(record) {
                record.item.classList.remove('sidebar-search-hidden');
                clearChildFiltering(record);
                setCollapseState(record, record.wasOpen);
            });

            categories.forEach(function(record) {
                record.category.classList.remove('sidebar-search-hidden');
            });

            empty.classList.remove('is-visible');
        }

        function applyFilter() {
            const query = input.value.trim().toLowerCase();
            const filtering = query.length > 0;
            const wasFiltering = sidebar.classList.contains('sidebar-filtering');
            let visibleCount = 0;

            if (filtering && !wasFiltering) {
                searchableItems.forEach(function(record) {
                    record.wasOpen = record.collapse?.classList.contains('show') || false;
                });
            }

            sidebar.classList.toggle('sidebar-filtering', filtering);
            wrapper.classList.toggle('has-query', filtering);

            if (!filtering) {
                restoreMenuState();
                return;
            }

            searchableItems.forEach(function(record) {
                const parentMatch = record.keywords.includes(query);
                let childMatch = false;

                record.childLinks.forEach(function(link) {
                    const childItem = link.closest('.nav-item');
                    const matches = link.textContent.toLowerCase().includes(query);

                    if (childItem) {
                        childItem.classList.toggle('sidebar-search-hidden', !matches && !parentMatch);
                    }

                    childMatch = childMatch || matches;
                });

                const isVisible = parentMatch || childMatch;
                record.item.classList.toggle('sidebar-search-hidden', !isVisible);

                const submenu = record.item.querySelector('.sub-menu');
                if (submenu) {
                    submenu.classList.toggle('sidebar-menu-searching', isVisible);
                }

                setCollapseState(record, isVisible && record.childLinks.length > 0);

                if (isVisible) {
                    visibleCount++;
                }
            });

            categories.forEach(function(record) {
                const hasVisibleItems = record.controlledItems.some(function(item) {
                    return !item.classList.contains('sidebar-search-hidden');
                });
                record.category.classList.toggle('sidebar-search-hidden', !hasVisibleItems);
            });

            empty.classList.toggle('is-visible', visibleCount === 0);
        }

        input.addEventListener('input', applyFilter);
        input.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;

            const firstVisibleLink = Array.from(sidebar.querySelectorAll('.nav-item:not(.sidebar-search-hidden) a.nav-link[href]'))
                .find(function(link) {
                    const href = link.getAttribute('href') || '';
                    return href !== '#'
                        && !href.startsWith('#')
                        && link.closest('.nav-item')
                        && !link.closest('.nav-item').classList.contains('nav-category');
                });

            if (firstVisibleLink) {
                e.preventDefault();
                firstVisibleLink.click();
            }
        });

        clear.addEventListener('click', function() {
            input.value = '';
            input.focus();
            applyFilter();
        });
    }

    setupCompactSubmenus();
    revealCurrentMenuItem();
    setupSidebarSearch();

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
            menuToOpen.classList.add('sidebar-menu-expanded');
            const collapseId = menuToOpen.getAttribute('id');
            const toggle = document.querySelector(`[data-bs-toggle="collapse"][href="#${collapseId}"]`);
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.remove('collapsed');
            }
            const moreButton = menuToOpen.querySelector('.sidebar-show-more button');
            if (moreButton) {
                moreButton.querySelector('i').className = 'mdi mdi-chevron-up';
                moreButton.querySelector('span').textContent = 'Show fewer links';
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
