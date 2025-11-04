<!-- partial:partials/_navbar.html -->
<nav class="navbar p-0 fixed-top d-flex flex-row" style="background: linear-gradient(90deg, #1a237e 0%, #0d47a1 100%) !important; height: 64px !important; box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;">
    <!-- Mobile Brand -->
    <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center" style="background: rgba(0,0,0,0.1) !important; width: 260px !important;">
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
        <div class="d-none d-lg-flex align-items-center flex-grow-1" style="max-width: 600px; margin-left: 2rem;">
            <form class="w-100" style="margin: 0;">
                <div class="input-group" style="background: rgba(255,255,255,0.15); border-radius: 25px; padding: 2px;">
                    <span class="input-group-text" style="background: transparent; border: none; color: rgba(255,255,255,0.7);">
                        <i class="mdi mdi-magnify"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Search members, loans, transactions..." 
                           style="background: transparent; border: none; color: white !important; padding: 0.5rem 1rem;">
                </div>
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
                   style="background: #4fd1c7 !important; 
                          color: white !important; 
                          padding: 0.5rem 1.25rem !important; 
                          border-radius: 25px !important;
                          font-weight: 500 !important;
                          font-size: 0.875rem !important;
                          border: none !important;
                          box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
                          transition: all 0.3s ease !important;">
                    <i class="mdi mdi-plus-circle me-1"></i> QUICK ACTIONS
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" 
                     aria-labelledby="createbuttonDropdown"
                     style="border-radius: 10px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; min-width: 250px !important;">
                    <h6 class="p-3 mb-0 fw-bold" style="color: #333; border-bottom: 1px solid #e9ecef;">Quick Actions</h6>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="#" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-account-plus text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">New Member</p>
                            <small class="text-muted">Add new member</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="#" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-cash text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">New Loan</p>
                            <small class="text-muted">Process loan application</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="#" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-credit-card text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">Record Payment</p>
                            <small class="text-muted">Log payment transaction</small>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" href="#" style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-piggy-bank text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <p class="preview-subject mb-0 fw-medium">New Savings</p>
                            <small class="text-muted">Record savings deposit</small>
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
                          background: rgba(255,255,255,0.1); 
                          border-radius: 50%; 
                          color: white !important;
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
                          background: rgba(255,255,255,0.1); 
                          border-radius: 50%; 
                          color: white !important;
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
                          background: rgba(255,255,255,0.1); 
                          border-radius: 50%; 
                          color: white !important;
                          position: relative;
                          transition: all 0.3s ease !important;">
                    <i class="mdi mdi-bell" style="font-size: 1.25rem;"></i>
                    <span class="count bg-danger" style="position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; border-radius: 50%;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" 
                     aria-labelledby="notificationDropdown"
                     style="border-radius: 10px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; min-width: 350px !important;">
                    <h6 class="p-3 mb-0 fw-bold" style="color: #333; border-bottom: 1px solid #e9ecef;">Notifications</h6>
                    <a class="dropdown-item preview-item d-flex align-items-center" style="padding: 12px 20px !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-alert text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content flex-grow-1">
                            <p class="preview-subject mb-1 fw-medium">Overdue Repayments</p>
                            <p class="text-muted small mb-0">{{ $stats['repayments_due_count'] ?? 0 }} payments overdue</p>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" style="padding: 12px 20px !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-clock text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content flex-grow-1">
                            <p class="preview-subject mb-1 fw-medium">Pending Approvals</p>
                            <p class="text-muted small mb-0">{{ $stats['pending_approval'] ?? 0 }} loans awaiting approval</p>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item preview-item d-flex align-items-center" style="padding: 12px 20px !important;">
                        <div class="preview-thumbnail me-3">
                            <div class="preview-icon bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="mdi mdi-account-clock text-white"></i>
                            </div>
                        </div>
                        <div class="preview-item-content flex-grow-1">
                            <p class="preview-subject mb-1 fw-medium">Pending Members</p>
                            <p class="text-muted small mb-0">{{ $stats['pending_members'] ?? 0 }} members awaiting activation</p>
                        </div>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <p class="p-3 mb-0 text-center text-primary fw-medium" style="cursor: pointer;">View all notifications</p>
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
                   style="color: white !important; 
                          cursor: pointer; 
                          padding: 0.5rem 1rem !important;
                          background: rgba(255,255,255,0.1);
                          border-radius: 25px;
                          transition: all 0.3s ease !important;">
                    <div class="d-flex align-items-center">
                        <img src="{{ Auth::user()->profile_photo_url ?? asset('admin/assets/images/faces/face4.jpg') }}" 
                             alt="Profile" 
                             class="rounded-circle me-2" 
                             width="32" 
                             height="32"
                             style="border: 2px solid rgba(255,255,255,0.3);">
                        <span class="d-none d-xl-inline fw-medium">{{ Auth::user()->name ?? 'Super Admin' }}</span>
                        <i class="mdi mdi-chevron-down ms-2"></i>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end" 
                     aria-labelledby="userDropdown"
                     style="min-width: 250px !important; 
                            border-radius: 10px !important;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                            border: none !important;
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
                       href="{{ route('admin.users.edit', Auth::id()) }}" 
                       style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <i class="mdi mdi-account-outline me-3" style="font-size: 1.25rem; color: #4299e1;"></i>
                        <span>My Profile</span>
                    </a>
                    <a class="dropdown-item d-flex align-items-center" 
                       href="#" 
                       style="padding: 12px 20px !important; transition: background 0.2s !important;">
                        <i class="mdi mdi-settings-outline me-3" style="font-size: 1.25rem; color: #48bb78;"></i>
                        <span>Settings</span>
                    </a>
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
                                       background: none; 
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
    background: rgba(255,255,255,0.2) !important;
}

.navbar .create-new-button:hover {
    background: #38b2ac !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
}

/* Dropdown Item Hover */
.dropdown-item:hover {
    background-color: #f8f9fa !important;
}

/* Search Input Placeholder */
.navbar input::placeholder {
    color: rgba(255,255,255,0.6) !important;
}

/* Ensure navbar stays on top */
.navbar.fixed-top {
    z-index: 1030 !important;
}

/* Add margin to main content to account for fixed navbar */
.main-panel {
    padding-top: 64px !important;
}

/* Force dropdown to work */
.dropdown-menu.show {
    display: block !important;
}
</style>

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