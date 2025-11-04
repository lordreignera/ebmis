<!-- resources/views/admin/admin_layout.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4fd1c7">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ERA Audit">
    <meta name="msapplication-TileColor" content="#4fd1c7">
    <meta name="msapplication-tap-highlight" content="no">
    
    <title>EBIMS - @yield('title', 'Dashboard')</title>
    <!-- plugins:css -->
    @include('admin.css')
    <!-- Custom Health Audit System Styles -->
    <style>
        /* Card Body Styles */
        .card-body {
            background-color: #ffffff !important;
            color: #333333 !important;
        }
        
        /* Text Colors */
        .card-title {
            color: #2d3748 !important;
            font-weight: 600;
        }
        
        .text-muted {
            color: #718096 !important;
        }
        
        /* Statistics Cards */
        .audit-card h3 {
            color: #2d3748 !important;
            font-weight: 700;
        }
        
        /* Table Styles */
        .table {
            background-color: #ffffff !important;
            color: #333333 !important;
        }
        
        .table thead th {
            background-color: #f7fafc !important;
            color: #2d3748 !important;
            border-bottom: 2px solid #e2e8f0 !important;
            font-weight: 600;
        }
        
        .table tbody td {
            background-color: #ffffff !important;
            color: #4a5568 !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        
        .table tbody tr:hover {
            background-color: #f7fafc !important;
        }
        
        
        /* Badge Styles */
        .badge {
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #48bb78 !important;
            color: #ffffff !important;
        }
        
        .badge-warning {
            background-color: #ed8936 !important;
            color: #ffffff !important;
        }
        
        .badge-danger {
            background-color: #f56565 !important;
            color: #ffffff !important;
        }
        
        .badge-primary {
            background-color: #4299e1 !important;
            color: #ffffff !important;
        }
        
        /* Icon Styles */
        .icon-box-primary {
            background-color: #4299e1 !important;
            color: #ffffff !important;
        }
        
        .icon-box-success {
            background-color: #48bb78 !important;
            color: #ffffff !important;
        }
        
        .icon-box-warning {
            background-color: #ed8936 !important;
            color: #ffffff !important;
        }
        
        .icon-box-danger {
            background-color: #f56565 !important;
            color: #ffffff !important;
        }
        
        /* Quick Actions */
        .btn {
            font-weight: 500;
        }
        
        /* Role Badge */
        .role-badge {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            background-color: #f7fafc !important;
        }
        
        /* Welcome Message */
        .welcome-message {
            background-color: #ffffff !important;
            border-left: 4px solid #4fd1c7;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Form Elements */
        .form-control {
            background-color: #ffffff !important;
            color: #333333 !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        .form-control:focus {
            background-color: #ffffff !important;
            color: #333333 !important;
            border-color: #4fd1c7 !important;
            box-shadow: 0 0 0 0.2rem rgba(79, 209, 199, 0.25) !important;
        }
        
        .form-label {
            color: #2d3748 !important;
            font-weight: 500;
        }
        
        /* Fix dropdown issues */
        .navbar .dropdown-menu {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            border-radius: 8px !important;
            padding: 0.5rem 0 !important;
            z-index: 9999 !important;
            display: none !important;
            position: absolute !important;
            min-width: 200px !important;
        }
        
        .navbar .dropdown-menu.show {
            display: block !important;
        }
        
        .navbar .dropdown-item {
            color: #2d3748 !important;
            padding: 0.5rem 1rem !important;
            display: flex !important;
            align-items: center !important;
            text-decoration: none !important;
            background: transparent !important;
            border: none !important;
            width: 100% !important;
            text-align: left !important;
        }
        
        .navbar .dropdown-item:hover {
            background-color: #f7fafc !important;
            color: #2d3748 !important;
        }
        
        .navbar .dropdown-header {
            color: #718096 !important;
            padding: 0.5rem 1rem !important;
        }
        
        .navbar .dropdown-divider {
            border-top: 1px solid #e2e8f0 !important;
            margin: 0.25rem 0 !important;
        }
        
        /* Ensure dropdown toggle works */
        .navbar .nav-link.dropdown-toggle::after {
            display: none !important;
        }
        
        .navbar .dropdown-toggle {
            cursor: pointer !important;
        }
        
        /* Position dropdown correctly */
        .navbar .nav-item.dropdown {
            position: relative !important;
        }
        
        .navbar .dropdown-menu-right {
            right: 0 !important;
            left: auto !important;
        }
        
        /* CRITICAL: Fix Content Alignment with Sidebar */
        body {
            margin: 0;
            padding: 0;
        }
        
        .container-scroller {
            width: 100%;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* SIDEBAR - MAXIMUM SPECIFICITY (Match admin/home.blade.php) */
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
        
        .sidebar .profile .profile-name p {
            color: rgba(255,255,255,0.7) !important;
            font-size: 12px !important;
            margin: 0 !important;
        }
        
        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        
        /* PAGE BODY WRAPPER - PUSH TO RIGHT (Match admin/home.blade.php) */
        .page-body-wrapper,
        .container-fluid.page-body-wrapper {
            margin-left: 260px !important; /* Match sidebar width */
            width: calc(100% - 260px) !important;
            min-height: 100vh !important;
            background: #f4f5f7 !important;
            display: block !important;
        }
        
        .main-panel {
            width: 100% !important;
            min-height: calc(100vh - 64px);
            padding: 0 !important;
        }
        
        .content-wrapper {
            padding: 2rem !important;
            min-height: calc(100vh - 64px - 60px); /* navbar + footer */
        }
        
        .navbar {
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            left: 260px !important; /* Match sidebar width */
            z-index: 10 !important;
            transition: left 0.25s ease;
        }
        
        .footer {
            background: #f7fafc;
            padding: 1rem 2rem;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }
        
        /* Responsive - Mobile */
        @media (max-width: 991px) {
            .page-body-wrapper {
                padding-left: 0 !important;
            }
            
            .navbar {
                left: 0 !important;
            }
            
            .sidebar {
                left: -260px !important;
            }
            
            .sidebar.active {
                left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <!-- Sidebar -->
        @include('admin.sidebar_new')
        
        <!-- Page Body Wrapper -->
        <div class="container-fluid page-body-wrapper">
            <!-- Navbar -->
            @include('admin.navbar')
            
            <!-- Main Panel -->
            <div class="main-panel">
                <div class="content-wrapper">
                    @yield('content')
                </div>
                
                <!-- Footer -->
                <footer class="footer">
                    <div class="d-sm-flex justify-content-center justify-content-sm-between">
                        <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
                            &copy; {{ date('Y') }} <a href="#" target="_blank">Emuria Micro Finance Limited</a>. All rights reserved.
                        </span>
                        <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
                            School Banking & Investment Management System
                        </span>
                    </div>
                </footer>
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    
    <!-- plugins:js -->
    @include('admin.java')
    <!-- End custom js for this page -->
    
    <!-- Custom page scripts -->
    @stack('scripts')
</body>
</html>
