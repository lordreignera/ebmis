<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
  @include('admin.css')
  
  <!-- CRITICAL: Force Sidebar Styles - ULTRA AGGRESSIVE -->
  <style>
    /* FORCE SIDEBAR TO SHOW */
    body {
      margin: 0 !important;
      padding: 0 !important;
    }
    
    .container-scroller {
      position: relative !important;
      display: block !important;
      width: 100% !important;
      min-height: 100vh !important;
    }
    
    /* SIDEBAR - MAXIMUM SPECIFICITY */
    nav.sidebar,
    .sidebar,
    #sidebar,
    nav#sidebar.sidebar.sidebar-offcanvas {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      bottom: 0 !important;
      width: 260px !important;
      height: 100vh !important;
      background: linear-gradient(180deg, #1a237e 0%, #0d47a1 100%) !important;
      z-index: 9999 !important;
      overflow-y: auto !important;
      overflow-x: hidden !important;
      transform: translateX(0) !important;
      transition: none !important;
    }
    
    /* Sidebar Brand */
    .sidebar .sidebar-brand-wrapper {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      height: 70px !important;
      padding: 0 20px !important;
      background: rgba(0,0,0,0.1) !important;
      border-bottom: 1px solid rgba(255,255,255,0.1) !important;
    }
    
    .sidebar .sidebar-brand-wrapper img {
      max-height: 45px !important;
      max-width: 180px !important;
      display: block !important;
    }
    
    /* Sidebar Navigation */
    .sidebar ul.nav {
      display: block !important;
      padding: 10px 0 !important;
      margin: 0 !important;
      list-style: none !important;
    }
    
    .sidebar .nav li {
      display: block !important;
      list-style: none !important;
      margin: 0 !important;
    }
    
    .sidebar .nav .nav-item {
      display: block !important;
      list-style: none !important;
    }
    
    .sidebar .nav .nav-link {
      display: flex !important;
      align-items: center !important;
      padding: 14px 25px !important;
      color: rgba(255,255,255,0.85) !important;
      text-decoration: none !important;
      font-size: 14px !important;
      transition: all 0.25s ease !important;
      cursor: pointer !important;
    }
    
    .sidebar .nav .nav-link:hover {
      background: rgba(255,255,255,0.12) !important;
      color: #ffffff !important;
      padding-left: 30px !important;
    }
    
    .sidebar .nav .menu-icon {
      display: inline-flex !important;
      margin-right: 15px !important;
      font-size: 20px !important;
      color: rgba(255,255,255,0.9) !important;
      width: 24px !important;
    }
    
    .sidebar .nav .menu-title {
      color: rgba(255,255,255,0.95) !important;
      font-size: 14px !important;
      font-weight: 400 !important;
    }
    
    .sidebar .nav .menu-arrow {
      margin-left: auto !important;
      color: rgba(255,255,255,0.6) !important;
      font-size: 16px !important;
    }
    
    /* Nav Category */
    .sidebar .nav-category {
      display: block !important;
    }
    
    .sidebar .nav-category .nav-link {
      color: rgba(255,255,255,0.45) !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 1.2px !important;
      padding: 25px 25px 12px !important;
      pointer-events: none !important;
    }
    
    .sidebar .nav-category:first-child .nav-link {
      padding-top: 15px !important;
    }
    
    /* Profile Section */
    .sidebar .nav-item.profile {
      display: block !important;
      padding: 20px 25px !important;
      border-bottom: 1px solid rgba(255,255,255,0.1) !important;
      margin-bottom: 5px !important;
    }
    
    .sidebar .profile .profile-desc {
      display: block !important;
    }
    
    .sidebar .profile .profile-pic {
      display: flex !important;
      align-items: center !important;
    }
    
    .sidebar .profile .count-indicator {
      position: relative !important;
      margin-right: 15px !important;
    }
    
    .sidebar .profile img {
      width: 42px !important;
      height: 42px !important;
      border-radius: 50% !important;
      border: 2px solid rgba(255,255,255,0.3) !important;
      display: block !important;
    }
    
    .sidebar .profile .profile-name h5 {
      color: #ffffff !important;
      font-size: 15px !important;
      margin: 0 0 3px 0 !important;
      font-weight: 500 !important;
    }
    
    .sidebar .profile .profile-name span {
      color: rgba(255,255,255,0.65) !important;
      font-size: 12px !important;
      display: block !important;
    }
    
    /* Submenu/Collapse */
    .sidebar .nav .collapse {
      display: none !important;
      background: rgba(0,0,0,0.15) !important;
    }
    
    .sidebar .nav .collapse.show {
      display: block !important;
    }
    
    .sidebar .nav .sub-menu {
      display: block !important;
      padding: 5px 0 !important;
      margin: 0 !important;
      list-style: none !important;
    }
    
    .sidebar .nav .sub-menu .nav-item {
      display: block !important;
    }
    
    .sidebar .nav .sub-menu .nav-link {
      padding: 10px 25px 10px 65px !important;
      font-size: 13px !important;
      color: rgba(255,255,255,0.75) !important;
    }
    
    .sidebar .nav .sub-menu .nav-link:hover {
      color: #ffffff !important;
      background: rgba(255,255,255,0.08) !important;
      padding-left: 70px !important;
    }
    
    /* PAGE BODY WRAPPER - PUSH TO RIGHT */
    .page-body-wrapper,
    .container-fluid.page-body-wrapper {
      margin-left: 260px !important;
      width: calc(100% - 260px) !important;
      min-height: 100vh !important;
      background: #f4f5f7 !important;
      display: block !important;
    }
    
    /* Scrollbar for sidebar */
    .sidebar::-webkit-scrollbar {
      width: 6px !important;
    }
    
    .sidebar::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.05) !important;
    }
    
    .sidebar::-webkit-scrollbar-thumb {
      background: rgba(255,255,255,0.2) !important;
      border-radius: 3px !important;
    }
    
    .sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255,255,255,0.3) !important;
    }

    /* Prevent sidebar from being minimized */
    body.sidebar-icon-only .sidebar,
    body.sidebar-hidden .sidebar {
      display: block !important;
      width: 260px !important;
      left: 0 !important;
      transform: translateX(0) !important;
    }

    body.sidebar-icon-only .page-body-wrapper,
    body.sidebar-hidden .page-body-wrapper {
      margin-left: 260px !important;
      width: calc(100% - 260px) !important;
    }

    /* Hide the sidebar toggle button */
    .navbar-toggler[data-toggle="minimize"],
    button[data-toggle="minimize"] {
      display: none !important;
      visibility: hidden !important;
    }

   

  
    /* User profile dropdown - Ensure it's visible and positioned correctly */
   

    </style>
  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery Cookie Plugin (if used) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">

