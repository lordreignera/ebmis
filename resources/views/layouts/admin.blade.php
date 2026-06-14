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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ERA Audit">
    <meta name="msapplication-TileColor" content="#4fd1c7">
    <meta name="msapplication-tap-highlight" content="no">
    
    <title>EBIMS - @yield('title', 'Dashboard')</title>
    <link rel="shortcut icon" href="{{ asset('admin/assets/images/ebims-logo.jpg') }}">
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
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #111827;
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

        .sidebar-overlay {
            display: none;
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
            background: #eef6ff !important;
            border-right: 1px solid #bfdbfe !important;
            z-index: 9999 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            transform: translateX(0) !important;
            transition: transform 0.25s ease !important;
        }
        
        /* Sidebar Brand */
        .sidebar .sidebar-brand-wrapper {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 70px !important;
            padding: 0 20px !important;
            background: #e0f2fe !important;
            border-bottom: 1px solid #bfdbfe !important;
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
            color: #0f172a !important;
            text-decoration: none !important;
            font-size: 14px !important;
            transition: all 0.25s ease !important;
            cursor: pointer !important;
        }
        
        .sidebar .nav .nav-link:hover {
            background: #dbeafe !important;
            color: #0f172a !important;
            padding-left: 25px !important;
        }
        
        .sidebar .nav .menu-icon {
            display: inline-flex !important;
            margin-right: 15px !important;
            font-size: 20px !important;
            color: #0f172a !important;
            width: 24px !important;
        }
        
        .sidebar .nav .menu-title {
            color: #0f172a !important;
            font-size: 14px !important;
            font-weight: 400 !important;
        }
        
        .sidebar .nav .menu-arrow {
            margin-left: auto !important;
            color: #334155 !important;
            font-size: 16px !important;
        }
        
        /* Nav Category */
        .sidebar .nav-category {
            display: block !important;
        }
        
        .sidebar .nav-category .nav-link {
            color: #475569 !important;
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

        .table-responsive,
        .mobile-table-scroll {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .table-responsive table,
        .mobile-table-scroll table {
            min-width: max-content;
            margin-bottom: 0;
        }

        .table-responsive::-webkit-scrollbar,
        .mobile-table-scroll::-webkit-scrollbar,
        .dataTables_wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-thumb,
        .mobile-table-scroll::-webkit-scrollbar-thumb,
        .dataTables_wrapper::-webkit-scrollbar-thumb {
            background: rgba(107, 114, 128, 0.6);
            border-radius: 999px;
        }

        .table-responsive::-webkit-scrollbar-track,
        .mobile-table-scroll::-webkit-scrollbar-track,
        .dataTables_wrapper::-webkit-scrollbar-track {
            background: rgba(229, 231, 235, 0.7);
        }

        .dataTables_wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Responsive - Mobile */
        @media (max-width: 991px) {
            nav.sidebar,
            .sidebar,
            #sidebar,
            nav#sidebar.sidebar.sidebar-offcanvas {
                width: min(82vw, 320px) !important;
                left: 0 !important;
                transform: translateX(-100%) !important;
                z-index: 1045 !important;
            }

            body.sidebar-open nav.sidebar,
            body.sidebar-open .sidebar,
            body.sidebar-open #sidebar,
            body.sidebar-open nav#sidebar.sidebar.sidebar-offcanvas,
            .sidebar.active {
                transform: translateX(0) !important;
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                z-index: 1040;
            }

            body.sidebar-open .sidebar-overlay {
                display: block;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .page-body-wrapper,
            .container-fluid.page-body-wrapper {
                padding-left: 0 !important;
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .navbar {
                left: 0 !important;
                width: 100% !important;
            }

            .main-panel {
                width: 100% !important;
            }
            
            .content-wrapper {
                padding: 1rem !important;
            }

            .footer {
                padding: 1rem !important;
            }

            .card-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .card-header .card-title {
                width: 100%;
                margin-bottom: 0;
            }

            .card-header .card-tools {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .small-box .inner h3 {
                font-size: 1.5rem !important;
                line-height: 1.2 !important;
                white-space: normal !important;
            }

            .small-box .inner p {
                white-space: normal !important;
            }

            .table {
                white-space: nowrap;
            }
        }

        @media (max-width: 575px) {
            .card-header .card-tools .btn {
                width: 100%;
            }

            .small-box .icon {
                display: none;
            }
        }

        /* EBIMS monochrome shell: final admin-wide color cleanup */
        body,
        .main-panel,
        .content-wrapper {
            background: #f7f7f7 !important;
            color: #111827 !important;
        }

        nav.sidebar,
        .sidebar,
        #sidebar,
        nav#sidebar.sidebar.sidebar-offcanvas {
            background: #eef6ff !important;
            border-right: 1px solid #bfdbfe !important;
            box-shadow: none !important;
        }

        .sidebar .sidebar-brand-wrapper {
            background: #e0f2fe !important;
            border-bottom: 1px solid #bfdbfe !important;
        }

        .sidebar .nav .nav-link,
        .sidebar .nav .menu-icon,
        .sidebar .nav .menu-title,
        .sidebar .nav .menu-arrow,
        .sidebar .nav-category .nav-link,
        .sidebar .profile-name h5,
        .sidebar .profile-name span {
            color: #111827 !important;
        }

        .sidebar .nav .nav-link:hover,
        .sidebar .nav .nav-item.active > .nav-link,
        .sidebar .nav .collapse.show .nav-link:hover {
            background: #dbeafe !important;
            color: #0f172a !important;
            padding-left: 25px !important;
            border-left: 4px solid #2563eb !important;
        }

        .navbar,
        .navbar .navbar-menu-wrapper,
        .navbar .navbar-brand-wrapper {
            background: #ffffff !important;
            color: #111827 !important;
            border-bottom: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 6px rgba(17, 24, 39, 0.08) !important;
        }

        .navbar .nav-link,
        .navbar .navbar-toggler,
        .navbar .navbar-toggler span,
        .navbar .navbar-nav .nav-item .nav-link i,
        .navbar .navbar-nav .nav-item .nav-link,
        .navbar .navbar-menu-wrapper .navbar-nav .nav-item .nav-link {
            color: #111827 !important;
        }

        .role-badge,
        .content-wrapper .role-badge {
            background: #f3f4f6 !important;
            border: 1px solid #e5e7eb !important;
            color: #111827 !important;
        }

        .content-wrapper .card > .card-header,
        .content-wrapper .modal-header,
        .content-wrapper .card-header.bg-primary,
        .content-wrapper .card-header.bg-success,
        .content-wrapper .card-header.bg-info,
        .content-wrapper .card-header.bg-warning,
        .content-wrapper .card-header.bg-danger,
        .content-wrapper .card-header.bg-dark,
        .content-wrapper .card-header.bg-secondary,
        .content-wrapper .card-header.text-white {
            background: #ffffff !important;
            border-bottom: 1px solid #e5e7eb !important;
            color: #111827 !important;
            box-shadow: none !important;
        }

        .content-wrapper .card > .card-header *,
        .content-wrapper .modal-header *,
        .content-wrapper .card-header.text-white * {
            color: #111827 !important;
        }

        .content-wrapper .small-box,
        .content-wrapper .small-box.bg-primary,
        .content-wrapper .small-box.bg-success,
        .content-wrapper .small-box.bg-info,
        .content-wrapper .small-box.bg-warning,
        .content-wrapper .small-box.bg-danger {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            color: #111827 !important;
            box-shadow: 0 1px 6px rgba(17, 24, 39, 0.06) !important;
        }

        .content-wrapper .small-box *,
        .content-wrapper .small-box .icon {
            color: #111827 !important;
        }
    </style>
    @stack('styles')
    <style>
        /* Final page-level guard after pushed styles */
        .content-wrapper .page-header,
        .content-wrapper .card > .card-header,
        .content-wrapper .modal-header {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            color: #111827 !important;
            box-shadow: 0 1px 6px rgba(17, 24, 39, 0.06) !important;
        }

        .content-wrapper .card > .card-header,
        .content-wrapper .modal-header {
            border-width: 0 0 1px 0 !important;
            box-shadow: none !important;
        }

        .content-wrapper .page-header *,
        .content-wrapper .card > .card-header *,
        .content-wrapper .modal-header * {
            color: #111827 !important;
        }

        .content-wrapper .page-title-icon,
        .content-wrapper .bg-gradient-primary,
        .content-wrapper .bg-gradient-success,
        .content-wrapper .bg-gradient-info,
        .content-wrapper .bg-gradient-warning,
        .content-wrapper .bg-gradient-danger {
            background: #f3f4f6 !important;
            color: #111827 !important;
            box-shadow: none !important;
        }

        .content-wrapper .table thead th,
        .content-wrapper table.dataTable thead th,
        .content-wrapper .table .table-dark,
        .content-wrapper .table-dark th {
            background: #eef4f8 !important;
            color: #111827 !important;
            border-color: #dbe5ec !important;
        }

        .content-wrapper .text-primary:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .text-success:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .text-info:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .text-warning:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .text-danger:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .text-secondary:not(.badge):not(.alert):not(.progress-bar) {
            color: #111827 !important;
        }

        .content-wrapper .bg-primary:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .bg-success:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .bg-info:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .bg-warning:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .bg-danger:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .bg-secondary:not(.badge):not(.alert):not(.progress-bar),
        .content-wrapper .bg-primary-subtle,
        .content-wrapper .bg-success-subtle,
        .content-wrapper .bg-info-subtle,
        .content-wrapper .bg-warning-subtle,
        .content-wrapper .bg-danger-subtle,
        .content-wrapper .bg-secondary-subtle {
            background: #f3f4f6 !important;
            color: #111827 !important;
            border-color: #e5e7eb !important;
        }

        .content-wrapper .icon-lg,
        .content-wrapper .icon-md,
        .content-wrapper .avatar-title:not(.badge) {
            color: #111827 !important;
        }

        .content-wrapper .badge.bg-primary,
        .content-wrapper .badge.bg-success,
        .content-wrapper .badge.bg-info,
        .content-wrapper .badge.bg-warning,
        .content-wrapper .badge.bg-danger,
        .content-wrapper .badge.bg-secondary,
        .content-wrapper .badge.text-bg-primary,
        .content-wrapper .badge.text-bg-success,
        .content-wrapper .badge.text-bg-info,
        .content-wrapper .badge.text-bg-warning,
        .content-wrapper .badge.text-bg-danger,
        .content-wrapper .badge.text-bg-secondary {
            background: #f3f4f6 !important;
            border: 1px solid #d1d5db !important;
            color: #111827 !important;
        }

        /* Keep data visualizations colorful while the surrounding UI stays restrained. */
        .content-wrapper canvas,
        .content-wrapper svg,
        .content-wrapper [id*="Chart"],
        .content-wrapper [id*="chart"],
        .content-wrapper [class*="chart"],
        .content-wrapper [class*="Chart"],
        .content-wrapper [class*="graph"],
        .content-wrapper [class*="Graph"],
        .content-wrapper .legend,
        .content-wrapper [class*="legend"],
        .content-wrapper .apexcharts-canvas {
            filter: none !important;
            color-scheme: normal;
        }

        .content-wrapper [class*="chart"] .bg-primary,
        .content-wrapper [class*="Chart"] .bg-primary,
        .content-wrapper [class*="graph"] .bg-primary,
        .content-wrapper [class*="Graph"] .bg-primary,
        .content-wrapper [class*="legend"] .bg-primary,
        .content-wrapper .legend .bg-primary,
        .content-wrapper [class*="chart"] .text-bg-primary,
        .content-wrapper [class*="legend"] .text-bg-primary {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }

        .content-wrapper [class*="chart"] .bg-success,
        .content-wrapper [class*="Chart"] .bg-success,
        .content-wrapper [class*="graph"] .bg-success,
        .content-wrapper [class*="Graph"] .bg-success,
        .content-wrapper [class*="legend"] .bg-success,
        .content-wrapper .legend .bg-success,
        .content-wrapper [class*="chart"] .text-bg-success,
        .content-wrapper [class*="legend"] .text-bg-success {
            background: #16a34a !important;
            border-color: #16a34a !important;
            color: #ffffff !important;
        }

        .content-wrapper [class*="chart"] .bg-info,
        .content-wrapper [class*="Chart"] .bg-info,
        .content-wrapper [class*="graph"] .bg-info,
        .content-wrapper [class*="Graph"] .bg-info,
        .content-wrapper [class*="legend"] .bg-info,
        .content-wrapper .legend .bg-info,
        .content-wrapper [class*="chart"] .text-bg-info,
        .content-wrapper [class*="legend"] .text-bg-info {
            background: #0891b2 !important;
            border-color: #0891b2 !important;
            color: #ffffff !important;
        }

        .content-wrapper [class*="chart"] .bg-warning,
        .content-wrapper [class*="Chart"] .bg-warning,
        .content-wrapper [class*="graph"] .bg-warning,
        .content-wrapper [class*="Graph"] .bg-warning,
        .content-wrapper [class*="legend"] .bg-warning,
        .content-wrapper .legend .bg-warning,
        .content-wrapper [class*="chart"] .text-bg-warning,
        .content-wrapper [class*="legend"] .text-bg-warning {
            background: #f59e0b !important;
            border-color: #f59e0b !important;
            color: #111827 !important;
        }

        .content-wrapper [class*="chart"] .bg-danger,
        .content-wrapper [class*="Chart"] .bg-danger,
        .content-wrapper [class*="graph"] .bg-danger,
        .content-wrapper [class*="Graph"] .bg-danger,
        .content-wrapper [class*="legend"] .bg-danger,
        .content-wrapper .legend .bg-danger,
        .content-wrapper [class*="chart"] .text-bg-danger,
        .content-wrapper [class*="legend"] .text-bg-danger {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
        }

        .content-wrapper [class*="chart"] .text-primary,
        .content-wrapper [class*="Chart"] .text-primary,
        .content-wrapper [class*="graph"] .text-primary,
        .content-wrapper [class*="Graph"] .text-primary,
        .content-wrapper [class*="legend"] .text-primary,
        .content-wrapper .legend .text-primary { color: #2563eb !important; }

        .content-wrapper [class*="chart"] .text-success,
        .content-wrapper [class*="Chart"] .text-success,
        .content-wrapper [class*="graph"] .text-success,
        .content-wrapper [class*="Graph"] .text-success,
        .content-wrapper [class*="legend"] .text-success,
        .content-wrapper .legend .text-success { color: #16a34a !important; }

        .content-wrapper [class*="chart"] .text-info,
        .content-wrapper [class*="Chart"] .text-info,
        .content-wrapper [class*="graph"] .text-info,
        .content-wrapper [class*="Graph"] .text-info,
        .content-wrapper [class*="legend"] .text-info,
        .content-wrapper .legend .text-info { color: #0891b2 !important; }

        .content-wrapper [class*="chart"] .text-warning,
        .content-wrapper [class*="Chart"] .text-warning,
        .content-wrapper [class*="graph"] .text-warning,
        .content-wrapper [class*="Graph"] .text-warning,
        .content-wrapper [class*="legend"] .text-warning,
        .content-wrapper .legend .text-warning { color: #d97706 !important; }

        .content-wrapper [class*="chart"] .text-danger,
        .content-wrapper [class*="Chart"] .text-danger,
        .content-wrapper [class*="graph"] .text-danger,
        .content-wrapper [class*="Graph"] .text-danger,
        .content-wrapper [class*="legend"] .text-danger,
        .content-wrapper .legend .text-danger { color: #dc2626 !important; }

        /* Button visibility guard: table hover rules must never wash out actions. */
        .content-wrapper .btn,
        .content-wrapper button.btn,
        .content-wrapper a.btn {
            border-width: 1px !important;
            text-decoration: none !important;
        }

        .content-wrapper .btn-dark,
        .content-wrapper .btn-primary {
            background: #111827 !important;
            border-color: #111827 !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-dark:hover,
        .content-wrapper .btn-dark:focus,
        .content-wrapper .btn-primary:hover,
        .content-wrapper .btn-primary:focus {
            background: #374151 !important;
            border-color: #374151 !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-outline-dark,
        .content-wrapper .btn-outline-secondary,
        .content-wrapper .btn-light {
            background: #ffffff !important;
            border-color: #9ca3af !important;
            color: #111827 !important;
        }

        .content-wrapper .btn-outline-dark:hover,
        .content-wrapper .btn-outline-dark:focus,
        .content-wrapper .btn-outline-secondary:hover,
        .content-wrapper .btn-outline-secondary:focus,
        .content-wrapper .btn-light:hover,
        .content-wrapper .btn-light:focus {
            background: #f3f4f6 !important;
            border-color: #111827 !important;
            color: #111827 !important;
        }

        .content-wrapper .btn-success {
            background: #16a34a !important;
            border-color: #16a34a !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-success:hover,
        .content-wrapper .btn-success:focus {
            background: #15803d !important;
            border-color: #15803d !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-info {
            background: #0891b2 !important;
            border-color: #0891b2 !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-info:hover,
        .content-wrapper .btn-info:focus {
            background: #0e7490 !important;
            border-color: #0e7490 !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-warning {
            background: #f59e0b !important;
            border-color: #f59e0b !important;
            color: #111827 !important;
        }

        .content-wrapper .btn-warning:hover,
        .content-wrapper .btn-warning:focus {
            background: #d97706 !important;
            border-color: #d97706 !important;
            color: #111827 !important;
        }

        .content-wrapper .btn-danger {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-danger:hover,
        .content-wrapper .btn-danger:focus {
            background: #b91c1c !important;
            border-color: #b91c1c !important;
            color: #ffffff !important;
        }

        .content-wrapper .btn-outline-primary,
        .content-wrapper .btn-outline-success,
        .content-wrapper .btn-outline-info,
        .content-wrapper .btn-outline-warning,
        .content-wrapper .btn-outline-danger {
            background: #ffffff !important;
            color: #111827 !important;
        }

        .content-wrapper .btn-outline-primary:hover,
        .content-wrapper .btn-outline-primary:focus {
            background: #eff6ff !important;
            border-color: #2563eb !important;
            color: #1d4ed8 !important;
        }

        .content-wrapper .btn-outline-success:hover,
        .content-wrapper .btn-outline-success:focus {
            background: #f0fdf4 !important;
            border-color: #16a34a !important;
            color: #15803d !important;
        }

        .content-wrapper .btn-outline-info:hover,
        .content-wrapper .btn-outline-info:focus {
            background: #ecfeff !important;
            border-color: #0891b2 !important;
            color: #0e7490 !important;
        }

        .content-wrapper .btn-outline-warning:hover,
        .content-wrapper .btn-outline-warning:focus {
            background: #fffbeb !important;
            border-color: #f59e0b !important;
            color: #92400e !important;
        }

        .content-wrapper .btn-outline-danger:hover,
        .content-wrapper .btn-outline-danger:focus {
            background: #fef2f2 !important;
            border-color: #dc2626 !important;
            color: #b91c1c !important;
        }

        .content-wrapper .table tbody tr:hover .btn,
        .content-wrapper .table tbody tr:hover .btn *,
        .content-wrapper table.table tbody tr:hover .btn,
        .content-wrapper table.table tbody tr:hover .btn * {
            color: inherit !important;
        }

        .content-wrapper .table tbody tr:hover .btn-dark,
        .content-wrapper .table tbody tr:hover .btn-primary,
        .content-wrapper .table tbody tr:hover .btn-success,
        .content-wrapper .table tbody tr:hover .btn-info,
        .content-wrapper .table tbody tr:hover .btn-danger {
            color: #ffffff !important;
        }

        .content-wrapper .table tbody tr:hover .btn-warning {
            color: #111827 !important;
        }

        .content-wrapper .table tbody tr:hover .btn-outline-dark,
        .content-wrapper .table tbody tr:hover .btn-outline-secondary,
        .content-wrapper .table tbody tr:hover .btn-payout-action {
            background: #ffffff !important;
            border-color: #111827 !important;
            color: #111827 !important;
        }

        .content-wrapper .btn:disabled,
        .content-wrapper .btn.disabled {
            background: #e5e7eb !important;
            border-color: #d1d5db !important;
            color: #6b7280 !important;
            opacity: 1 !important;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <!-- Sidebar -->
        @include('admin.sidebar_new')
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Page Body Wrapper -->
        <div class="container-fluid page-body-wrapper">
            <!-- Navbar -->
            @include('admin.navbar')
            
            <!-- Main Panel -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <!-- Password Change Reminder -->
                    <x-password-change-reminder />
                    
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const offcanvasToggles = document.querySelectorAll('[data-toggle="offcanvas"]');

            if (!sidebar || offcanvasToggles.length === 0) {
                return;
            }

            function isMobile() {
                return window.innerWidth <= 991;
            }

            function closeSidebar() {
                body.classList.remove('sidebar-open');
                sidebar.classList.remove('active');
            }

            function toggleSidebar() {
                if (!isMobile()) {
                    return;
                }

                const willOpen = !body.classList.contains('sidebar-open');
                body.classList.toggle('sidebar-open', willOpen);
                sidebar.classList.toggle('active', willOpen);
            }

            offcanvasToggles.forEach(function (toggle) {
                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    toggleSidebar();
                });
            });

            document.querySelectorAll('.content-wrapper table').forEach(function (table) {
                if (table.closest('.table-responsive, .mobile-table-scroll')) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'mobile-table-scroll';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            });

            sidebar.addEventListener('click', function (event) {
                const navLink = event.target.closest('a.nav-link');

                if (!navLink || !isMobile()) {
                    return;
                }

                if (navLink.hasAttribute('data-bs-toggle')) {
                    return;
                }

                closeSidebar();
            });

            closeSidebar();

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeSidebar();
                }
            });

            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    closeSidebar();
                }
            });
        });
    </script>
    
    <!-- CSRF Token Auto-Refresh & Session Management -->
    <script>
        // Auto-logout after 5 minutes of inactivity
        let inactivityTimer;
        const INACTIVITY_TIMEOUT = 5 * 60 * 1000; // 5 minutes in milliseconds

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            
            inactivityTimer = setTimeout(function() {
                // Show warning before logout
                if (confirm('You have been inactive for 5 minutes. Click OK to stay logged in, or Cancel to logout.')) {
                    // User wants to stay - refresh the session
                    fetch('/api/csrf-token', {
                        method: 'GET',
                        credentials: 'same-origin'
                    }).then(() => {
                        resetInactivityTimer(); // Reset timer again
                    });
                } else {
                    // Auto-logout
                    performLogout();
                }
            }, INACTIVITY_TIMEOUT);
        }

        function performLogout() {
            // Create a form and submit it to preserve session flash messages
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrfInput);
            
            // Append to body and submit
            document.body.appendChild(form);
            form.submit();
        }

        // Track user activity
        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        activityEvents.forEach(function(eventName) {
            document.addEventListener(eventName, resetInactivityTimer, true);
        });

        // Start the inactivity timer when page loads
        resetInactivityTimer();

        // Refresh CSRF token every 4 minutes (before 5 min timeout)
        setInterval(function() {
            fetch('/api/csrf-token', {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.token) {
                    // Update all CSRF token meta tags
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                    
                    // Update all CSRF input fields
                    document.querySelectorAll('input[name="_token"]').forEach(input => {
                        input.value = data.token;
                    });
                    
                    console.log('CSRF token refreshed');
                }
            })
            .catch(error => {
                console.warn('Failed to refresh CSRF token:', error);
            });
        }, 240000); // 4 minutes

        // Handle 419 Page Expired errors globally
        window.addEventListener('beforeunload', function() {
            // Store the intended action before page unload
            if (event.target.activeElement.form) {
                sessionStorage.setItem('lastFormAction', event.target.activeElement.form.action);
            }
        });

        // Intercept all form submissions to handle expired tokens
        document.addEventListener('DOMContentLoaded', function() {
            // Handle logout forms specifically
            document.querySelectorAll('form[action*="logout"]').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    // Don't prevent default - let the form submit naturally
                    // This preserves the flash message from the controller
                    // No need to intercept with JavaScript
                    return true;
                });
            });

            // OLD CODE - REMOVED TO FIX LOGOUT MESSAGE
            // The issue was that JavaScript was intercepting the form and doing a fetch()
            // which doesn't preserve Laravel's session flash messages
            // Now we let the form submit normally to preserve the success message
            
            /* BACKUP OF OLD CODE (removed):
                    e.preventDefault();
                    
                    // Force logout even if token is expired
            */ 

            // Global AJAX error handler for 419 errors
            if (typeof $ !== 'undefined') {
                $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                    if (jqxhr.status === 419) {
                        // Token expired - show alert and reload
                        alert('Your session has expired. The page will reload to refresh your session.');
                        window.location.reload();
                    }
                });
            }

            // Fetch interceptor for non-jQuery requests
            const originalFetch = window.fetch;
            window.fetch = function() {
                return originalFetch.apply(this, arguments)
                    .then(response => {
                        if (response.status === 419) {
                            alert('Your session has expired. The page will reload to refresh your session.');
                            window.location.reload();
                        }
                        return response;
                    });
            };
        });
    </script>
    
    <!-- Custom page scripts -->
    @stack('scripts')
</body>
</html>