<!-- DataTables Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  </head>
  <body>
    <div class="container-scroller">
      <!-- Pro Banner (Hidden) - Required by misc.js -->
      <div class="row p-0 m-0 proBanner d-none" id="proBanner" style="display: none !important;">
        <div class="col-md-12 p-0 m-0">
          <div class="card-body card-body-padding d-flex align-items-center justify-content-between">
            <div class="ps-lg-1">
              <div class="d-flex align-items-center justify-content-between">
                <p class="mb-0 font-weight-medium me-3 buy-now-text">Emuria Micro Finance Limited</p>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <button id="bannerClose" class="btn border-0 p-0">
                <i class="mdi mdi-close text-white me-0"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      @include('admin.sidebar_new')

      <!-- Page Body Wrapper -->
      <div class="container-fluid page-body-wrapper">
        <!-- Navbar -->
        @include('admin.navbar')
        
        <!-- Main Panel / Content -->
        @include('admin.body')
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    
    <!-- plugins:js -->
    @include('admin.java')

    <!-- Force Sidebar to Show + Bootstrap Collapse Fix -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        console.log('🎯 Sidebar Initialization Starting...');
        
        // 1. Remove any classes that might hide sidebar and prevent them from being added
        document.body.classList.remove('sidebar-icon-only');
        document.body.classList.remove('sidebar-hidden');
        
        // Prevent minimize button from working
        document.querySelectorAll('[data-toggle="minimize"]').forEach(function(button) {
          button.style.display = 'none';
          button.remove(); // Remove it completely
        });

        // Prevent sidebar-icon-only class from being added
        const preventMinimize = function() {
          if (document.body.classList.contains('sidebar-icon-only')) {
            document.body.classList.remove('sidebar-icon-only');
          }
          if (document.body.classList.contains('sidebar-hidden')) {
            document.body.classList.remove('sidebar-hidden');
          }
        };
        
        // Check every 100ms
        setInterval(preventMinimize, 100);
        
        // Also observe for class changes
        const bodyObserver = new MutationObserver(preventMinimize);
        bodyObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        
        // 2. Find sidebar
        const sidebar = document.querySelector('.sidebar');
        console.log('✅ Sidebar element found:', !!sidebar);
        
        if (sidebar) {
          // Force visibility
          sidebar.style.cssText = `
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            width: 260px !important;
            z-index: 9999 !important;
          `;
          
          console.log('✅ Sidebar forced to visible');
          
          // 3. Initialize Bootstrap collapse functionality
          const menuItems = sidebar.querySelectorAll('[data-bs-toggle="collapse"]');
          console.log(`📂 Found ${menuItems.length} collapsible menu items`);
          
          menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
              e.preventDefault();
              const targetId = this.getAttribute('href');
              const targetElement = document.querySelector(targetId);
              
              if (targetElement) {
                // Toggle the collapse
                if (targetElement.classList.contains('show')) {
                  targetElement.classList.remove('show');
                  targetElement.style.display = 'none';
                } else {
                  // Close other open menus
                  sidebar.querySelectorAll('.collapse.show').forEach(openMenu => {
                    if (openMenu !== targetElement) {
                      openMenu.classList.remove('show');
                      openMenu.style.display = 'none';
                    }
                  });
                  
                  // Open clicked menu
                  targetElement.classList.add('show');
                  targetElement.style.display = 'block';
                }
              }
            });
          });
          
          console.log('✅ Collapse functionality initialized');
          
        } else {
          console.error('❌ SIDEBAR NOT FOUND!');
        }
        
        // 4. Ensure page body wrapper has correct margin
        const pageBody = document.querySelector('.page-body-wrapper');
        if (pageBody) {
          pageBody.style.marginLeft = '260px';
          console.log('✅ Page body margin set');
        }
        
        console.log('🎉 Sidebar initialization complete!');
      });
    </script>

    <!-- End custom js for this page -->
  </body>
</html>